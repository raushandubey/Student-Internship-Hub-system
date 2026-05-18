# Resume Optimization Pipeline Fix - Technical Design

## Overview

This design document specifies the complete architectural reconstruction of the AI-powered resume optimization pipeline. The system processes candidate resumes through 8 interconnected subsystems: semantic skill matching, identity preservation, AI output validation, ATS scoring, LaTeX PDF rendering, production API reliability, N8N orchestration, and resume quality detection. The current implementation exhibits systematic failures across all subsystems, requiring targeted fixes while preserving core functionality.

**Fix Scope:** This is a bugfix, not a feature addition. All changes target defect correction while maintaining backward compatibility with existing resume processing workflows, authentication, database operations, and UI components.

**Architecture Pattern:** The system follows a pipeline orchestration pattern where `ResumeOptimizationService` coordinates specialized subsystem engines. Each engine is independently testable and follows single-responsibility principles.

## Glossary

- **Bug_Condition (C)**: Input conditions that trigger defective behavior across the 8 subsystems
- **Property (P)**: Expected correct behavior for buggy inputs after fixes are applied
- **Preservation**: Existing functionality that must remain unchanged (24 regression prevention rules)
- **Pipeline Orchestrator**: `ResumeOptimizationService` - master coordinator for all subsystem engines
- **Subsystem Engine**: Specialized service class handling one architectural concern (e.g., `SemanticSkillMatcher`, `IdentityPreservationEngine`)
- **ATS Score**: Applicant Tracking System compatibility score (0-100) measuring resume-job alignment
- **Semantic Matching**: Synonym-aware skill comparison using evidence clusters and contextual equivalents
- **Identity Corruption**: Transformation of professional identity during optimization (e.g., Backend Engineer → UI Designer)
- **Quality Tier**: Resume classification (elite/strong/average/weak) determining optimization aggressiveness
- **Locked Section**: High-quality resume section protected from AI modification
- **LaTeX Template**: Deterministic PHP-based PDF layout engine (no AI-generated formatting)
- **N8N Webhook**: External workflow orchestration endpoint with retry logic
- **Production API**: OpenAI/OpenRouter/LaTeXLite/N8N endpoints in live environment
- **Correlation ID**: Request tracing identifier for diagnostic logging

## Bug Details

### Bug Condition

The resume optimization pipeline fails when processing resumes through 8 critical subsystems. Each subsystem exhibits specific defect patterns that compound to create systematic pipeline failures.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type ResumeOptimizationRequest
  OUTPUT: boolean
  
  RETURN (
    // Subsystem 1: Semantic Skill Matching
    input.resume.skills CONTAINS semanticallyEquivalentSkill(input.job.requiredSkills)
    AND skillMatchEngine.usesExactMatchOnly() == true
  ) OR (
    // Subsystem 2: Identity Preservation
    input.resume.professionalIdentity IN ['Backend Engineer', 'Full Stack Engineer']
    AND aiOptimization.identityConstraints == null
  ) OR (
    // Subsystem 3: AI Output Validation
    aiProvider.response CONTAINS '```json' OR aiProvider.response.isValidJSON() == false
  ) OR (
    // Subsystem 4: ATS Scoring
    atsScoreEngine.scoreResume(input.resume.original) == atsScoreEngine.scoreResume(input.resume.optimized)
    AND input.resume.original != input.resume.optimized
  ) OR (
    // Subsystem 5: LaTeX/PDF Rendering
    pdfGenerator.layoutSource == 'AI_GENERATED'
    OR pdfOutput.hasWhitespaceCorruption() == true
  ) OR (
    // Subsystem 6: Production API
    environment == 'production'
    AND (openAiApi.fails() OR openRouterApi.fails() OR latexApi.fails() OR n8nWebhook.fails())
    AND diagnosticLogging.enabled == false
  ) OR (
    // Subsystem 7: N8N Orchestration
    n8nWebhook.request.fails()
    AND retryLogic.enabled == false
  ) OR (
    // Subsystem 8: Resume Quality Detection
    input.resume.qualityTier == 'elite'
    AND optimizationEngine.preservesStrongSections() == false
  )
END FUNCTION
```

### Examples

**Example 1: Semantic Skill Matching Failure**
- **Input**: Resume contains "debugging", "REST APIs", "architecture". Job requires "problem solving", "backend engineering", "system design".
- **Current Behavior**: System marks "problem solving", "backend engineering", "system design" as MISSING (false negatives)
- **Expected Behavior**: System recognizes semantic equivalents and marks all as MATCHED

**Example 2: Identity Corruption**
- **Input**: Backend Engineer resume with Laravel/PHP/MySQL experience. Job description for Frontend React role.
- **Current Behavior**: AI rewrites summary to "UI Designer Intern seeking frontend opportunities"
- **Expected Behavior**: System preserves "Backend Engineer" identity and only adds relevant transferable skills

**Example 3: Malformed AI Output**
- **Input**: AI provider returns: ` ```json\n{"summary": "...", "skills": [...]}` ` `
- **Current Behavior**: JSON parser fails with `AI_OUTPUT_VALIDATION_FAILED` error
- **Expected Behavior**: System strips markdown wrappers, validates schema, and processes clean JSON

**Example 4: Fake ATS Score**
- **Input**: Original resume scores 69%. Optimized resume adds 5 required skills and 3 quantified achievements.
- **Current Behavior**: Optimized resume still shows 69% (identical score)
- **Expected Behavior**: Optimized resume shows 78-82% (realistic improvement delta)


**Example 5: LaTeX/PDF Rendering Corruption**
- **Input**: Optimized resume JSON with 5 experience bullets
- **Current Behavior**: PDF contains giant whitespace blocks, broken bullet formatting, malformed spacing
- **Expected Behavior**: PDF renders with consistent spacing, proper bullets, professional layout

**Example 6: Production API Failure**
- **Input**: Resume optimization request in production environment
- **Current Behavior**: OpenAI API call fails silently, no diagnostic logs, pipeline crashes
- **Expected Behavior**: System logs request/response details, captures error context, provides actionable diagnostics

**Example 7: N8N Orchestration Failure**
- **Input**: N8N webhook request times out after 5 seconds
- **Current Behavior**: Request fails permanently, no retry, no fallback
- **Expected Behavior**: System retries with exponential backoff (2s, 4s, 8s), logs failure context

**Example 8: Elite Resume Over-Optimization**
- **Input**: Elite resume with architecture-level language, quantified achievements, strong technical depth
- **Current Behavior**: AI aggressively rewrites all sections, degrades quality
- **Expected Behavior**: System detects elite tier, locks strong sections, only optimizes weak areas

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Resume PDF parsing using smalot/pdfparser (Requirement 3.1)
- Resume file storage in configured disk (local/S3/R2) (Requirement 3.2)
- Candidate profile metadata persistence (Requirement 3.3)
- Job description skill/keyword extraction (Requirements 3.4-3.6)
- Multi-provider AI fallback logic (OpenAI/Anthropic/OpenRouter) (Requirements 3.7-3.9)
- PDF generation via LaTeXLite API (Requirements 3.10-3.12)
- User interface display components (Requirements 3.13-3.15)
- Light mode optimization behavior (Requirements 3.16-3.18)
- Authentication and authorization enforcement (Requirements 3.19-3.21)
- Laravel Eloquent ORM database operations (Requirements 3.22-3.24)

**Scope:**
All inputs that do NOT involve the 8 buggy subsystems should be completely unaffected by this fix. This includes:
- Valid resumes that parse correctly without semantic skill matching
- Resumes that don't require identity preservation (non-cross-domain applications)
- AI responses that are already valid JSON without markdown wrappers
- Score calculations for resumes that haven't been optimized
- PDF generation requests using existing templates
- API calls in local development environments
- Synchronous operations not requiring N8N orchestration
- Average/weak resumes that benefit from aggressive optimization

## Hypothesized Root Cause

Based on the bug description and code analysis, the most likely issues are:

### 1. Semantic Skill Matching: Primitive Exact-Match Algorithm

**Root Cause**: `SemanticSkillMatcher` exists but is not fully integrated into the scoring pipeline. The system uses literal keyword matching in some code paths instead of semantic inference.

**Evidence**:
- `AtsScoreEngine::scoreSkillMatch()` calls `SemanticSkillMatcher::scoreSkills()` correctly
- However, weakness detection and recommendation generation may bypass semantic matching
- The semantic skill map is comprehensive but may not cover all domain-specific synonyms

**Fix Strategy**: Ensure all skill comparison code paths use `SemanticSkillMatcher` consistently. Expand semantic clusters for common engineering domains.

### 2. Identity Preservation: Missing Validation Layer

**Root Cause**: `IdentityPreservationEngine` exists and extracts identity correctly, but validation is not enforced as a hard gate in the pipeline. AI optimization can proceed even when identity corruption is detected.

**Evidence**:
- `ResumeOptimizationService::rewriteResume()` calls `identityEngine->validate()` at line ~450
- If validation fails, system triggers rule-based fallback
- However, the fallback may not have sufficient identity constraints

**Fix Strategy**: Strengthen identity validation as a hard gate. Enhance rule-based fallback with explicit identity preservation prompts.

### 3. AI Output Validation: Incomplete Sanitization

**Root Cause**: `AiOutputSanitizer` strips markdown wrappers but may not handle all edge cases (nested code blocks, mixed formatting, Unicode artifacts).

**Evidence**:
- `AiOutputSanitizer::stripArtifacts()` uses regex to remove ` ```json` ` wrappers
- However, some AI providers return nested structures or malformed JSON
- Schema validation exists but may not catch all structural issues

**Fix Strategy**: Enhance sanitization regex patterns. Add retry logic with corrective prompts when validation fails.

### 4. ATS Scoring: Static Score Caching

**Root Cause**: The scoring engine may be using cached scores or not properly comparing original vs optimized resumes. The `evaluateImprovement()` method exists but may have logic errors.

**Evidence**:
- `AtsScoreEngine::evaluateImprovement()` computes both before/after scores
- However, the method may return cached values or fail to detect improvements
- AI-based scoring has fallback to rule-based scoring, which may be too conservative

**Fix Strategy**: Ensure `evaluateImprovement()` always computes fresh scores for both versions. Validate that score deltas are realistic (minimum 1% improvement threshold).


### 5. LaTeX/PDF Rendering: AI-Generated Layout Logic

**Root Cause**: The system may be allowing AI providers to generate LaTeX formatting instructions directly, leading to layout corruption. Deterministic PHP templates should handle all layout logic.

**Evidence**:
- `LatexTemplateEngine` should exist but may not be enforcing strict template boundaries
- AI prompts may be requesting LaTeX output instead of pure JSON data
- PDF validation may not be checking for whitespace corruption

**Fix Strategy**: Ensure AI providers return ONLY structured JSON data (no LaTeX). Implement deterministic PHP-based LaTeX template rendering with fixed layout logic.

### 6. Production API: Missing Diagnostic Logging

**Root Cause**: Production API failures occur due to environment-specific issues (SSL verification, timeouts, config caching, permissions) but lack comprehensive logging to diagnose root causes.

**Evidence**:
- `AiProviderGateway` and `N8nOrchestrator` have basic logging
- However, production-specific diagnostics (environment variables, SSL settings, timeout values) are not captured
- Error messages are generic and don't provide actionable context

**Fix Strategy**: Implement `AiProductionDiagnosticsService` to capture detailed error context. Log request/response payloads, headers, timing, and environment state.

### 7. N8N Orchestration: No Retry Logic

**Root Cause**: `N8nOrchestrator` exists but may not implement exponential backoff retry logic. Webhook failures are permanent with no recovery mechanism.

**Evidence**:
- `N8nOrchestrator::dispatch()` has a `$maxAttempts` parameter (default 2)
- However, retry delays may not use exponential backoff
- Failure logging may not capture full context for diagnostics

**Fix Strategy**: Implement exponential backoff retry logic (2s, 4s, 8s delays). Enhance failure logging with structured error responses.

### 8. Resume Quality Detection: No Section-Level Protection

**Root Cause**: `ResumeQualityDetector` classifies resume tiers but may not implement section-level locking. Elite resumes are treated the same as weak resumes during optimization.

**Evidence**:
- `ResumeQualityDetector::detect()` returns quality tier and section scores
- However, the optimization engine may not respect locked sections
- AI prompts may not include section-level preservation instructions

**Fix Strategy**: Implement section-level quality scoring. Lock strong sections (score > 80) from AI modification. Only optimize weak sections (score < 60).

## Correctness Properties

Property 1: Bug Condition - Semantic Skill Matching with Intelligence

_For any_ resume containing semantically equivalent skills (synonyms, related concepts, contextual equivalents) and job description with required skills, the fixed skill matching engine SHALL recognize semantic relationships using evidence clusters, synonym dictionaries, and contextual inference, and SHALL only flag skills as "missing" when no semantically equivalent skill exists in the resume.

**Validates: Requirements 2.1, 2.2, 2.3**

Property 2: Bug Condition - Identity Preservation with 3-Layer Protection

_For any_ resume submitted for optimization where the candidate's professional identity (job title, career domain, seniority level) differs from the job description role, the fixed system SHALL detect and preserve the candidate's identity through (1) quality detection, (2) identity engine validation, and (3) weak section optimization only, preventing role corruption during AI rewriting.

**Validates: Requirements 2.4, 2.5, 2.6**

Property 3: Bug Condition - Strict JSON Validation with Sanitization

_For any_ AI provider response containing markdown wrappers, code blocks, or malformed JSON structures, the fixed system SHALL sanitize responses by stripping artifacts, validate against strict schema, implement retry logic with corrective prompts, and fallback to alternative AI providers when validation fails.

**Validates: Requirements 2.7, 2.8, 2.9**

Property 4: Bug Condition - Dual-Resume Semantic ATS Scoring

_For any_ resume optimization request, the fixed ATS scoring engine SHALL compute separate scores for original and optimized versions using the same semantic analysis algorithm, detect keyword density and skill coverage improvements, and display realistic before/after scores with measurable improvement deltas (minimum 1% improvement threshold).

**Validates: Requirements 2.10, 2.11, 2.12**

Property 5: Bug Condition - Deterministic PHP LaTeX Rendering

_For any_ optimized resume requiring PDF generation, the fixed system SHALL use deterministic PHP-based LaTeX template rendering with fixed layout logic, receive structured JSON data only from AI (no LaTeX formatting), and produce professional ATS-compliant PDFs with consistent spacing, proper bullet formatting, and clean layout.

**Validates: Requirements 2.13, 2.14, 2.15**

Property 6: Bug Condition - Enterprise-Grade API Logging and Error Handling

_For any_ API request in production environments (OpenAI, OpenRouter, LaTeX API, n8n webhooks), the fixed system SHALL successfully execute calls with comprehensive logging of request/response payloads, headers, timing, error details, and SHALL capture detailed error context (environment variables, config state, SSL settings, timeout values) to provide actionable diagnostics when failures occur.

**Validates: Requirements 2.16, 2.17, 2.18**

Property 7: Bug Condition - Fault-Tolerant N8N Orchestration

_For any_ n8n webhook orchestration request, the fixed system SHALL implement exponential backoff retry logic (3 attempts with 2s, 4s, 8s delays), queue failed requests for retry with full context logging, and provide structured error responses with diagnostic information and fallback mechanisms when orchestration errors occur.

**Validates: Requirements 2.19, 2.20, 2.21**

Property 8: Bug Condition - Intelligent Resume Quality Detection

_For any_ high-quality resume submitted for optimization, the fixed system SHALL detect strong sections using quality metrics (keyword density, action verb usage, quantifiable achievements), apply section-level quality scoring, preserve sections scoring above 80 from modification, and focus optimization only on weak sections scoring below 60.

**Validates: Requirements 2.22, 2.23, 2.24**

Property 9: Preservation - Core Resume Processing

_For any_ valid resume PDF upload, job description matching, or resume metadata processing, the fixed system SHALL produce exactly the same behavior as the original system, preserving PDF parsing (smalot/pdfparser), file storage (local/S3/R2), profile persistence, skill extraction, and keyword analysis.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**

Property 10: Preservation - AI Provider Integration and PDF Generation

_For any_ AI optimization request or PDF generation request, the fixed system SHALL produce exactly the same behavior as the original system, preserving multi-provider fallback logic (OpenAI/Anthropic/OpenRouter), API key configuration, LaTeXLite API integration, and PDF storage mechanisms.

**Validates: Requirements 3.7, 3.8, 3.9, 3.10, 3.11, 3.12**

Property 11: Preservation - User Interface and Authentication

_For any_ user interaction with resume optimization features, the fixed system SHALL produce exactly the same behavior as the original system, preserving UI display components, loading states, before/after comparison views, light mode behavior, authentication requirements, and authorization enforcement.

**Validates: Requirements 3.13, 3.14, 3.15, 3.16, 3.17, 3.18, 3.19, 3.20, 3.21**

Property 12: Preservation - Database Operations

_For any_ database interaction during resume optimization, the fixed system SHALL produce exactly the same behavior as the original system, preserving Laravel Eloquent ORM usage, optimization history tracking, and transaction wrappers for data consistency.

**Validates: Requirements 3.22, 3.23, 3.24**


## Fix Implementation

### Architecture Overview

The resume optimization pipeline follows a **Pipeline Orchestration Pattern** with specialized subsystem engines:

```
┌─────────────────────────────────────────────────────────────────┐
│                  ResumeOptimizationService                      │
│                   (Pipeline Orchestrator)                        │
└────────────────────────┬────────────────────────────────────────┘
                         │
         ┌───────────────┼───────────────┐
         │               │               │
         ▼               ▼               ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│  Stage 1:   │  │  Stage 2:   │  │  Stage 3:   │
│   Parsing   │  │    JD       │  │  Quality    │
│             │  │  Analysis   │  │  Detection  │
└─────────────┘  └─────────────┘  └─────────────┘
         │               │               │
         └───────────────┼───────────────┘
                         │
         ┌───────────────┼───────────────┐
         │               │               │
         ▼               ▼               ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│  Stage 4:   │  │  Stage 5:   │  │  Stage 6:   │
│  Weakness   │  │    ATS      │  │     AI      │
│  Detection  │  │   Scoring   │  │  Rewrite    │
└─────────────┘  └─────────────┘  └─────────────┘
         │               │               │
         └───────────────┼───────────────┘
                         │
                         ▼
                ┌─────────────┐
                │  Stage 7:   │
                │ Persistence │
                └─────────────┘
```

**Subsystem Engines (8 Critical Components):**

1. **SemanticSkillMatcher** - Synonym-aware skill comparison with evidence clusters
2. **IdentityPreservationEngine** - Professional identity extraction and validation
3. **AiOutputSanitizer** - JSON sanitization and schema validation
4. **AtsScoreEngine** - Dual-resume semantic scoring with intrinsic quality + JD alignment
5. **LatexTemplateEngine** - Deterministic PHP-based PDF layout rendering
6. **AiProductionDiagnosticsService** - Enterprise-grade API logging and error capture
7. **N8nOrchestrator** - Fault-tolerant webhook client with exponential backoff
8. **ResumeQualityDetector** - Section-level quality scoring and locking

### Component Designs

#### 1. SemanticSkillMatcher Enhancement

**File**: `app/Services/Resume/SemanticSkillMatcher.php`

**Current State**: Implements semantic matching with evidence clusters but may not be consistently used across all code paths.

**Required Changes**:

1. **Expand Semantic Skill Map** (Lines 20-60)
   - Add domain-specific clusters for common engineering roles
   - Include more synonym variations for technical skills
   - Support configurable custom clusters via `config/services.php`

2. **Enhance Inference Logic** (Lines 180-220)
   - Strengthen `inferFromEngineeringSignals()` with more signal patterns
   - Add support for compound skills (e.g., "full stack development")
   - Implement fuzzy matching for skill variations

3. **Improve Literal Matching** (Lines 240-260)
   - Handle special characters in skill names (e.g., "C++", "Node.js")
   - Support multi-word skill phrases with flexible word boundaries
   - Add case-insensitive matching with normalization

**API Contract**:
```php
// Input: Required skill, resume text, JD analysis
public function evaluate(string $requiredSkill, string $resumeText, array $jdAnalysis = []): array

// Output: Match result with evidence
return [
    'matched' => bool,           // Whether skill is satisfied
    'via' => string,             // Match method: 'literal', 'alias', 'cluster', 'inferred', 'none'
    'evidence' => array<string>  // Skills/terms that satisfied the requirement
];
```

**Integration Points**:
- Called by `AtsScoreEngine::scoreSkillMatch()` for all skill comparisons
- Used by `WeaknessDetectionEngine` for gap analysis
- Referenced by `RecommendationController` for skill suggestions

#### 2. IdentityPreservationEngine Strengthening

**File**: `app/Services/Resume/IdentityPreservationEngine.php`

**Current State**: Extracts identity and validates corruption but may not enforce as hard gate.

**Required Changes**:

1. **Enhance Identity Extraction** (Lines 20-40)
   - Add seniority level detection (junior/mid/senior/lead/principal)
   - Extract domain specialization (backend/frontend/fullstack/devops/data)
   - Identify career trajectory patterns

2. **Strengthen Validation Rules** (Lines 60-120)
   - Add forbidden domain transitions (e.g., engineering → design)
   - Validate experience title consistency
   - Check summary alignment with original identity

3. **Implement Hard Gate Enforcement**
   - Return validation failures as pipeline-blocking errors
   - Trigger rule-based fallback with identity constraints
   - Log identity corruption attempts for monitoring

**API Contract**:
```php
// Input: Parsed resume, JD analysis
public function buildContext(array $parsedResume, array $jdAnalysis): array

// Output: Identity context with optimization mode
return [
    'optimization_mode' => string,  // 'standard' or 'identity_preserving'
    'cross_domain' => bool,         // Whether JD role differs from resume domain
    'identity' => [
        'candidate_type' => string,      // 'backend engineer', 'frontend engineer', etc.
        'role_category' => string,       // 'backend', 'frontend', 'fullstack', etc.
        'seniority' => string,           // 'junior', 'mid', 'senior', 'lead', 'principal'
        'forbidden_domains' => array,    // Domains that should not appear in optimized resume
    ]
];

// Input: Identity context, optimized resume, original resume
public function validate(array $identity, array $optimized, array $original): array

// Output: Validation result with violations
return [
    'valid' => bool,                // Whether identity is preserved
    'violations' => array<string>   // List of identity corruption issues
];
```

**Integration Points**:
- Called by `ResumeOptimizationService::rewriteResume()` before AI optimization
- Validation enforced as hard gate after AI rewrite
- Identity context passed to `AiRewriteEngine` for prompt constraints


#### 3. AiOutputSanitizer Robustness

**File**: `app/Services/Resume/AiOutputSanitizer.php`

**Current State**: Strips basic markdown wrappers but may miss edge cases.

**Required Changes**:

1. **Enhanced Artifact Stripping** (Lines 80-100)
   - Handle nested code blocks: ` ``` ``` `json\n...\n``` ``` `
   - Remove Unicode BOM markers: `\xEF\xBB\xBF`
   - Strip PDF artifacts: `%PDF-...%%EOF`
   - Clean non-printable characters

2. **Strict Schema Validation** (Lines 40-70)
   - Validate required fields: `summary`, `skills`, `experience`/`projects`/`education`
   - Check array structures: `experience[].bullets`, `projects[].bullets`
   - Detect forbidden keys: `latex`, `formatting`, `layout`, `spacing`, `template`
   - Validate data types and non-empty constraints

3. **Retry Logic Integration**
   - Return validation issues for corrective prompts
   - Support multiple sanitization passes
   - Provide detailed error messages for debugging

**API Contract**:
```php
// Input: Raw AI response content
public function sanitizeAndValidate(string $rawContent): array

// Output: Sanitization result with validation status
return [
    'valid' => bool,              // Whether output passes validation
    'data' => ?array,             // Decoded JSON data (if valid)
    'issues' => array<string>,    // List of validation issues
    'json' => ?string             // Clean JSON string (if valid)
];
```

**Integration Points**:
- Called by `ResumeOptimizationService::rewriteResume()` after AI response
- Validation failures trigger retry with corrective prompts
- Clean JSON passed to identity validation and quality gate

#### 4. AtsScoreEngine Dual-Resume Scoring

**File**: `app/Services/Resume/AtsScoreEngine.php`

**Current State**: Implements intrinsic quality + JD alignment scoring but may have caching issues.

**Required Changes**:

1. **Ensure Fresh Score Computation** (Lines 50-150)
   - Remove any score caching logic
   - Always compute both `before_score` and `after_score` from scratch
   - Validate that scores are computed using identical algorithms

2. **Realistic Score Delta Validation** (Lines 60-90)
   - Enforce minimum improvement threshold (1% minimum)
   - Cap maximum AI score delta (8 points for average, 12 for elite)
   - Prevent unrealistic jumps (no 40-point increases)

3. **Semantic Alignment Scoring** (Lines 400-450)
   - Use `SemanticSkillMatcher` for all skill comparisons
   - Compute semantic cluster coverage
   - Weight intrinsic quality (40%) + JD alignment (60%)

**API Contract**:
```php
// Input: Original resume, optimized resume, JD analysis, quality tier
public function evaluateImprovement(
    array $originalResume,
    array $optimizedResume,
    array $jdAnalysis,
    string $qualityTier = 'average'
): array

// Output: Score comparison with improvements
return [
    'before_score' => int,           // Original resume score (0-100)
    'after_score' => int,            // Optimized resume score (0-100)
    'after_breakdown' => array,      // Detailed score breakdown
    'before_breakdown' => array,     // Original score breakdown
    'improvements' => array<string>, // List of improvement descriptions
    'ai_used' => bool,               // Whether AI scoring was used
    'fallback_used' => bool,         // Whether rule-based fallback was used
    'rule_delta' => int,             // Score delta from rule-based scoring
    'attempts' => array              // AI provider attempts log
];
```

**Integration Points**:
- Called by `ResumeOptimizationService::rewriteResume()` after AI optimization
- Score delta used by `ResumeOptimizationQualityGate` for acceptance criteria
- Before/after scores displayed in UI comparison view

#### 5. LatexTemplateEngine Deterministic Rendering

**File**: `app/Services/Resume/LatexTemplateEngine.php` (may need creation)

**Current State**: May not exist or may allow AI-generated LaTeX.

**Required Changes**:

1. **Create Deterministic Template Engine**
   - Implement PHP-based LaTeX template rendering
   - Define fixed layout logic for all resume sections
   - Use Blade-style templating for data injection

2. **Strict Template Boundaries**
   - Accept ONLY structured JSON data as input
   - Reject any LaTeX formatting from AI responses
   - Enforce consistent spacing, bullet formatting, margins

3. **PDF Quality Validation**
   - Check for whitespace corruption
   - Validate bullet point rendering
   - Ensure professional layout consistency

**API Contract**:
```php
// Input: Structured resume data (JSON)
public function render(array $resumeData): string

// Output: LaTeX source code
return string; // Complete LaTeX document ready for PDF compilation

// Input: Resume data, template name
public function renderWithTemplate(array $resumeData, string $template = 'default'): string

// Output: LaTeX source code
return string;
```

**Integration Points**:
- Called by `ResumePdfService` for PDF generation
- Receives structured JSON from `ResumeOptimizationService`
- LaTeX output sent to LaTeXLite API for compilation

#### 6. AiProductionDiagnosticsService Creation

**File**: `app/Services/Resume/AiProductionDiagnosticsService.php` (new file)

**Current State**: Does not exist.

**Required Changes**:

1. **Create Diagnostic Service**
   - Capture request/response payloads for all API calls
   - Log headers, timing, status codes
   - Record environment variables (sanitized, no secrets)
   - Track SSL verification settings, timeout values

2. **Error Context Capture**
   - Detect production-specific failures (SSL, timeouts, permissions)
   - Provide actionable error messages
   - Generate diagnostic reports for support

3. **Structured Logging**
   - Use correlation IDs for request tracing
   - Log to dedicated diagnostic channel
   - Support log aggregation for monitoring

**API Contract**:
```php
// Input: API call context
public function logApiCall(string $provider, string $endpoint, array $request, array $response, float $duration): void

// Input: API failure context
public function logApiFailure(string $provider, string $endpoint, \Throwable $exception, array $context): void

// Input: Correlation ID
public function generateDiagnosticReport(string $correlationId): array

// Output: Diagnostic report
return [
    'correlation_id' => string,
    'api_calls' => array,        // All API calls in this request
    'failures' => array,         // Failed API calls with context
    'environment' => array,      // Sanitized environment state
    'recommendations' => array   // Actionable fix suggestions
];
```

**Integration Points**:
- Called by `AiProviderGateway` for all AI API calls
- Called by `N8nOrchestrator` for webhook requests
- Called by `ResumePdfService` for LaTeX API calls
- Diagnostic reports accessible via admin panel

