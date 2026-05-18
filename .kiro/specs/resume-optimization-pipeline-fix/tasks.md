# Implementation Plan

## Overview

This task list implements the bugfix for 8 critical subsystem failures in the AI-powered resume optimization pipeline. The workflow follows the exploratory bugfix methodology: write property-based tests first (to confirm bugs exist), then fix each subsystem, then verify all tests pass. Tasks 1–2 are exploration and preservation tests written on unfixed code. Tasks 3–10 fix each subsystem independently. Task 11 integrates all fixes into the pipeline orchestrator. Task 12 is the final checkpoint.

## Task Dependency Graph

```json
{
  "waves": [
    { "wave": 1, "tasks": ["1", "2"] },
    { "wave": 2, "tasks": ["3.1", "4.1", "5.1", "6.1", "7.1", "8.1", "10.1"] },
    { "wave": 3, "tasks": ["9.1"] },
    { "wave": 4, "tasks": ["3.2", "3.3", "4.2", "4.3", "5.2", "5.3", "6.2", "6.3", "7.2", "7.3", "8.2", "8.3", "9.2", "9.3", "10.2", "10.3"] },
    { "wave": 5, "tasks": ["11.1"] },
    { "wave": 6, "tasks": ["11.2", "11.3"] },
    { "wave": 7, "tasks": ["12"] }
  ]
}
```

## Tasks

- [x] 1. Write bug condition exploration tests (Properties 1–8)
  - **Property 1: Bug Condition** - Semantic Skill Matching Failures
  - **Property 2: Bug Condition** - Identity Corruption During Optimization
  - **Property 3: Bug Condition** - Malformed AI Outputs
  - **Property 4: Bug Condition** - Fake ATS Score Engine
  - **Property 5: Bug Condition** - LaTeX/PDF Rendering Failures
  - **Property 6: Bug Condition** - Production API Failures
  - **Property 7: Bug Condition** - N8N Orchestration Instability
  - **Property 8: Bug Condition** - Weak Resume Over-Optimization
  - **CRITICAL**: Write ALL eight property-based tests BEFORE implementing any fix
  - **GOAL**: Surface counterexamples that demonstrate each bug exists
  - **Scoped PBT Approach**: Scope each property to the concrete failing case(s) described in the design
  - Create `tests/Unit/Resume/BugConditionExplorationTest.php`
  - **Property 1 details**: For any resume containing semantically equivalent skills (e.g., "debugging" → "problem solving"), assert `SemanticSkillMatcher::evaluate()` returns `matched = true`. Run on UNFIXED code — expect FAILURE (exact-match returns `matched = false`)
  - **Property 2 details**: For any Backend/Full Stack Engineer resume, assert `IdentityPreservationEngine::validate()` blocks identity corruption. Run on UNFIXED code — expect FAILURE (no hard gate enforcement)
  - **Property 3 details**: For any AI response containing ` ```json ` wrappers or malformed JSON, assert `AiOutputSanitizer::sanitizeAndValidate()` returns `valid = true` with clean data. Run on UNFIXED code — expect FAILURE (sanitization misses edge cases)
  - **Property 4 details**: For any original + optimized resume pair where content differs, assert `AtsScoreEngine::evaluateImprovement()` returns `after_score > before_score` (min 1% delta). Run on UNFIXED code — expect FAILURE (static 69% returned for both)
  - **Property 5 details**: For any structured JSON resume data, assert `LatexTemplateEngine::render()` produces LaTeX with no whitespace corruption. Run on UNFIXED code — expect FAILURE (AI-generated layout causes corruption)
  - **Property 6 details**: For any production API call, assert `AiProductionDiagnosticsService` logs request/response/timing/errors. Run on UNFIXED code — expect FAILURE (service does not exist)
  - **Property 7 details**: For any failing n8n webhook, assert `N8nOrchestrator` retries with exponential backoff (2s, 4s, 8s). Run on UNFIXED code — expect FAILURE (no retry logic)
  - **Property 8 details**: For any elite-tier resume, assert `ResumeQualityDetector` locks sections scoring > 80 from modification. Run on UNFIXED code — expect FAILURE (all sections treated equally)
  - Run all tests on UNFIXED code — **EXPECTED OUTCOME**: All 8 tests FAIL (confirms all bugs exist)
  - Document counterexamples found for each property
  - Mark task complete when all 8 tests are written, run, and failures are documented
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 1.11, 1.12, 1.13, 1.14, 1.15, 1.16, 1.17, 1.18, 1.19, 1.20, 1.21, 1.22, 1.23, 1.24_


- [x] 2. Write preservation property tests (Properties 9–12, BEFORE implementing fix)
  - **Property 9: Preservation** - Core Resume Processing
  - **Property 10: Preservation** - AI Provider Integration and PDF Generation
  - **Property 11: Preservation** - User Interface and Authentication
  - **Property 12: Preservation** - Database Operations
  - **IMPORTANT**: Follow observation-first methodology for all four preservation properties
  - Create `tests/Unit/Resume/PreservationPropertyTest.php`
  - **Observe (Property 9)**: Run valid PDF upload, job description matching, and metadata processing on UNFIXED code. Record exact outputs from `smalot/pdfparser`, storage disk writes, profile persistence, skill extraction, and keyword analysis
  - **Write (Property 9)**: For all valid resume PDF uploads and JD matching requests that do NOT involve the 8 buggy subsystems, assert system produces identical outputs to observed baseline (PDF parsing, S3/R2 storage, Eloquent profile save, skill/keyword extraction)
  - **Observe (Property 10)**: Run AI optimization and PDF generation requests on UNFIXED code. Record multi-provider fallback behavior, API key usage, LaTeXLite API calls, and PDF storage URLs
  - **Write (Property 10)**: For all AI optimization and PDF generation requests, assert multi-provider fallback (OpenAI/Anthropic/OpenRouter), API key config, LaTeXLite integration, and PDF storage remain unchanged
  - **Observe (Property 11)**: Run UI interactions, light mode optimization, and auth checks on UNFIXED code. Record display components, loading states, before/after views, RESUME_OPTIMIZER_LIGHT_MODE behavior, and auth enforcement
  - **Write (Property 11)**: For all user interactions, assert UI components, light mode, authentication, and authorization produce identical behavior to observed baseline
  - **Observe (Property 12)**: Run database operations on UNFIXED code. Record Eloquent ORM calls, optimization history inserts, and transaction wrappers
  - **Write (Property 12)**: For all database interactions, assert Eloquent ORM usage, optimization history tracking, and transaction wrappers remain unchanged
  - Verify all 4 preservation tests PASS on UNFIXED code
  - **EXPECTED OUTCOME**: All 4 tests PASS (confirms baseline behavior to preserve)
  - Mark task complete when all 4 tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12, 3.13, 3.14, 3.15, 3.16, 3.17, 3.18, 3.19, 3.20, 3.21, 3.22, 3.23, 3.24_


- [x] 3. Fix Subsystem 1: Semantic Skill Matching

  - [x] 3.1 Enhance SemanticSkillMatcher with expanded semantic clusters and consistent integration
    - File: `app/Services/Resume/SemanticSkillMatcher.php`
    - Expand semantic skill map (Lines 20–60): add domain-specific clusters for backend, frontend, fullstack, devops, data engineering roles
    - Add synonym variations for technical skills (e.g., "problem solving" ↔ "debugging", "system design" ↔ "architecture")
    - Support configurable custom clusters via `config/services.php`
    - Strengthen `inferFromEngineeringSignals()` (Lines 180–220): add compound skill support (e.g., "full stack development"), fuzzy matching for skill variations
    - Fix literal matching (Lines 240–260): handle special characters ("C++", "Node.js"), multi-word phrases, case-insensitive normalization
    - Ensure ALL skill comparison code paths use `SemanticSkillMatcher` consistently (no bypass in `WeaknessDetectionEngine` or `RecommendationController`)
    - _Bug_Condition: `input.resume.skills CONTAINS semanticallyEquivalentSkill(input.job.requiredSkills) AND skillMatchEngine.usesExactMatchOnly() == true`_
    - _Expected_Behavior: `evaluate()` returns `matched = true` with `via` = 'alias'|'cluster'|'inferred' and non-empty `evidence` array for all semantically equivalent skills_
    - _Preservation: Job description skill/keyword extraction (3.4–3.6) must remain unchanged_
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 3.2 Verify Property 1 exploration test now passes
    - **Property 1: Expected Behavior** - Semantic Skill Matching with Intelligence
    - **IMPORTANT**: Re-run the SAME test from task 1 — do NOT write a new test
    - Run `tests/Unit/Resume/BugConditionExplorationTest.php` Property 1 assertion
    - **EXPECTED OUTCOME**: Test PASSES (confirms semantic matching bug is fixed)
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 3.3 Verify preservation tests still pass after Subsystem 1 fix
    - **Property 9: Preservation** - Core Resume Processing (skill extraction paths)
    - Re-run `tests/Unit/Resume/PreservationPropertyTest.php` Properties 9 and 10
    - **EXPECTED OUTCOME**: Tests PASS (no regressions in skill extraction or JD matching)


- [x] 4. Fix Subsystem 2: Identity Preservation

  - [x] 4.1 Strengthen IdentityPreservationEngine with hard gate enforcement
    - File: `app/Services/Resume/IdentityPreservationEngine.php`
    - Enhance identity extraction (Lines 20–40): add seniority level detection (junior/mid/senior/lead/principal), domain specialization (backend/frontend/fullstack/devops/data), career trajectory patterns
    - Strengthen validation rules (Lines 60–120): add forbidden domain transitions (engineering → design), validate experience title consistency, check summary alignment with original identity
    - Implement hard gate: return validation failures as pipeline-blocking errors in `ResumeOptimizationService::rewriteResume()`
    - Trigger rule-based fallback with explicit identity preservation prompts when validation fails
    - Log identity corruption attempts for monitoring
    - Ensure `buildContext()` returns `optimization_mode`, `cross_domain`, and full `identity` object including `forbidden_domains`
    - _Bug_Condition: `input.resume.professionalIdentity IN ['Backend Engineer', 'Full Stack Engineer'] AND aiOptimization.identityConstraints == null`_
    - _Expected_Behavior: `validate()` returns `valid = false` with populated `violations` array when identity corruption is detected; pipeline blocks AI rewrite and triggers identity-constrained fallback_
    - _Preservation: Multi-provider AI fallback logic (3.7–3.9) must remain unchanged_
    - _Requirements: 2.4, 2.5, 2.6_

  - [x] 4.2 Verify Property 2 exploration test now passes
    - **Property 2: Expected Behavior** - Identity Preservation with 3-Layer Protection
    - **IMPORTANT**: Re-run the SAME test from task 1 — do NOT write a new test
    - Run `tests/Unit/Resume/BugConditionExplorationTest.php` Property 2 assertion
    - **EXPECTED OUTCOME**: Test PASSES (confirms identity corruption bug is fixed)
    - _Requirements: 2.4, 2.5, 2.6_

  - [x] 4.3 Verify preservation tests still pass after Subsystem 2 fix
    - **Property 10: Preservation** - AI Provider Integration (fallback logic must be unchanged)
    - Re-run `tests/Unit/Resume/PreservationPropertyTest.php` Properties 10 and 11
    - **EXPECTED OUTCOME**: Tests PASS (no regressions in AI provider integration or auth)


- [x] 5. Fix Subsystem 3: AI Output Sanitization

  - [x] 5.1 Harden AiOutputSanitizer with comprehensive artifact stripping and retry logic
    - File: `app/Services/Resume/AiOutputSanitizer.php`
    - Enhanced artifact stripping (Lines 80–100): handle nested code blocks (` ``` ``` `json\n...\n``` ``` `), Unicode BOM markers (`\xEF\xBB\xBF`), PDF artifacts (`%PDF-...%%EOF`), non-printable characters
    - Strict schema validation (Lines 40–70): validate required fields (`summary`, `skills`, `experience`/`projects`/`education`), check array structures (`experience[].bullets`, `projects[].bullets`), detect forbidden keys (`latex`, `formatting`, `layout`, `spacing`, `template`), validate data types and non-empty constraints
    - Return detailed `issues` array from `sanitizeAndValidate()` for corrective prompt generation
    - Support multiple sanitization passes for stubborn malformed responses
    - Integrate retry logic in `ResumeOptimizationService`: on validation failure, re-prompt AI with corrective instructions; fallback to alternative provider after 2 failed attempts
    - _Bug_Condition: `aiProvider.response CONTAINS '```json' OR aiProvider.response.isValidJSON() == false`_
    - _Expected_Behavior: `sanitizeAndValidate()` returns `valid = true` with clean `data` and `json` for any AI response containing markdown wrappers, nested code blocks, or malformed JSON_
    - _Preservation: Multi-provider AI fallback (3.7–3.9) and API key config (3.9) must remain unchanged_
    - _Requirements: 2.7, 2.8, 2.9_

  - [x] 5.2 Verify Property 3 exploration test now passes
    - **Property 3: Expected Behavior** - Strict JSON Validation with Sanitization
    - **IMPORTANT**: Re-run the SAME test from task 1 — do NOT write a new test
    - Run `tests/Unit/Resume/BugConditionExplorationTest.php` Property 3 assertion
    - **EXPECTED OUTCOME**: Test PASSES (confirms malformed AI output bug is fixed)
    - _Requirements: 2.7, 2.8, 2.9_

  - [x] 5.3 Verify preservation tests still pass after Subsystem 3 fix
    - **Property 10: Preservation** - AI Provider Integration (provider fallback must be unchanged)
    - Re-run `tests/Unit/Resume/PreservationPropertyTest.php` Property 10
    - **EXPECTED OUTCOME**: Test PASSES (no regressions in AI provider integration)


- [x] 6. Fix Subsystem 4: ATS Score Engine

  - [x] 6.1 Fix AtsScoreEngine to compute fresh dual-resume scores with realistic deltas
    - File: `app/Services/Resume/AtsScoreEngine.php`
    - Remove any score caching logic (Lines 50–150): always compute `before_score` and `after_score` from scratch using identical algorithms
    - Enforce minimum improvement threshold (Lines 60–90): minimum 1% delta required; cap maximum AI score delta at 8 points for average tier, 12 for elite tier; prevent unrealistic jumps (no 40-point increases)
    - Use `SemanticSkillMatcher` for all skill comparisons in semantic alignment scoring (Lines 400–450)
    - Compute semantic cluster coverage; weight intrinsic quality (40%) + JD alignment (60%)
    - Ensure `evaluateImprovement()` always returns both `before_score` and `after_score` with populated `before_breakdown` and `after_breakdown`
    - _Bug_Condition: `atsScoreEngine.scoreResume(input.resume.original) == atsScoreEngine.scoreResume(input.resume.optimized) AND input.resume.original != input.resume.optimized`_
    - _Expected_Behavior: `evaluateImprovement()` returns `after_score > before_score` with minimum 1% delta for any original/optimized resume pair where content differs_
    - _Preservation: UI before/after comparison display (3.13–3.15) and light mode score delta settings (3.16–3.18) must remain unchanged_
    - _Requirements: 2.10, 2.11, 2.12_

  - [x] 6.2 Verify Property 4 exploration test now passes
    - **Property 4: Expected Behavior** - Dual-Resume Semantic ATS Scoring
    - **IMPORTANT**: Re-run the SAME test from task 1 — do NOT write a new test
    - Run `tests/Unit/Resume/BugConditionExplorationTest.php` Property 4 assertion
    - **EXPECTED OUTCOME**: Test PASSES (confirms fake ATS score bug is fixed)
    - _Requirements: 2.10, 2.11, 2.12_

  - [x] 6.3 Verify preservation tests still pass after Subsystem 4 fix
    - **Property 11: Preservation** - User Interface (before/after score display must be unchanged)
    - Re-run `tests/Unit/Resume/PreservationPropertyTest.php` Properties 11 and 12
    - **EXPECTED OUTCOME**: Tests PASS (no regressions in UI display or light mode behavior)


- [x] 7. Fix Subsystem 5: LaTeX/PDF Rendering

  - [x] 7.1 Create deterministic LatexTemplateEngine with strict template boundaries
    - File: `app/Services/Resume/LatexTemplateEngine.php` (create if not exists)
    - Implement PHP-based LaTeX template rendering with fixed layout logic for all resume sections (header, summary, skills, experience, projects, education)
    - Use Blade-style templating for data injection from structured JSON only
    - Enforce strict template boundaries: accept ONLY structured JSON data as input; reject any LaTeX formatting, layout instructions, or spacing directives from AI responses
    - Detect and reject forbidden keys in input data (`latex`, `formatting`, `layout`, `spacing`, `template`)
    - Implement PDF quality validation: check for whitespace corruption, validate bullet point rendering, ensure consistent spacing and margins
    - Implement `render(array $resumeData): string` and `renderWithTemplate(array $resumeData, string $template = 'default'): string`
    - Update AI prompts in `ResumeOptimizationService` to request structured JSON data ONLY (no LaTeX output)
    - _Bug_Condition: `pdfGenerator.layoutSource == 'AI_GENERATED' OR pdfOutput.hasWhitespaceCorruption() == true`_
    - _Expected_Behavior: `render()` produces valid LaTeX with consistent spacing, proper bullet formatting, and no whitespace corruption for any structured JSON resume data_
    - _Preservation: PDF generation via LaTeXLite API (3.10–3.12) and PDF storage/download URL (3.12) must remain unchanged_
    - _Requirements: 2.13, 2.14, 2.15_

  - [x] 7.2 Verify Property 5 exploration test now passes
    - **Property 5: Expected Behavior** - Deterministic PHP LaTeX Rendering
    - **IMPORTANT**: Re-run the SAME test from task 1 — do NOT write a new test
    - Run `tests/Unit/Resume/BugConditionExplorationTest.php` Property 5 assertion
    - **EXPECTED OUTCOME**: Test PASSES (confirms LaTeX rendering bug is fixed)
    - _Requirements: 2.13, 2.14, 2.15_

  - [x] 7.3 Verify preservation tests still pass after Subsystem 5 fix
    - **Property 10: Preservation** - PDF Generation (LaTeXLite API integration must be unchanged)
    - Re-run `tests/Unit/Resume/PreservationPropertyTest.php` Property 10
    - **EXPECTED OUTCOME**: Test PASSES (no regressions in PDF generation or storage)


- [x] 8. Fix Subsystem 6: Production API Diagnostics

  - [x] 8.1 Create AiProductionDiagnosticsService with enterprise-grade logging
    - File: `app/Services/Resume/AiProductionDiagnosticsService.php` (new file)
    - Implement `logApiCall(string $provider, string $endpoint, array $request, array $response, float $duration): void` — log request/response payloads, headers, timing, status codes
    - Implement `logApiFailure(string $provider, string $endpoint, \Throwable $exception, array $context): void` — capture production-specific failures (SSL verification, timeouts, permissions, config caching)
    - Implement `generateDiagnosticReport(string $correlationId): array` — return structured report with `correlation_id`, `api_calls`, `failures`, sanitized `environment` state, and actionable `recommendations`
    - Use correlation IDs for request tracing across all API calls in a single optimization request
    - Log to dedicated diagnostic channel; support log aggregation for monitoring
    - Sanitize all logged data: mask API keys, tokens, and secrets; log only key names not values
    - Integrate into `AiProviderGateway`, `N8nOrchestrator`, and `ResumePdfService` for all API calls
    - _Bug_Condition: `environment == 'production' AND (openAiApi.fails() OR openRouterApi.fails() OR latexApi.fails() OR n8nWebhook.fails()) AND diagnosticLogging.enabled == false`_
    - _Expected_Behavior: All production API calls log request/response/timing/errors; failures capture environment state (SSL settings, timeout values, config state) and provide actionable diagnostics_
    - _Preservation: API key configuration from environment (3.9), LaTeXLite API integration (3.10–3.11), and multi-provider fallback (3.7–3.8) must remain unchanged_
    - _Requirements: 2.16, 2.17, 2.18_

  - [x] 8.2 Verify Property 6 exploration test now passes
    - **Property 6: Expected Behavior** - Enterprise-Grade API Logging and Error Handling
    - **IMPORTANT**: Re-run the SAME test from task 1 — do NOT write a new test
    - Run `tests/Unit/Resume/BugConditionExplorationTest.php` Property 6 assertion
    - **EXPECTED OUTCOME**: Test PASSES (confirms production API diagnostics bug is fixed)
    - _Requirements: 2.16, 2.17, 2.18_

  - [x] 8.3 Verify preservation tests still pass after Subsystem 6 fix
    - **Property 10: Preservation** - AI Provider Integration (API key config and fallback must be unchanged)
    - Re-run `tests/Unit/Resume/PreservationPropertyTest.php` Properties 9 and 10
    - **EXPECTED OUTCOME**: Tests PASS (no regressions in API integration or provider fallback)


- [x] 9. Fix Subsystem 7: N8N Orchestration

  - [x] 9.1 Implement exponential backoff retry logic in N8nOrchestrator
    - File: `app/Services/Resume/N8nOrchestrator.php`
    - Implement exponential backoff retry: 3 attempts with delays of 2s, 4s, 8s between attempts
    - Update `dispatch()` method: use `$maxAttempts = 3` with exponential delay calculation (`2^attempt` seconds)
    - Queue failed requests for retry with full context (payload, headers, correlation ID, failure reason)
    - Implement structured error responses: return `['success' => false, 'attempts' => [...], 'last_error' => ..., 'diagnostic' => ...]` on final failure
    - Log each attempt with full context via `AiProductionDiagnosticsService::logApiFailure()`
    - Implement fallback mechanism: on orchestration failure, log structured diagnostic and continue pipeline with degraded mode (skip n8n-dependent steps)
    - _Bug_Condition: `n8nWebhook.request.fails() AND retryLogic.enabled == false`_
    - _Expected_Behavior: On any webhook failure, `N8nOrchestrator` retries exactly 3 times with 2s/4s/8s delays, logs each attempt with full context, and returns structured error response with diagnostic info on final failure_
    - _Preservation: Synchronous operations not requiring N8N orchestration (3.1–3.6, 3.22–3.24) must remain completely unaffected_
    - _Requirements: 2.19, 2.20, 2.21_

  - [x] 9.2 Verify Property 7 exploration test now passes
    - **Property 7: Expected Behavior** - Fault-Tolerant N8N Orchestration
    - **IMPORTANT**: Re-run the SAME test from task 1 — do NOT write a new test
    - Run `tests/Unit/Resume/BugConditionExplorationTest.php` Property 7 assertion
    - **EXPECTED OUTCOME**: Test PASSES (confirms N8N orchestration instability bug is fixed)
    - _Requirements: 2.19, 2.20, 2.21_

  - [x] 9.3 Verify preservation tests still pass after Subsystem 7 fix
    - **Property 9: Preservation** - Core Resume Processing (non-N8N paths must be unchanged)
    - Re-run `tests/Unit/Resume/PreservationPropertyTest.php` Properties 9 and 12
    - **EXPECTED OUTCOME**: Tests PASS (no regressions in core processing or database operations)


- [x] 10. Fix Subsystem 8: Resume Quality Detection

  - [x] 10.1 Implement section-level quality scoring and locking in ResumeQualityDetector
    - File: `app/Services/Resume/ResumeQualityDetector.php`
    - Implement section-level quality scoring: score each resume section (summary, skills, experience bullets, projects, education) using keyword density, action verb usage, and quantifiable achievement detection
    - Lock strong sections (score > 80) from AI modification: add `locked_sections` array to `detect()` output
    - Only optimize weak sections (score < 60): pass `locked_sections` and `weak_sections` to AI prompt constraints
    - Classify resume quality tier: `elite` (overall > 85), `strong` (70–85), `average` (50–70), `weak` (< 50)
    - Update `ResumeOptimizationService` to respect locked sections: exclude locked sections from AI rewrite prompt; include them verbatim in optimized output
    - Ensure AI prompts include explicit section-level preservation instructions for locked sections
    - _Bug_Condition: `input.resume.qualityTier == 'elite' AND optimizationEngine.preservesStrongSections() == false`_
    - _Expected_Behavior: For any elite-tier resume, `detect()` returns `locked_sections` for all sections scoring > 80; optimization pipeline preserves locked sections verbatim and only rewrites sections scoring < 60_
    - _Preservation: Average/weak resumes that benefit from aggressive optimization (3.16–3.18) must continue to receive full optimization; light mode behavior must remain unchanged_
    - _Requirements: 2.22, 2.23, 2.24_

  - [x] 10.2 Verify Property 8 exploration test now passes
    - **Property 8: Expected Behavior** - Intelligent Resume Quality Detection
    - **IMPORTANT**: Re-run the SAME test from task 1 — do NOT write a new test
    - Run `tests/Unit/Resume/BugConditionExplorationTest.php` Property 8 assertion
    - **EXPECTED OUTCOME**: Test PASSES (confirms elite resume over-optimization bug is fixed)
    - _Requirements: 2.22, 2.23, 2.24_

  - [x] 10.3 Verify preservation tests still pass after Subsystem 8 fix
    - **Property 11: Preservation** - User Interface and Authentication (light mode must be unchanged)
    - Re-run `tests/Unit/Resume/PreservationPropertyTest.php` Properties 11 and 12
    - **EXPECTED OUTCOME**: Tests PASS (no regressions in light mode or UI behavior)


- [x] 11. Integrate all subsystem fixes into ResumeOptimizationService pipeline

  - [x] 11.1 Wire all 8 fixed subsystem engines into the pipeline orchestrator
    - File: `app/Services/ResumeOptimizationService.php`
    - Stage 3 (Quality Detection): call `ResumeQualityDetector::detect()` early in pipeline; pass `quality_tier` and `locked_sections` to all downstream stages
    - Stage 4 (Weakness Detection): ensure `WeaknessDetectionEngine` uses `SemanticSkillMatcher` for all gap analysis (no exact-match bypass)
    - Stage 5 (ATS Scoring): call `AtsScoreEngine::evaluateImprovement()` with both original and optimized resumes; enforce minimum 1% delta
    - Stage 6 (AI Rewrite): pass `identity_context` from `IdentityPreservationEngine::buildContext()` to AI prompt; enforce `locked_sections` in prompt; validate output with `AiOutputSanitizer::sanitizeAndValidate()`; enforce identity hard gate with `IdentityPreservationEngine::validate()`
    - Stage 7 (Persistence): use `LatexTemplateEngine::render()` for all LaTeX generation; pass structured JSON only
    - Attach `AiProductionDiagnosticsService` correlation ID to all API calls in the pipeline
    - Use `N8nOrchestrator` with exponential backoff for all webhook dispatches
    - _Bug_Condition: All 8 isBugCondition clauses from design_
    - _Expected_Behavior: All 8 expectedBehavior properties from design_
    - _Preservation: All 24 regression prevention requirements (3.1–3.24)_
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 2.10, 2.11, 2.12, 2.13, 2.14, 2.15, 2.16, 2.17, 2.18, 2.19, 2.20, 2.21, 2.22, 2.23, 2.24_

  - [x] 11.2 Verify all 8 bug condition exploration tests now pass
    - **Property 1: Expected Behavior** - Semantic Skill Matching with Intelligence
    - **Property 2: Expected Behavior** - Identity Preservation with 3-Layer Protection
    - **Property 3: Expected Behavior** - Strict JSON Validation with Sanitization
    - **Property 4: Expected Behavior** - Dual-Resume Semantic ATS Scoring
    - **Property 5: Expected Behavior** - Deterministic PHP LaTeX Rendering
    - **Property 6: Expected Behavior** - Enterprise-Grade API Logging and Error Handling
    - **Property 7: Expected Behavior** - Fault-Tolerant N8N Orchestration
    - **Property 8: Expected Behavior** - Intelligent Resume Quality Detection
    - **IMPORTANT**: Re-run the SAME tests from task 1 — do NOT write new tests
    - Run full `tests/Unit/Resume/BugConditionExplorationTest.php` suite
    - **EXPECTED OUTCOME**: All 8 tests PASS (confirms all bugs are fixed)

  - [x] 11.3 Verify all 4 preservation property tests still pass
    - **Property 9: Preservation** - Core Resume Processing
    - **Property 10: Preservation** - AI Provider Integration and PDF Generation
    - **Property 11: Preservation** - User Interface and Authentication
    - **Property 12: Preservation** - Database Operations
    - **IMPORTANT**: Re-run the SAME tests from task 2 — do NOT write new tests
    - Run full `tests/Unit/Resume/PreservationPropertyTest.php` suite
    - **EXPECTED OUTCOME**: All 4 tests PASS (confirms no regressions introduced)


- [x] 12. Checkpoint - Ensure all tests pass
  - Run the complete test suite: `php artisan test tests/Unit/Resume/`
  - Confirm all 8 bug condition exploration tests pass (Properties 1–8)
  - Confirm all 4 preservation property tests pass (Properties 9–12)
  - Run any existing integration tests to confirm no regressions in authentication, database operations, or UI components
  - Verify no new test failures were introduced by the pipeline integration in task 11
  - Ensure all tests pass, ask the user if questions arise

## Notes

- All property-based tests (Properties 1–12) must be written BEFORE any fix is implemented (tasks 1 and 2)
- Bug condition tests (Properties 1–8) are expected to FAIL on unfixed code — this is correct and confirms the bugs exist
- Preservation tests (Properties 9–12) are expected to PASS on unfixed code — this establishes the regression baseline
- Each subsystem fix (tasks 3–10) is independently verifiable; fix and verify one subsystem at a time
- Task 11 wires all fixes together in `ResumeOptimizationService` — run the full test suite after this step
- `AiProductionDiagnosticsService` (task 8) must be created before `N8nOrchestrator` retry logging (task 9) since task 9 depends on it
- `LatexTemplateEngine` (task 7) may need to be created from scratch if it does not exist
- All API keys, tokens, and secrets must be masked in diagnostic logs — never log secret values
- Test files: `tests/Unit/Resume/BugConditionExplorationTest.php` and `tests/Unit/Resume/PreservationPropertyTest.php`
- Run tests with: `php artisan test tests/Unit/Resume/`
