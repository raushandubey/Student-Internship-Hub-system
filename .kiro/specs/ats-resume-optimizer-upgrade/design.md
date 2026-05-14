# Design Document: ATS Resume Optimizer Upgrade

## Overview

This document describes the technical design for upgrading the existing AI Resume Rewrite system in the Laravel-based Internship Platform. The upgrade enhances four existing components — `ResumeOptimizationService`, `ResumePdfService`, `ResumeOptimizerController`, and the chatbot's `ResumeOptimizerChatbot` module — without rebuilding from scratch or breaking backward compatibility.

The design follows the existing Laravel service-layer architecture and preserves all current database schemas, model methods, and API contracts.

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        HTTP Layer                               │
│  ResumeOptimizerController (thin JSON API, no business logic)   │
│  GET  /resume-optimizer/score/{internship}                      │
│  POST /resume-optimizer/rewrite/{internship}                    │
│  GET  /resume-optimizer/download/{internship}?version_id=       │
│  GET  /resume-optimizer/chatbot/analyse?internship_id=          │
│  GET  /resume-optimizer/internships                             │
└────────────────────┬────────────────────────────────────────────┘
                     │
         ┌───────────┴───────────┐
         ▼                       ▼
┌─────────────────┐   ┌──────────────────────┐
│ ResumeOptimiza- │   │  ResumePdfService     │
│ tionService     │   │                       │
│                 │   │  parseResumeText()    │
│ analyseResume() │   │  buildPdfData()       │
│ rewriteResume() │   │  downloadPdf()        │
│ scoreResume()   │   │  detectSections()     │
│ callOpenAI()    │   │  parseEntries()       │
│ ruleBasedRew.() │   │  parseProjects()      │
│ validateOutput()│   │  parseEducation()     │
└────────┬────────┘   └──────────┬───────────┘
         │                       │
         ▼                       ▼
┌─────────────────────────────────────────────┐
│              Data Layer                     │
│  ResumeScore   (resume_scores table)        │
│  ResumeVersion (resume_versions table)      │
│  Profile       (profiles table)             │
│  Internship    (internships table)          │
└─────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────┐
│           External Services                 │
│  OpenAI GPT-4o-mini  (AI rewrite)           │
│  smalot/pdfparser    (PDF text extraction)  │
│  barryvdh/laravel-dompdf (PDF generation)   │
│  AWS S3 / Local disk (resume file storage)  │
└─────────────────────────────────────────────┘
```

### Frontend Integration

```
chatbot.blade.php
    └── ResumeOptimizerChatbot (public/js/chatbot.js, lines 1174+)
            ├── startFlow()          → fetch /resume-optimizer/internships
            ├── _analyseForJob()     → fetch /resume-optimizer/chatbot/analyse
            ├── _triggerRewrite()    → POST /resume-optimizer/rewrite/{id}
            └── PDF download link   → GET  /resume-optimizer/download/{id}?version_id=
```

---

## Component Designs

### 1. ResumeOptimizationService (Enhanced)

The service is enhanced in-place. No new classes are introduced. All existing public method signatures are preserved.

#### 1.1 Scoring Engine (`scoreResume`)

The current weighted scoring formula is correct and stays unchanged:

| Component | Weight | Method |
|---|---|---|
| Skill Match | 40% | `scoreSkillMatch()` |
| Keyword Match | 25% | `scoreKeywordMatch()` |
| Experience Relevance | 20% | `scoreExperienceRelevance()` |
| Project Relevance | 10% | `scoreProjectRelevance()` |
| Completeness | 5% | `scoreCompleteness()` |

**Improvements to the scoring engine:**

**Skill Match — smarter matching:**
The current implementation uses `str_contains` which can produce false positives (e.g., "Java" matching "JavaScript"). The upgrade uses word-boundary matching:

```php
// BEFORE (current — false positives)
if (str_contains($resumeLower, $skill)) { ... }

// AFTER (upgraded — word-boundary aware)
private function skillExistsInText(string $skill, string $resumeLower): bool
{
    // Exact word boundary match
    $escaped = preg_quote($skill, '/');
    if (preg_match('/\b' . $escaped . '\b/i', $resumeLower)) {
        return true;
    }
    // Partial match for multi-word skills (e.g., "machine learning")
    if (str_word_count($skill) > 1 && str_contains($resumeLower, $skill)) {
        return true;
    }
    return false;
}
```

**Keyword extraction — stop-word list expansion:**
The current stop-word list is extended with more common filler words to reduce noise in keyword scoring. The `extractJobKeywords` method also deduplicates against `required_skills` to avoid double-counting.

**Experience relevance — section boundary detection:**
Instead of taking a fixed 2000-character window from the first occurrence of "experience", the upgraded method finds the actual section boundaries using the same `detectSectionBoundaries` helper used by `ResumePdfService`.

**Completeness scoring — contact field detection:**
The current phone regex `/[6-9]\d{9}/` only matches Indian mobile numbers. The upgrade adds a broader international pattern as a secondary check.

#### 1.2 Output Validation (`validateRewriteOutput`)

A new private method added after the AI rewrite call to enforce quality gates before persisting:

```php
private function validateRewriteOutput(string $text, Internship $internship): array
{
    $issues = [];
    $lower  = strtolower($text);

    // 1. Required sections present
    $requiredSections = ['professional summary', 'technical skills', 'experience', 'education'];
    foreach ($requiredSections as $section) {
        if (!str_contains($lower, $section)) {
            $issues[] = "Missing section: {$section}";
        }
    }

    // 2. Word count within limit
    $wordCount = str_word_count($text);
    if ($wordCount > 650) {  // 650 = 600 + 8% buffer for parsing variance
        $issues[] = "Word count {$wordCount} exceeds 600-word limit";
    }

    // 3. Job title mentioned in summary
    $summaryStart = stripos($text, 'professional summary');
    if ($summaryStart !== false) {
        $summaryBlock = strtolower(substr($text, $summaryStart, 400));
        if (!str_contains($summaryBlock, strtolower($internship->title))) {
            $issues[] = 'Professional summary does not mention target job title';
        }
    }

    // 4. No PDF binary artifacts
    if (preg_match('/%PDF-|endobj|xref|%%EOF/', $text)) {
        $issues[] = 'Output contains PDF binary artifacts';
    }

    return $issues;
}
```

If `validateRewriteOutput` returns any issues, the service logs them and falls back to `ruleBasedRewrite`. This prevents garbage output from being stored.

#### 1.3 AI Prompt Engineering (`callOpenAIRewrite`)

The existing prompt structure is good. The upgrade adds two improvements:

**Improvement 1 — Explicit bullet transformation example in the prompt:**
```
EXAMPLE TRANSFORMATION:
WEAK:   "Worked on APIs"
STRONG: "Developed and optimized RESTful APIs using Laravel, reducing average response time and improving scalability for concurrent requests"
```

**Improvement 2 — Explicit word count instruction:**
```
WORD COUNT: Output must be 500-600 words total. Count carefully.
```

**Improvement 3 — Skills grouping instruction:**
```
SKILLS FORMAT: Group as → Languages: PHP, Python | Frameworks: Laravel, React | Tools: Git, Docker | Databases: MySQL, Redis
```

The `max_tokens` stays at 1200 (sufficient for 600 words with formatting overhead). Temperature stays at 0.4.

#### 1.4 Rule-Based Rewrite (`ruleBasedRewrite`)

The existing rule-based rewrite is enhanced to also improve bullet points when OpenAI is unavailable:

```php
private function enhanceBulletPoints(array $bullets, array $jobSkills): array
{
    $weakVerbs = [
        'worked on'      => 'Developed',
        'helped with'    => 'Contributed to',
        'did some'       => 'Implemented',
        'was responsible' => 'Led',
        'assisted in'    => 'Supported',
        'did'            => 'Executed',
    ];

    $enhanced = [];
    foreach ($bullets as $bullet) {
        $lower = strtolower($bullet);
        foreach ($weakVerbs as $weak => $strong) {
            if (str_starts_with($lower, $weak)) {
                $bullet = $strong . ' ' . substr($bullet, strlen($weak));
                break;
            }
        }
        $enhanced[] = $bullet;
    }
    return $enhanced;
}
```

---

### 2. ResumePdfService (Enhanced)

#### 2.1 Section Parser Improvements

**Problem:** `parseEntries()` uses a regex split on capitalized lines which misidentifies skill names and section headers as experience entries.

**Fix — explicit section-boundary-aware parsing:**

```php
private function parseEntries(string $text): array
{
    $entries = [];
    $lines   = array_values(array_filter(
        array_map('trim', explode("\n", $text))
    ));

    $current = null;

    foreach ($lines as $line) {
        if (empty($line)) continue;

        $isBullet = str_starts_with($line, '•')
                 || str_starts_with($line, '-')
                 || str_starts_with($line, '*');

        // Pipe-separated header line: "Company | Role | Jan 2024 – Present | City"
        if (!$isBullet && substr_count($line, '|') >= 1 && strlen($line) < 120) {
            if ($current) $entries[] = $current;
            $parts   = array_map('trim', explode('|', $line));
            $current = [
                'org'      => $parts[0] ?? '',
                'title'    => $parts[1] ?? '',
                'date'     => $parts[2] ?? '',
                'location' => $parts[3] ?? '',
                'bullets'  => [],
            ];
        }
        // Short non-bullet line that looks like a title (no pipe)
        elseif (!$isBullet && strlen($line) < 80 && preg_match('/[A-Z]/', $line[0] ?? '')) {
            if ($current && empty($current['title']) && !empty($current['org'])) {
                $current['title'] = $line;
            } elseif ($current && !empty($current['title'])) {
                // Could be a date line
                if (preg_match('/\d{4}|present/i', $line)) {
                    $current['date'] = $line;
                } else {
                    $entries[] = $current;
                    $current = ['org' => $line, 'title' => '', 'date' => '', 'location' => '', 'bullets' => []];
                }
            } else {
                if ($current) $entries[] = $current;
                $current = ['org' => $line, 'title' => '', 'date' => '', 'location' => '', 'bullets' => []];
            }
        }
        // Bullet line
        elseif ($current !== null) {
            $bullet = ltrim($line, '•-* ');
            if (!empty($bullet)) {
                $current['bullets'][] = $bullet;
            }
        }
    }

    if ($current) $entries[] = $current;
    return array_slice($entries, 0, 5);
}
```

#### 2.2 PDF Template Improvements (`resources/views/resume/pdf-template.blade.php`)

The existing template is structurally sound. The following CSS improvements ensure single-page fit:

```css
/* Tighter body font for more content per page */
body {
    font-size: 9pt;       /* was 9.5pt */
    line-height: 1.3;     /* was 1.35 */
}

/* Tighter section spacing */
.section { margin-bottom: 7px; }  /* was 9px */

/* Prevent orphaned section headers */
.section-title { page-break-after: avoid; }

/* Overflow guard — truncate rather than overflow to page 2 */
body { overflow: hidden; max-height: 267mm; }
```

**Skills grouping display** — the template already handles `Languages: ... | Frameworks: ...` format via the `$hasCategories` check. No change needed.

#### 2.3 One-Page Enforcement

DomPDF does not natively truncate to one page. The strategy is:

1. The AI prompt enforces ≤600 words in the text output.
2. `validateRewriteOutput` checks word count before persisting.
3. The PDF template uses `overflow: hidden` on body with `max-height: 267mm` (A4 height minus margins).
4. `page-break-inside: avoid` on `.entry` and `.edu-entry` prevents mid-entry splits.

---

### 3. ResumeOptimizerController (No Changes Required)

The controller already exposes all required endpoints with correct JSON response shapes. No changes are needed to the controller itself. All improvements are in the service layer.

Existing endpoints:
- `GET /resume-optimizer/score/{internship}` → `score()`
- `POST /resume-optimizer/rewrite/{internship}` → `rewrite()`
- `GET /resume-optimizer/download/{internship}` → `downloadPdf()`
- `GET /resume-optimizer/chatbot/analyse` → `chatbotAnalyse()`
- `GET /resume-optimizer/internships` → `internshipsList()`

---

### 4. Chatbot Integration (`public/js/chatbot.js`)

The `ResumeOptimizerChatbot` module (lines 1174–1465) already implements the full conversation flow. The upgrade adds a PDF download button to the rewrite completion message.

#### 4.1 PDF Download Button in Chatbot

**Current behavior:** After rewrite, the chatbot shows a text summary with a "View Recommendations" link.

**Upgraded behavior:** After rewrite, the chatbot also shows a "Download PDF" button that triggers the download endpoint.

```javascript
// In _triggerRewrite(), replace the final displayMessage call:
ShreeRamChatbot.displayMessage({
    type: 'bot',
    text: `${arrow} Resume Optimization Complete!\n\nBEFORE: ${d.before_score}%\nAFTER:  ${d.after_score}% (+${delta}%)\n\nImprovements:\n${d.improvements.slice(0, 3).map(i => '  ' + i).join('\n')}`,
    timestamp: new Date(),
    links: [
        {
            text: '⬇️ Download Optimized Resume (PDF)',
            url: `/resume-optimizer/download/${job.id}?version_id=${d.version_id}`,
            icon: 'fa-file-pdf'
        },
        {
            text: 'View Recommendations',
            url: '/recommendations',
            icon: 'fa-star'
        }
    ],
    quickReplies: ['Track Applications', 'Job Strategy']
});
```

---

## Data Flow Diagrams

### Resume Analysis Flow

```
User Request (GET /score/{internship})
    │
    ▼
ResumeOptimizerController::score()
    │
    ▼
ResumeOptimizationService::analyseResume(User, Internship)
    │
    ├─► extractResumeText(Profile)
    │       ├─► smalot/pdfparser (primary)
    │       └─► BT/ET regex fallback (if < 80 chars)
    │
    ├─► scoreResume(resumeText, Internship)
    │       ├─► extractJobKeywords(Internship)
    │       ├─► scoreSkillMatch()      → 40%
    │       ├─► scoreKeywordMatch()    → 25%
    │       ├─► scoreExperienceRelevance() → 20%
    │       ├─► scoreProjectRelevance()    → 10%
    │       └─► scoreCompleteness()        → 5%
    │
    ├─► ResumeScore::updateOrCreate()
    │
    └─► return JSON { score, skill_match, keyword_score, tier, issues, strengths, ... }
```

### Resume Rewrite Flow

```
User Request (POST /rewrite/{internship})
    │
    ▼
ResumeOptimizerController::rewrite()
    │
    ▼
ResumeOptimizationService::rewriteResume(User, Internship)
    │
    ├─► extractResumeText()          → resumeText
    ├─► scoreResume(resumeText)      → beforeBreakdown (beforeScore)
    │
    ├─► callOpenAIRewrite(resumeText, Internship)
    │       ├─► [API key present?]
    │       │       YES → OpenAI GPT-4o-mini (temp=0.4, max_tokens=1200)
    │       │       NO  → ruleBasedRewrite()
    │       └─► [API call fails?]
    │               → ruleBasedRewrite() + Log::error()
    │
    ├─► validateRewriteOutput(rewrittenText, Internship)
    │       └─► [validation fails?] → ruleBasedRewrite() + Log::warning()
    │
    ├─► scoreResume(rewrittenText)   → afterBreakdown (afterScore)
    ├─► sanitizeForStorage()
    ├─► saveOriginalSnapshot()       → ResumeVersion (type=original)
    ├─► ResumeVersion::create()      → ResumeVersion (type=ai_rewrite)
    ├─► ResumeScore::updateOrCreate()
    ├─► buildImprovementsList()
    │
    └─► return JSON { before_score, after_score, improvements, version_id, rewritten_text }
```

### PDF Generation Flow

```
User Request (GET /download/{internship}?version_id=X)
    │
    ▼
ResumeOptimizerController::downloadPdf()
    │
    ▼
ResumePdfService::downloadPdf(User, Internship, versionId)
    │
    ├─► ResumeVersion::find(versionId) or latestAiVersion()
    │
    ├─► buildPdfData(User, Internship, content)
    │       ├─► parseResumeText(text, Internship)
    │       │       ├─► detectSections(lines)
    │       │       ├─► parseSkills()
    │       │       ├─► parseEntries()      (enhanced)
    │       │       ├─► parseProjects()
    │       │       ├─► parseEducation()
    │       │       └─► parseCertifications()
    │       └─► extractPhone(), extractLocation()
    │
    ├─► Pdf::loadView('resume.pdf-template', data)
    │       └─► DomPDF A4 portrait, 150 DPI, DejaVu Sans
    │
    └─► return PDF download response (AI_Optimised_{Name}_{Job}.pdf)
```

---

## Database Schema (Unchanged)

Both tables remain unchanged. No migrations are required.

### `resume_scores`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK | |
| internship_id | bigint FK | |
| overall_score | int | 0–100 |
| skill_match_score | int | 0–100 |
| keyword_score | int | 0–100 |
| format_score | int | 0–100 (stores completeness) |
| matching_skills | json | array |
| missing_skills | json | array |
| issues | json | array |
| strengths | json | array |
| match_tier | varchar | low/medium/high |
| resume_version_id | bigint FK nullable | |
| created_at / updated_at | timestamps | |

### `resume_versions`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK | |
| internship_id | bigint FK | |
| version_number | int | increments per user+job |
| label | varchar | "Original" / "AI Optimized — v2" |
| content | longtext | plain-text resume |
| file_path | varchar nullable | unused currently |
| score_at_creation | int | |
| type | varchar | original / ai_rewrite |
| created_at / updated_at | timestamps | |

---

## Error Handling Strategy

| Scenario | Handling |
|---|---|
| No resume uploaded | Return `emptyScore()` with user-friendly message |
| PDF file not found on disk/S3 | Log warning, return `emptyScore()` |
| smalot/pdfparser fails | Fall back to BT/ET regex extraction |
| Both extraction methods yield < 80 chars | Return `emptyScore('Unable to read your resume...')` |
| OpenAI API key not configured | Fall back to `ruleBasedRewrite()`, log warning |
| OpenAI API call fails/times out | Fall back to `ruleBasedRewrite()`, log error |
| Output validation fails | Fall back to `ruleBasedRewrite()`, log warning with issues |
| ResumeVersion not found for PDF | `abort(404, 'Resume version not found...')` |
| PDF generation fails | `abort(500, 'PDF generation failed...')` |
| Controller exception | Return `{ success: false, error: '...' }` JSON with 500 status |

---

## Correctness Properties for Property-Based Testing

These properties define the formal correctness of the system and can be validated with PBT:

### P1: Score Bounds
**Property:** For any resume text and internship, the overall ATS score is always in [0, 100].
```
∀ resumeText, internship: 0 ≤ scoreResume(resumeText, internship).overall_score ≤ 100
```

### P2: Score Component Weights Sum to 100%
**Property:** The weighted sum formula always produces a value consistent with the declared weights.
```
overall = (skillMatch × 0.40) + (keywordScore × 0.25) + (expScore × 0.20) + (projScore × 0.10) + (completeness × 0.05)
∀ components ∈ [0,100]: overall ∈ [0,100]
```

### P3: Tier Consistency
**Property:** The match tier is always consistent with the overall score.
```
∀ score: scoreTier(score) = "high"   ↔ score ≥ 70
∀ score: scoreTier(score) = "medium" ↔ 60 ≤ score < 70
∀ score: scoreTier(score) = "low"    ↔ score < 60
```

### P4: Missing Skills Complement
**Property:** The union of matching_skills and missing_skills always equals the full set of job required_skills.
```
∀ jobSkills, resumeText:
    matchingSkills ∪ missingSkills = jobSkills
    matchingSkills ∩ missingSkills = ∅
```

### P5: Rewrite Preserves Real Data
**Property:** The rewritten resume never removes company names, dates, or educational institutions that exist in the original.
```
∀ originalText, rewrittenText:
    extractedCompanies(originalText) ⊆ rewrittenText (case-insensitive)
    extractedDates(originalText) ⊆ rewrittenText
```

### P6: Score Non-Decrease After Rewrite
**Property:** The after-score is always ≥ the before-score when the rewrite adds missing skills to the Technical Skills section.
```
∀ original, rewritten where rewritten contains all jobSkills:
    scoreResume(rewritten).skill_match ≥ scoreResume(original).skill_match
```

### P7: Sanitization Idempotency
**Property:** Applying `sanitizeForStorage` twice produces the same result as applying it once.
```
∀ text: sanitizeForStorage(sanitizeForStorage(text)) = sanitizeForStorage(text)
```

### P8: PDF Section Completeness
**Property:** If the plain-text resume contains a section header, `parseResumeText` always returns a non-empty value for that section.
```
∀ text containing "PROFESSIONAL SUMMARY":
    parseResumeText(text).summary ≠ ""
```

---

## Files to Modify

| File | Change Type | Description |
|---|---|---|
| `app/Services/ResumeOptimizationService.php` | Enhance | Add `validateRewriteOutput()`, improve `scoreResume()` skill matching, enhance AI prompt, improve `ruleBasedRewrite()` bullet enhancement |
| `app/Services/ResumePdfService.php` | Enhance | Improve `parseEntries()` with pipe-separator support, fix section boundary detection |
| `resources/views/resume/pdf-template.blade.php` | Enhance | Tighten CSS for reliable single-page output |
| `public/js/chatbot.js` | Enhance | Add PDF download link to rewrite completion message in `_triggerRewrite()` |

## Files NOT Modified

| File | Reason |
|---|---|
| `app/Http/Controllers/ResumeOptimizerController.php` | API contracts already correct |
| `app/Models/ResumeScore.php` | Schema and methods unchanged |
| `app/Models/ResumeVersion.php` | Schema and methods unchanged |
| `database/migrations/*` | No schema changes required |
| `resources/views/components/chatbot.blade.php` | No template changes needed |
