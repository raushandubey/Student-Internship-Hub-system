# Implementation Plan: ATS Resume Optimizer Upgrade

## Overview

Upgrade four existing files in-place to improve ATS scoring accuracy, output quality, PDF rendering, and chatbot UX. No new classes, no database migrations, no controller changes. All tasks build incrementally toward a fully wired, validated system.

## Tasks

- [x] 1. Improve skill matching in `ResumeOptimizationService::scoreResume()`
  - [x] 1.1 Add `skillExistsInText()` private method with word-boundary regex
    - Replace the `str_contains($resumeLower, $skill)` call in the skill-match loop with a call to the new `skillExistsInText()` helper
    - Implement exact word-boundary match via `preg_match('/\b' . preg_quote($skill, '/') . '\b/i', ...)` as primary check
    - Add partial-match fallback for multi-word skills (e.g. "machine learning") using `str_word_count($skill) > 1 && str_contains(...)`
    - _Requirements: 1.3_

  - [x] 1.2 Write property test for skill matching (P4: Missing Skills Complement)
    - **Property 4: Missing Skills Complement**
    - For any set of job skills and any resume text, `matchingSkills ∪ missingSkills = jobSkills` and `matchingSkills ∩ missingSkills = ∅`
    - Use `eris/eris` or `phpunit` data providers to generate random skill lists and resume strings
    - **Validates: Requirements 1.3, 1.12**

  - [x] 1.3 Expand stop-word list and deduplicate keywords in `extractJobKeywords()`
    - Add common filler words to the `$stopWords` array (e.g. `'using', 'develop', 'build', 'manage', 'ensure', 'provide', 'support', 'maintain'`)
    - After building the `$keywords` array, filter out any word that already appears in `$internship->required_skills` (lowercased) to avoid double-counting in keyword score
    - _Requirements: 1.2, 1.4_

- [x] 2. Improve experience relevance scoring in `scoreExperienceRelevance()`
  - [x] 2.1 Replace fixed 2000-char window with section-boundary-aware extraction
    - Instead of `substr($resumeLower, $expStart, 2000)`, find the next section header after the experience section start using the same header keywords used in `ResumePdfService::detectSections()` (education, projects, skills, certifications)
    - Extract the text between the experience header and the next section header as `$expBlock`
    - Fall back to the 2000-char window if no subsequent section header is found
    - _Requirements: 1.5_

  - [x] 2.2 Write property test for score bounds (P1 and P2)
    - **Property 1: Score Bounds** — `∀ resumeText, internship: 0 ≤ scoreResume(...).overall_score ≤ 100`
    - **Property 2: Score Component Weights Sum to 100%** — `overall = (skill×0.40) + (kw×0.25) + (exp×0.20) + (proj×0.10) + (comp×0.05)` always in [0,100]
    - Generate random component values in [0,100] and assert the weighted formula stays within bounds
    - **Validates: Requirements 1.8**

  - [x] 2.3 Write property test for tier consistency (P3)
    - **Property 3: Tier Consistency** — `scoreTier(score) = "high" ↔ score ≥ 70`, `"medium" ↔ 60 ≤ score < 70`, `"low" ↔ score < 60`
    - Generate random integer scores in [0,100] and assert `ResumeScore::scoreTier()` returns the correct tier
    - **Validates: Requirements 1.9, 1.10, 1.11**

- [x] 3. Add `validateRewriteOutput()` to `ResumeOptimizationService`
  - [x] 3.1 Implement the `validateRewriteOutput(string $text, Internship $internship): array` private method
    - Check that all four required section headers are present (professional summary, technical skills, experience, education) using `str_contains(strtolower($text), ...)`
    - Check word count with `str_word_count($text) > 650` (600-word limit + 8% buffer)
    - Check that the professional summary block (first 400 chars after the summary header) contains `strtolower($internship->title)`
    - Check for PDF binary artifacts using `preg_match('/%PDF-|endobj|xref|%%EOF/', $text)`
    - Return an array of issue strings (empty array = valid)
    - _Requirements: 9.1, 9.2, 9.6_

  - [x] 3.2 Wire `validateRewriteOutput()` into `rewriteResume()` after the OpenAI call
    - Call `validateRewriteOutput($rewrittenText, $internship)` immediately after `callOpenAIRewrite()` returns
    - If the issues array is non-empty, log a warning with `Log::warning('ResumeOptimization: Output validation failed', ['issues' => $issues])` and fall back to `ruleBasedRewrite($resumeText, $internship)`
    - _Requirements: 9.10, 15.2_

  - [x] 3.3 Write property test for sanitization idempotency (P7)
    - **Property 7: Sanitization Idempotency** — `sanitizeForStorage(sanitizeForStorage(text)) = sanitizeForStorage(text)`
    - Generate random strings including PDF artifact patterns and assert double-application equals single-application
    - Note: `sanitizeForStorage` must be made `public` or `protected` temporarily for testing, or tested via `rewriteResume()` output
    - **Validates: Requirements 14.8**

- [x] 4. Improve AI prompt in `callOpenAIRewrite()`
  - [x] 4.1 Add bullet transformation example to the prompt string
    - Insert the following block into the `$prompt` heredoc, inside the `━━━ STRICT RULES ━━━` section, after rule 3:
      ```
      EXAMPLE TRANSFORMATION:
      WEAK:   "Worked on APIs"
      STRONG: "Developed and optimized RESTful APIs using Laravel, reducing average response time and improving scalability for concurrent requests"
      ```
    - _Requirements: 12.6_

  - [x] 4.2 Add explicit word count instruction and skills grouping instruction to the prompt
    - Add `WORD COUNT: Output must be 500-600 words total. Count carefully.` as a numbered rule in the strict rules section
    - Add `SKILLS FORMAT: Group as → Languages: PHP, Python | Frameworks: Laravel, React | Tools: Git, Docker | Databases: MySQL, Redis` as a numbered rule
    - _Requirements: 3.2, 2.5, 12.4_

- [x] 5. Enhance `ruleBasedRewrite()` with bullet point improvement
  - [x] 5.1 Add `enhanceBulletPoints(array $bullets, array $jobSkills): array` private method
    - Define a `$weakVerbs` map: `'worked on' => 'Developed'`, `'helped with' => 'Contributed to'`, `'did some' => 'Implemented'`, `'was responsible' => 'Led'`, `'assisted in' => 'Supported'`, `'did' => 'Executed'`
    - For each bullet, check if `strtolower($bullet)` starts with any weak verb key using `str_starts_with()`; if so, replace the prefix with the strong verb
    - Return the enhanced bullets array
    - _Requirements: 5.1, 13.1_

  - [x] 5.2 Call `enhanceBulletPoints()` on experience bullets inside `ruleBasedRewrite()`
    - After parsing experience entries from the original resume text, pass each entry's bullets through `enhanceBulletPoints($entry['bullets'], $skills)` before assembling the output string
    - _Requirements: 5.1, 13.6_

  - [x] 5.3 Write property test for score non-decrease after rewrite (P6)
    - **Property 6: Score Non-Decrease After Rewrite**
    - When the rewritten text contains all job-required skills, `scoreResume(rewritten).skill_match ≥ scoreResume(original).skill_match`
    - Construct synthetic original (missing some skills) and rewritten (containing all skills) texts and assert the property
    - **Validates: Requirements 6.1, 6.2, 6.3**

- [x] 6. Checkpoint — verify scoring and rewrite pipeline
  - Ensure all tests pass, ask the user if questions arise.
  - At this point `ResumeOptimizationService` changes are complete: skill matching, experience scoring, output validation, prompt improvements, and rule-based bullet enhancement are all wired together.

- [x] 7. Improve `parseEntries()` in `ResumePdfService`
  - [x] 7.1 Rewrite `parseEntries()` to support pipe-separated header lines
    - Replace the existing `preg_split('/\n(?=[A-Z][^\n]{5,60}...)/m', $text)` block-split approach with a line-by-line loop
    - Detect pipe-separated header lines (`substr_count($line, '|') >= 1 && strlen($line) < 120 && !$isBullet`) and parse `$parts = array_map('trim', explode('|', $line))` into `org`, `title`, `date`, `location`
    - Detect short non-bullet capitalized lines as fallback org/title/date lines using `strlen($line) < 80 && preg_match('/[A-Z]/', $line[0])` and `preg_match('/\d{4}|present/i', $line)` for date detection
    - Accumulate bullet lines (starting with `•`, `-`, or `*`) into `$current['bullets']`
    - Push completed entries and return `array_slice($entries, 0, 5)`
    - _Requirements: 7.2, 11.1_

  - [x] 7.2 Write property test for PDF section completeness (P8)
    - **Property 8: PDF Section Completeness**
    - `∀ text containing "PROFESSIONAL SUMMARY": parseResumeText(text).summary ≠ ""`
    - Generate synthetic resume strings that include the "PROFESSIONAL SUMMARY" header followed by content and assert the parsed summary is non-empty
    - Note: `parseResumeText` must be made `public` or `protected` temporarily for testing
    - **Validates: Requirements 7.2, 7.3**

- [x] 8. Tighten CSS in `resources/views/resume/pdf-template.blade.php`
  - [x] 8.1 Reduce body font size and line height for single-page fit
    - Change `font-size` in the `body` rule from `9.5pt` to `9pt`
    - Change `line-height` in the `body` rule from `1.35` to `1.3`
    - _Requirements: 7.8, 7.10, 3.10_

  - [x] 8.2 Tighten section spacing and add page-break and overflow guards
    - Change `.section { margin-bottom: ... }` from `9px` to `7px`
    - Add `page-break-after: avoid;` to the `.section-title` rule
    - Add `overflow: hidden; max-height: 267mm;` to the `body` rule (A4 height minus top+bottom margins)
    - _Requirements: 7.8, 7.9, 3.1_

  - [x] 8.3 Write property test for rewrite preserves real data (P5)
    - **Property 5: Rewrite Preserves Real Data**
    - `∀ originalText, rewrittenText: extractedCompanies(originalText) ⊆ rewrittenText` (case-insensitive)
    - Generate synthetic original resumes with known company names and dates; run through `ruleBasedRewrite()` and assert all company names and years still appear in the output
    - **Validates: Requirements 4.4, 4.5, 9.5**

- [x] 9. Add PDF download link to chatbot rewrite completion in `public/js/chatbot.js`
  - [x] 9.1 Update the `displayMessage` call at the end of `_triggerRewrite()` to include a PDF download link
    - Locate the final `ShreeRamChatbot.displayMessage({...})` call inside the `.then(data => {...})` block of `_triggerRewrite()` (around line 1430)
    - Replace the single `links: [{ text: 'View Recommendations', url: '/recommendations', icon: 'fa-star' }]` with two links:
      1. `{ text: '⬇️ Download Optimized Resume (PDF)', url: '/resume-optimizer/download/' + job.id + '?version_id=' + d.version_id, icon: 'fa-file-pdf' }`
      2. `{ text: 'View Recommendations', url: '/recommendations', icon: 'fa-star' }`
    - Ensure `d.version_id` is referenced from the rewrite response data object `d`
    - _Requirements: 8.9, 8.10_

- [x] 10. Final checkpoint — ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
  - All four files are now modified: `ResumeOptimizationService.php`, `ResumePdfService.php`, `pdf-template.blade.php`, and `chatbot.js`.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- All changes are in-place upgrades — no new files, classes, migrations, or controller changes
- Property tests (P1–P8) map directly to the Correctness Properties section in `design.md`
- The `sanitizeForStorage` and `parseResumeText` methods may need visibility changed to `protected` to enable unit testing without reflection
- Checkpoints at tasks 6 and 10 ensure incremental validation before moving to the next layer
