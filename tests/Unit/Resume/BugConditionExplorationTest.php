<?php

namespace Tests\Unit\Resume;

use App\Services\Resume\AiOutputSanitizer;
use App\Services\Resume\AiProductionDiagnosticsService;
use App\Services\Resume\AtsScoreEngine;
use App\Services\Resume\IdentityPreservationEngine;
use App\Services\Resume\LatexTemplateEngine;
use App\Services\Resume\N8nOrchestrator;
use App\Services\Resume\ResumeQualityDetector;
use App\Services\Resume\SemanticSkillMatcher;
use Tests\TestCase;

/**
 * Bug Condition Exploration Tests — Properties 1–8
 *
 * CRITICAL: These tests are EXPECTED TO FAIL on unfixed code.
 * Failure confirms each bug exists. DO NOT fix the tests when they fail.
 *
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8,
 *              1.9, 1.10, 1.11, 1.12, 1.13, 1.14, 1.15, 1.16, 1.17,
 *              1.18, 1.19, 1.20, 1.21, 1.22, 1.23, 1.24**
 */
class BugConditionExplorationTest extends TestCase
{

    // ─────────────────────────────────────────────────────────────────────────
    // Property 1: Bug Condition — Semantic Skill Matching Failures
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 1: The WeaknessDetectionEngine::detect() method SHALL use
     * SemanticSkillMatcher for ALL skill comparisons — including the missing_skills
     * output. When a resume contains semantically equivalent skills, those skills
     * SHALL NOT appear in the missing_skills list.
     *
     * The bug: WeaknessDetectionEngine calls skillMatcher()->scoreSkills() but the
     * SemanticSkillMatcher's DEFAULT_SEMANTIC_SKILL_MAP does NOT cover all domain
     * synonyms. Specifically, "analytical thinking" and "software craftsmanship"
     * are NOT in any cluster, so they are always reported as missing even when
     * strong engineering evidence exists.
     *
     * EXPECTED TO FAIL on unfixed code — missing cluster coverage causes false negatives.
     *
     * **Validates: Requirements 1.1, 1.2, 1.3**
     */
    public function test_property_1_semantic_skill_matching_no_false_negatives_for_domain_synonyms(): void
    {
        $matcher = new SemanticSkillMatcher();
        SemanticSkillMatcher::resetCache();

        // Resume with strong engineering signals but using different terminology
        $resumeText = 'Senior software engineer with expertise in clean code principles, '
            . 'SOLID design patterns, test-driven development, code review practices, '
            . 'and software craftsmanship. Analytical approach to debugging complex systems. '
            . 'Proficient in refactoring legacy codebases and improving code quality metrics.';

        // These are domain-standard recruiter terms that SHOULD match the resume above
        // but are NOT in the DEFAULT_SEMANTIC_SKILL_MAP — this is the bug
        $recruiterTermsNotInMap = [
            'analytical thinking',    // resume has "analytical approach" — not in any cluster
            'software craftsmanship', // resume has "software craftsmanship" — not in any cluster
            'code quality',           // resume has "code quality metrics" — not in any cluster
        ];

        $failures = [];
        foreach ($recruiterTermsNotInMap as $term) {
            $result = $matcher->evaluate($term, $resumeText);
            if (!$result['matched']) {
                $failures[] = sprintf(
                    'COUNTEREXAMPLE: required="%s" not matched despite resume containing equivalent evidence. '
                    . 'via=%s, evidence=%s. '
                    . 'This term is missing from DEFAULT_SEMANTIC_SKILL_MAP — a false negative.',
                    $term,
                    $result['via'],
                    json_encode($result['evidence'])
                );
            }
        }

        $this->assertEmpty(
            $failures,
            "Property 1 FAILED — SemanticSkillMatcher has incomplete cluster coverage:\n"
            . implode("\n", $failures)
            . "\n\nFix: Expand DEFAULT_SEMANTIC_SKILL_MAP to cover these domain synonyms."
        );
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Property 2: Bug Condition — Identity Corruption During Optimization
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 2: IdentityPreservationEngine::buildContext() SHALL return an
     * identity object containing a 'seniority' key as specified in the design doc.
     *
     * The bug: buildContext() calls ResumeDomainClassifier::classify() which returns
     * 'experience_level' (not 'seniority'). The design API contract specifies the
     * identity object must have a 'seniority' key. This missing key means downstream
     * components (AI prompt builder, validation rules) cannot access seniority level,
     * weakening the identity preservation constraints.
     *
     * EXPECTED TO FAIL on unfixed code — identity object uses 'experience_level'
     * instead of the required 'seniority' key.
     *
     * **Validates: Requirements 1.4, 1.5, 1.6**
     */
    public function test_property_2_identity_preservation_context_includes_seniority_key(): void
    {
        $engine = new IdentityPreservationEngine();

        $backendResume = [
            'raw_text'   => 'Senior Backend Engineer with PHP Laravel MySQL REST API experience. '
                . 'Led team of 5 engineers. Architected microservices.',
            'summary'    => 'Senior Backend Engineer with 5 years of Laravel experience.',
            'skills'     => ['PHP', 'Laravel', 'MySQL', 'REST API', 'Docker'],
            'experience' => [
                [
                    'title'   => 'Senior Backend Engineer',
                    'org'     => 'Corp',
                    'bullets' => ['Led team of 5 engineers', 'Architected microservices'],
                    'date'    => '2019-2024',
                ],
                [
                    'title'   => 'Backend Developer',
                    'org'     => 'Startup',
                    'bullets' => ['Built REST APIs'],
                    'date'    => '2017-2019',
                ],
                [
                    'title'   => 'Junior Developer',
                    'org'     => 'Agency',
                    'bullets' => ['Developed features'],
                    'date'    => '2015-2017',
                ],
            ],
            'education'  => [],
            'projects'   => [],
        ];

        $jdAnalysis = [
            'role_category' => 'backend',
            'job_title'     => 'Senior Backend Engineer',
        ];

        $context = $engine->buildContext($backendResume, $jdAnalysis);
        $identity = $context['identity'];

        // Design API contract requires 'seniority' key in identity object
        // Current code returns 'experience_level' from ResumeDomainClassifier::classify()
        $this->assertArrayHasKey(
            'seniority',
            $identity,
            'COUNTEREXAMPLE: buildContext() identity object is missing the "seniority" key. '
            . 'The design API contract specifies identity must include: '
            . 'candidate_type, role_category, seniority, forbidden_domains. '
            . 'Current code returns "experience_level" from ResumeDomainClassifier instead. '
            . 'Identity keys returned: ' . json_encode(array_keys($identity)) . '. '
            . 'This confirms the identity preservation API contract bug — seniority-based '
            . 'constraints cannot be enforced without this key.'
        );
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Property 3: Bug Condition — Malformed AI Outputs
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 3: AiOutputSanitizer::sanitizeAndValidate() SHALL preserve the
     * content of JSON string values when stripping markdown wrappers. Backtick
     * characters inside JSON string values (e.g., in code examples within bullets)
     * SHALL be preserved, not stripped.
     *
     * The bug: stripArtifacts() calls str_replace('`', '', $text) which removes ALL
     * backtick characters from the entire raw content — including backticks that are
     * inside JSON string values. This corrupts the content of bullet points that
     * reference code (e.g., "Built REST API using `Laravel` framework").
     *
     * EXPECTED TO FAIL on unfixed code — backtick stripping corrupts JSON string values.
     *
     * **Validates: Requirements 1.7, 1.8, 1.9**
     */
    public function test_property_3_ai_output_sanitizer_preserves_content_inside_json_strings(): void
    {
        $sanitizer = new AiOutputSanitizer();

        // AI response with ```json wrapper AND backtick characters inside JSON string values
        $bulletWithBackticks = 'Built REST API using `Laravel` framework with `MySQL` database';
        $jsonPayload = json_encode([
            'summary'    => 'Backend Engineer with `Laravel` and `PHP` expertise.',
            'skills'     => ['PHP', 'Laravel', 'MySQL'],
            'experience' => [
                [
                    'title'   => 'Backend Developer',
                    'org'     => 'Corp',
                    'bullets' => [$bulletWithBackticks],
                ],
            ],
        ]);

        // Wrap in markdown code block
        $rawInput = "```json\n" . $jsonPayload . "\n```";

        $result = $sanitizer->sanitizeAndValidate($rawInput);

        // The sanitizer should be valid
        $this->assertTrue(
            $result['valid'],
            'sanitizeAndValidate() returned valid=false. issues=' . json_encode($result['issues'])
        );

        // The backtick characters inside JSON string values should be preserved
        // Current code: str_replace('`', '', $text) strips ALL backticks including inside strings
        $actualBullet = $result['data']['experience'][0]['bullets'][0] ?? '';

        $this->assertStringContainsString(
            '`Laravel`',
            $actualBullet,
            'COUNTEREXAMPLE: Backtick characters inside JSON string values were stripped. '
            . 'Expected bullet to contain "`Laravel`" but got: "' . $actualBullet . '". '
            . 'The stripArtifacts() method uses str_replace(\'`\', \'\', $text) which removes '
            . 'ALL backtick characters from the raw content, including those inside JSON string '
            . 'values. This corrupts bullet points that reference code with backtick notation. '
            . 'This confirms the AI output sanitization content corruption bug.'
        );
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Property 4: Bug Condition — Fake ATS Score Engine
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 4: AtsScoreEngine::evaluateImprovement() SHALL enforce a minimum
     * 1% improvement delta. When the AI gateway returns identical before/after scores
     * AND the rule-based scores also show no improvement (identical resumes),
     * the engine SHALL still return after_score > before_score by applying a
     * minimum delta floor.
     *
     * The bug: When original == optimized (no actual changes), both rule-based scores
     * are identical (delta = 0). The AI advisory is accepted when abs(0 - 0) <= 8.
     * The engine returns after_score == before_score with no enforcement of the
     * minimum 1% delta requirement from the design spec.
     *
     * EXPECTED TO FAIL on unfixed code — no minimum delta enforcement when
     * both rule-based and AI scores show zero improvement.
     *
     * **Validates: Requirements 1.10, 1.11, 1.12**
     */
    public function test_property_4_ats_score_engine_enforces_minimum_delta_for_identical_resumes(): void
    {
        // Mock AI gateway returning identical scores (the static 69% bug)
        $mockGateway = $this->createMock(\App\Services\Resume\AiProviderGateway::class);
        $mockGateway->method('complete')->willReturn([
            'success'  => true,
            'content'  => json_encode([
                'before_score' => 69,
                'after_score'  => 69,  // Bug: AI returns identical scores
                'improvements' => ['Enhanced technical phrasing.'],
            ]),
            'provider' => 'openai',
            'attempts' => [],
        ]);

        $engine = new AtsScoreEngine(aiGateway: $mockGateway, skillMatcher: null);

        $jdAnalysis = [
            'role_category'   => 'backend',
            'required_skills' => ['PHP', 'Laravel', 'MySQL'],
            'keywords'        => ['backend', 'api'],
            'semantic_clusters' => [],
        ];

        // IDENTICAL original and optimized resumes — rule-based delta will be 0
        $resume = [
            'raw_text'   => 'Backend Engineer with PHP, Laravel, MySQL experience. '
                . 'Built REST APIs. Optimized database queries. Deployed Docker containers.',
            'summary'    => 'Backend Engineer with 3 years of Laravel experience.',
            'skills'     => ['PHP', 'Laravel', 'MySQL', 'Docker'],
            'experience' => [
                [
                    'title'   => 'Backend Engineer',
                    'org'     => 'Corp',
                    'bullets' => [
                        'Built REST APIs with Laravel',
                        'Optimized MySQL queries',
                        'Deployed Docker containers',
                    ],
                    'date' => '2021-2024',
                ],
            ],
            'education'  => [['school' => 'University', 'degree' => 'CS', 'year' => '2021']],
            'projects'   => [],
        ];

        // Pass the SAME resume as both original and optimized
        $result = $engine->evaluateImprovement($resume, $resume, $jdAnalysis, 'average');

        $beforeScore = $result['before_score'];
        $afterScore  = $result['after_score'];

        // The design requires minimum 1% delta — but when resumes are identical,
        // the engine returns identical scores (no minimum delta enforcement)
        $this->assertGreaterThan(
            $beforeScore,
            $afterScore,
            sprintf(
                'COUNTEREXAMPLE: after_score (%d) equals before_score (%d) when identical '
                . 'resumes are passed as original and optimized. '
                . 'The AI gateway returned 69/69 (identical scores), the rule-based delta is 0, '
                . 'and the engine accepted the AI advisory without enforcing the minimum 1%% delta. '
                . 'The design requires after_score > before_score with minimum 1%% improvement. '
                . 'This confirms the fake ATS score bug — no minimum delta floor is enforced.',
                $afterScore,
                $beforeScore
            )
        );
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Property 5: Bug Condition — LaTeX/PDF Rendering Failures
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 5: For any structured JSON resume data, LatexTemplateEngine::render()
     * SHALL produce LaTeX with no whitespace corruption (no double blank lines,
     * no consecutive \vspace commands, no empty \cvPoint entries).
     *
     * EXPECTED TO FAIL on unfixed code — AI-generated layout causes corruption.
     *
     * **Validates: Requirements 1.13, 1.14, 1.15**
     */
    public function test_property_5_latex_template_engine_produces_no_whitespace_corruption(): void
    {
        $engine = new LatexTemplateEngine();

        // Concrete failing case: structured JSON resume data
        $resumeData = [
            'name'       => 'John Doe',
            'email'      => 'john@example.com',
            'phone'      => '+1-555-0100',
            'headline'   => 'Backend Engineer',
            'targetRole' => 'Senior Backend Engineer',
            'sections'   => [
                'summary'    => 'Backend Engineer with 3 years of Laravel experience building scalable REST APIs.',
                'skills'     => ['PHP', 'Laravel', 'MySQL', 'Docker', 'REST API'],
                'experience' => [
                    [
                        'title'   => 'Backend Engineer',
                        'org'     => 'TechCorp',
                        'date'    => '2022–2024',
                        'bullets' => [
                            'Architected REST API serving 10k concurrent users',
                            'Optimized MySQL queries reducing latency by 40%',
                        ],
                    ],
                ],
                'projects'   => [
                    [
                        'title'   => 'API Gateway',
                        'tech'    => 'Laravel, Redis',
                        'bullets' => ['Built rate-limited API gateway'],
                    ],
                ],
                'education'  => [
                    ['school' => 'State University', 'degree' => 'B.Sc. Computer Science', 'year' => '2022'],
                ],
            ],
        ];

        // LatexTemplateEngine::render() does not exist yet — this tests the bug condition
        // that the method is missing (class only has generatePdf/generatePdfResult).
        $this->assertTrue(
            method_exists($engine, 'render'),
            'COUNTEREXAMPLE: LatexTemplateEngine::render() method does not exist. '
            . 'The engine only exposes generatePdf() which calls the external LaTeXLite API. '
            . 'A deterministic PHP render() method is required to produce LaTeX locally '
            . 'without whitespace corruption from AI-generated layout. '
            . 'This confirms the LaTeX rendering bug (no deterministic PHP template renderer).'
        );

        // If render() exists, verify no whitespace corruption
        if (method_exists($engine, 'render')) {
            /** @var string $latex */
            $latex = $engine->render($resumeData);

            $this->assertIsString($latex, 'render() must return a string');

            // No consecutive blank lines (whitespace corruption indicator)
            $this->assertDoesNotMatchRegularExpression(
                '/\n{3,}/',
                $latex,
                'COUNTEREXAMPLE: LaTeX output contains 3+ consecutive newlines (whitespace corruption).'
            );

            // No empty \cvPoint{} entries
            $this->assertDoesNotMatchRegularExpression(
                '/\\\\cvPoint\{\s*\}/',
                $latex,
                'COUNTEREXAMPLE: LaTeX output contains empty \\cvPoint{} entries (bullet corruption).'
            );

            // Must contain \begin{document}
            $this->assertStringContainsString(
                '\begin{document}',
                $latex,
                'COUNTEREXAMPLE: LaTeX output missing \\begin{document}.'
            );
        }
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Property 6: Bug Condition — Production API Failures
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 6: AiProductionDiagnosticsService SHALL expose logApiCall(),
     * logApiFailure(), and generateDiagnosticReport() methods for enterprise-grade
     * API logging and error capture.
     *
     * EXPECTED TO FAIL on unfixed code — service does not have these logging methods.
     *
     * **Validates: Requirements 1.16, 1.17, 1.18**
     */
    public function test_property_6_ai_production_diagnostics_service_has_logging_methods(): void
    {
        // Verify the three required logging methods exist on the service
        $requiredMethods = [
            'logApiCall'              => 'Logs request/response payloads, headers, timing, status codes',
            'logApiFailure'           => 'Captures production-specific failures with full context',
            'generateDiagnosticReport' => 'Returns structured report with correlation_id, api_calls, failures, environment state',
        ];

        $missingMethods = [];
        foreach ($requiredMethods as $method => $description) {
            if (!method_exists(AiProductionDiagnosticsService::class, $method)) {
                $missingMethods[] = sprintf(
                    'COUNTEREXAMPLE: AiProductionDiagnosticsService::%s() does not exist. '
                    . 'Purpose: %s',
                    $method,
                    $description
                );
            }
        }

        $this->assertEmpty(
            $missingMethods,
            "Property 6 FAILED — required diagnostic logging methods are missing:\n"
            . implode("\n", $missingMethods)
            . "\n\nThe current AiProductionDiagnosticsService only has run() for diagnostics "
            . "but lacks the per-call logging API needed for enterprise-grade production monitoring. "
            . "This confirms the production API diagnostics bug."
        );
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Property 7: Bug Condition — N8N Orchestration Instability
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 7: For any failing n8n webhook, N8nOrchestrator SHALL retry with
     * exponential backoff delays of 2s, 4s, 8s (3 attempts total).
     *
     * EXPECTED TO FAIL on unfixed code — current implementation uses 500ms * attempt
     * (linear, not exponential) and only 2 attempts by default.
     *
     * **Validates: Requirements 1.19, 1.20, 1.21**
     */
    public function test_property_7_n8n_orchestrator_retries_with_exponential_backoff(): void
    {
        // Inspect the N8nOrchestrator dispatch() source to verify exponential backoff
        $reflection = new \ReflectionClass(N8nOrchestrator::class);
        $method     = $reflection->getMethod('dispatch');
        $source     = file_get_contents($reflection->getFileName());

        // Check 1: Default maxAttempts should be 3 (not 2)
        $defaultParam = $method->getParameters()[2] ?? null; // $maxAttempts parameter
        $defaultValue = $defaultParam?->getDefaultValue();

        $this->assertSame(
            3,
            $defaultValue,
            sprintf(
                'COUNTEREXAMPLE: N8nOrchestrator::dispatch() default $maxAttempts is %s, expected 3. '
                . 'The design requires exactly 3 retry attempts with 2s/4s/8s delays. '
                . 'This confirms the N8N retry logic bug.',
                var_export($defaultValue, true)
            )
        );

        // Check 2: Source must contain exponential backoff pattern (2^attempt seconds)
        // Current code uses: usleep((int) (500_000 * $attempts)) — linear, not exponential
        // Fixed code should use: sleep(2 ** $attempt) or equivalent 2s/4s/8s pattern
        $hasExponentialBackoff = (bool) preg_match(
            '/2\s*\*\*\s*\$attempt|pow\s*\(\s*2|sleep\s*\(\s*[248]\s*\)|2000|4000|8000/i',
            $source
        );

        $this->assertTrue(
            $hasExponentialBackoff,
            'COUNTEREXAMPLE: N8nOrchestrator::dispatch() does not implement exponential backoff. '
            . 'Current implementation uses linear delay: usleep(500_000 * $attempts). '
            . 'Required: 2s delay after attempt 1, 4s after attempt 2, 8s after attempt 3. '
            . 'This confirms the N8N orchestration instability bug.'
        );
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Property 8: Bug Condition — Weak Resume Over-Optimization
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 8: ResumeQualityDetector::ruleBasedDetect() SHALL lock sections
     * scoring > 80 (strictly greater than 80). The design spec requires locking
     * at score > 80, but the current implementation uses >= 85 as the threshold.
     *
     * The bug: In ruleBasedDetect(), the locked_sections filter is:
     *   array_keys(array_filter($sectionScores, fn($s) => $s >= 85))
     * But the design requires:
     *   array_keys(array_filter($sectionScores, fn($s) => $s > 80))
     *
     * This means sections scoring 81, 82, 83, or 84 are NOT locked even though
     * they are strong sections that should be protected from AI modification.
     *
     * EXPECTED TO FAIL on unfixed code — threshold is >= 85 instead of > 80.
     *
     * **Validates: Requirements 1.22, 1.23, 1.24**
     */
    public function test_property_8_resume_quality_detector_locks_sections_scoring_above_80(): void
    {
        $detector = new ResumeQualityDetector();

        // Craft a resume where the experience section scores in the 81-84 range.
        // Experience scoring: base=15, 2 roles=+15, hasDate=+10, hasOrg=+5 = 45
        // Strong verbs: 5/6 bullets start with strong verbs (83% ratio) = +20
        // Metrics: 3 quantified bullets = +20
        // Tech depth: 4+ tech terms = +15
        // Total: 45 + 20 + 20 + 15 = 100 → capped at 100
        // Need to reduce: 1 role only = +8 instead of +15 → 45-7=38
        // 38 + 20 + 20 + 15 = 93 → still too high
        // Use 3/5 strong verbs (60% ratio) = +12 instead of +20
        // 38 + 12 + 20 + 15 = 85 → still at threshold
        // Use 2/5 strong verbs (40% ratio) = +5 instead of +12
        // 38 + 5 + 20 + 15 = 78 → below 80
        // Use 3/5 strong verbs (60%) = +12, 2 metrics = +10, 3 tech = +8
        // 38 + 12 + 10 + 8 = 68 → too low
        // Try: base=15, 1 role=+8, hasDate=+10, hasOrg=+5 = 38
        // 4/5 strong verbs (80%) = +20, 2 metrics = +10, 4 tech = +15
        // 38 + 20 + 10 + 15 = 83 → in the 81-84 gap!
        $resume = [
            'raw_text'   => 'Backend engineer with PHP Laravel MySQL Docker REST API JWT.',
            'summary'    => 'Backend Engineer.',
            'skills'     => ['PHP', 'Laravel', 'MySQL'],
            'experience' => [
                [
                    'title'   => 'Backend Engineer',
                    'org'     => 'TechCorp',
                    'date'    => '2021-2024',
                    'bullets' => [
                        // 4 out of 5 start with strong verbs (80% ratio → +20)
                        'Architected REST API serving 10k concurrent users',  // strong verb + metric
                        'Optimized MySQL queries reducing latency by 40%',    // strong verb + metric
                        'Implemented JWT authentication with Laravel',         // strong verb + tech
                        'Deployed Docker containers for microservice architecture', // strong verb + tech
                        'Worked on database schema design',                    // weak verb (no strong verb)
                    ],
                ],
                // No second role — only 1 role = +8 (not +15)
            ],
            'projects'   => [],
            'education'  => [['school' => 'University', 'degree' => 'CS', 'year' => '2022']],
        ];

        $result   = $detector->ruleBasedDetect($resume);
        $scores   = $result['section_scores'];
        $locked   = $result['locked_sections'];

        $experienceScore = $scores['experience'] ?? 0;

        // Verify the experience section scored in the 81-84 range
        // If not, report the actual score for diagnosis
        if ($experienceScore <= 80 || $experienceScore >= 85) {
            // The test fixture didn't produce the expected score range
            // Still assert the core property: sections > 80 should be locked
            $sectionsAbove80 = array_keys(array_filter($scores, fn ($s) => $s > 80));
            $unlockedAbove80 = array_diff($sectionsAbove80, $locked);

            if (!empty($unlockedAbove80)) {
                $this->assertEmpty(
                    $unlockedAbove80,
                    sprintf(
                        'COUNTEREXAMPLE: Sections scoring > 80 are NOT locked: %s. '
                        . 'Section scores: %s. Locked sections: %s. '
                        . 'The design requires locking at score > 80, but current code uses >= 85.',
                        json_encode(array_values($unlockedAbove80)),
                        json_encode($scores),
                        json_encode($locked)
                    )
                );
            } else {
                // All sections > 80 are locked — but check if any score in 81-84 gap
                $inGap = array_keys(array_filter($scores, fn ($s) => $s > 80 && $s < 85));
                if (empty($inGap)) {
                    // No section in the gap — the threshold bug cannot be demonstrated
                    // with this fixture. Assert the threshold directly via reflection.
                    $reflection = new \ReflectionClass(ResumeQualityDetector::class);
                    $source = file_get_contents($reflection->getFileName());

                    // The bug: code uses >= 85, design requires > 80
                    $usesCorrectThreshold = (bool) preg_match(
                        '/array_filter\s*\(\s*\$sectionScores\s*,\s*fn\s*\(\s*\$s\s*\)\s*=>\s*\$s\s*>\s*80\s*\)/',
                        $source
                    );

                    $this->assertTrue(
                        $usesCorrectThreshold,
                        'COUNTEREXAMPLE: ResumeQualityDetector::ruleBasedDetect() uses the wrong '
                        . 'threshold for locking sections. '
                        . 'The design requires: fn($s) => $s > 80 (strictly greater than 80). '
                        . 'Current code uses: fn($s) => $s >= 85 (greater than or equal to 85). '
                        . 'This means sections scoring 81-84 are NOT locked even though they are '
                        . 'strong sections that should be protected from AI modification. '
                        . 'Section scores in this test: ' . json_encode($scores) . '. '
                        . 'This confirms the resume quality detection threshold bug.'
                    );
                }
            }
        } else {
            // Experience scored in 81-84 range — verify it's locked
            $this->assertContains(
                'experience',
                $locked,
                sprintf(
                    'COUNTEREXAMPLE: Experience section scored %d (> 80) but is NOT in locked_sections. '
                    . 'Locked sections: %s. '
                    . 'The design requires locking at score > 80, but current code uses >= 85. '
                    . 'This confirms the resume quality detection threshold bug.',
                    $experienceScore,
                    json_encode($locked)
                )
            );
        }
    }
}
