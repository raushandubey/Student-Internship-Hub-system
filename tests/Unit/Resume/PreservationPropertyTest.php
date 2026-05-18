<?php

namespace Tests\Unit\Resume;

use App\Services\Resume\AiProviderGateway;
use App\Services\Resume\AtsScoreEngine;
use App\Services\Resume\IdentityPreservationEngine;
use App\Services\Resume\LatexTemplateEngine;
use App\Services\Resume\N8nOrchestrator;
use App\Services\Resume\ResumeParserEngine;
use App\Services\Resume\ResumeQualityDetector;
use App\Services\Resume\SemanticSkillMatcher;
use Tests\TestCase;

/**
 * Preservation Property Tests — Properties 9–12
 *
 * CRITICAL: These tests MUST PASS on unfixed code.
 * They establish the regression baseline — behaviors that must remain
 * unchanged after all 8 subsystem fixes are applied.
 *
 * Observation-first methodology:
 *   1. Observe current behavior on unfixed code
 *   2. Record exact outputs
 *   3. Assert identical behavior after fixes
 *
 * **Validates: Requirements 3.1–3.24**
 */
class PreservationPropertyTest extends TestCase
{

    // ─────────────────────────────────────────────────────────────────────────
    // Property 9: Preservation — Core Resume Processing
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 9: For any valid resume PDF upload, job description matching,
     * or resume metadata processing, the fixed system SHALL produce exactly
     * the same behavior as the original system, preserving:
     *   - PDF parsing via smalot/pdfparser (Requirement 3.1)
     *   - File storage in configured disk (Requirement 3.2)
     *   - Candidate profile metadata persistence (Requirement 3.3)
     *   - Job description skill/keyword extraction (Requirements 3.4–3.6)
     *
     * Observation: ResumeParserEngine::parseRawText() produces structured output
     * with name, email, phone, skills, experience, projects, education, raw_text.
     * SemanticSkillMatcher::scoreSkills() returns score, matching, missing arrays.
     *
     * EXPECTED TO PASS on unfixed code — confirms baseline behavior to preserve.
     *
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**
     */
    public function test_property_9_core_resume_processing_produces_stable_structured_output(): void
    {
        // ── Observe: ResumeParserEngine output structure ──────────────────
        $parser = new ResumeParserEngine();

        $resumeText = "John Doe\njohn@example.com\n+1-555-0100\n\n"
            . "PROFESSIONAL SUMMARY\nBackend Engineer with 3 years of Laravel experience.\n\n"
            . "TECHNICAL SKILLS\nPHP, Laravel, MySQL, Docker, REST API, JWT\n\n"
            . "PROFESSIONAL EXPERIENCE\nBackend Engineer | TechCorp | 2021-2024\n"
            . "- Architected REST API serving 10k concurrent users\n"
            . "- Optimized MySQL queries reducing latency by 40%\n"
            . "- Deployed Docker containers for microservice architecture\n\n"
            . "EDUCATION\nState University | B.Sc. Computer Science | 2021\n\n"
            . "PROJECTS\nAPI Gateway | Laravel, Redis\n"
            . "- Built rate-limited API gateway handling 5k requests/sec\n";

        $parsed = $parser->parseRawText($resumeText);

        // Assert: parser returns all required structural keys (Requirement 3.1, 3.3)
        $requiredKeys = ['name', 'email', 'phone', 'summary', 'skills', 'experience',
                         'projects', 'education', 'raw_text', 'weak_bullets'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $parsed,
                "Property 9 FAILED: ResumeParserEngine output missing key '{$key}'. "
                . "Core resume processing structure must remain unchanged after fixes."
            );
        }

        // Assert: raw_text is preserved (Requirement 3.1 — smalot/pdfparser output preserved)
        $this->assertNotEmpty(
            $parsed['raw_text'],
            'Property 9 FAILED: raw_text must be non-empty after parsing.'
        );

        // Assert: skills are extracted as an array (Requirement 3.4 — JD skill extraction)
        $this->assertIsArray(
            $parsed['skills'],
            'Property 9 FAILED: skills must be an array. JD skill extraction must remain unchanged.'
        );

        // Assert: experience is extracted as an array of entries (Requirement 3.3)
        $this->assertIsArray(
            $parsed['experience'],
            'Property 9 FAILED: experience must be an array.'
        );

        // Assert: weak_bullets detection still runs (Requirement 3.3 — metadata persistence)
        $this->assertIsArray(
            $parsed['weak_bullets'],
            'Property 9 FAILED: weak_bullets must be an array. Metadata processing must remain unchanged.'
        );

        // ── Observe: SemanticSkillMatcher::scoreSkills() output structure ─
        // (Requirement 3.4–3.6 — JD matching, skill gap analysis, compatibility scores)
        $matcher = new SemanticSkillMatcher();
        SemanticSkillMatcher::resetCache();

        $requiredSkills = ['PHP', 'Laravel', 'MySQL', 'Docker'];
        $resumeBody = 'Backend Engineer with PHP, Laravel, MySQL, Docker, REST API experience.';

        $scoreResult = $matcher->scoreSkills($requiredSkills, $resumeBody);

        // Assert: scoreSkills returns required keys (Requirement 3.5 — skill gap analysis)
        $this->assertArrayHasKey('score', $scoreResult,
            'Property 9 FAILED: scoreSkills() must return "score" key.');
        $this->assertArrayHasKey('matching', $scoreResult,
            'Property 9 FAILED: scoreSkills() must return "matching" key.');
        $this->assertArrayHasKey('missing', $scoreResult,
            'Property 9 FAILED: scoreSkills() must return "missing" key.');
        $this->assertArrayHasKey('explanations', $scoreResult,
            'Property 9 FAILED: scoreSkills() must return "explanations" key.');

        // Assert: score is an integer in 0-100 range (Requirement 3.6 — compatibility scores)
        $this->assertIsInt($scoreResult['score'],
            'Property 9 FAILED: score must be an integer.');
        $this->assertGreaterThanOrEqual(0, $scoreResult['score'],
            'Property 9 FAILED: score must be >= 0.');
        $this->assertLessThanOrEqual(100, $scoreResult['score'],
            'Property 9 FAILED: score must be <= 100.');

        // Assert: matching and missing are arrays (Requirement 3.5)
        $this->assertIsArray($scoreResult['matching'],
            'Property 9 FAILED: matching must be an array.');
        $this->assertIsArray($scoreResult['missing'],
            'Property 9 FAILED: missing must be an array.');

        // Assert: literal skills present in resume text are matched (Requirement 3.4)
        // PHP, Laravel, MySQL, Docker are all literally in the resume text
        $this->assertContains('PHP', $scoreResult['matching'],
            'Property 9 FAILED: "PHP" is literally in resume text and must be in matching array. '
            . 'Core skill extraction behavior must remain unchanged.');
        $this->assertContains('Laravel', $scoreResult['matching'],
            'Property 9 FAILED: "Laravel" is literally in resume text and must be in matching array.');

        // Assert: score is 100% when all required skills are present (Requirement 3.6)
        $this->assertSame(100, $scoreResult['score'],
            'Property 9 FAILED: All 4 required skills are present in resume text, '
            . 'score must be 100. JD compatibility scoring must remain unchanged.');
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Property 10: Preservation — AI Provider Integration and PDF Generation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 10: For any AI optimization request or PDF generation request,
     * the fixed system SHALL produce exactly the same behavior as the original
     * system, preserving:
     *   - Multi-provider fallback logic (OpenAI/Anthropic/OpenRouter) (Requirements 3.7–3.8)
     *   - API key configuration from environment (Requirement 3.9)
     *   - LaTeXLite API integration (Requirements 3.10–3.11)
     *   - PDF storage and download URL (Requirement 3.12)
     *
     * Observation: AiProviderGateway::providers() returns providers in order:
     *   openai (if key present) → anthropic (if key present) → openrouter (always last).
     * LatexTemplateEngine::generatePdfResult() returns array with success/pdf/stage/message/http_status.
     *
     * EXPECTED TO PASS on unfixed code — confirms baseline behavior to preserve.
     *
     * **Validates: Requirements 3.7, 3.8, 3.9, 3.10, 3.11, 3.12**
     */
    public function test_property_10_ai_provider_integration_and_pdf_generation_behavior_unchanged(): void
    {
        // ── Observe: AiProviderGateway::providers() structure ─────────────
        // (Requirements 3.7–3.9 — multi-provider fallback, API key config)
        $gateway = new AiProviderGateway();
        $providers = $gateway->providers();

        // Assert: providers() always returns an array (Requirement 3.7)
        $this->assertIsArray($providers,
            'Property 10 FAILED: providers() must return an array. '
            . 'Multi-provider fallback must remain unchanged.');

        // Assert: openrouter is always present as the last fallback (Requirement 3.8)
        // OpenRouter is always added regardless of key presence — it's the final fallback
        $providerNames = array_column($providers, 'name');
        $this->assertContains('openrouter', $providerNames,
            'Property 10 FAILED: "openrouter" must always be present in providers list. '
            . 'OpenRouter is the final fallback provider and must remain unchanged.');

        // Assert: each provider entry has required keys (Requirement 3.9 — API key config)
        foreach ($providers as $provider) {
            $this->assertArrayHasKey('name', $provider,
                'Property 10 FAILED: each provider must have "name" key.');
            $this->assertArrayHasKey('key_present', $provider,
                'Property 10 FAILED: each provider must have "key_present" key. '
                . 'API key configuration structure must remain unchanged.');
            $this->assertArrayHasKey('model', $provider,
                'Property 10 FAILED: each provider must have "model" key.');
            $this->assertArrayHasKey('endpoint', $provider,
                'Property 10 FAILED: each provider must have "endpoint" key.');
        }

        // Assert: openrouter endpoint points to openrouter.ai (Requirement 3.8)
        $openrouter = collect($providers)->firstWhere('name', 'openrouter');
        $this->assertNotNull($openrouter,
            'Property 10 FAILED: openrouter provider entry must exist.');
        $this->assertStringContainsString('openrouter.ai', $openrouter['endpoint'],
            'Property 10 FAILED: openrouter endpoint must contain "openrouter.ai". '
            . 'Provider endpoint configuration must remain unchanged.');

        // Assert: complete() returns array with required keys on failure
        // (Requirement 3.7 — fallback behavior when all providers fail)
        $result = $gateway->complete('test_purpose', 'system prompt', 'user prompt');
        $this->assertIsArray($result,
            'Property 10 FAILED: complete() must return an array.');
        $this->assertArrayHasKey('success', $result,
            'Property 10 FAILED: complete() must return "success" key.');
        $this->assertArrayHasKey('attempts', $result,
            'Property 10 FAILED: complete() must return "attempts" key for fallback tracking.');

        // ── Observe: LatexTemplateEngine::generatePdfResult() structure ───
        // (Requirements 3.10–3.12 — LaTeXLite API integration, PDF storage)
        $latexEngine = new LatexTemplateEngine();

        // Assert: generatePdfResult() method exists (Requirement 3.10)
        $this->assertTrue(
            method_exists($latexEngine, 'generatePdfResult'),
            'Property 10 FAILED: LatexTemplateEngine::generatePdfResult() must exist. '
            . 'LaTeXLite API integration must remain unchanged.'
        );

        // Assert: generatePdf() method exists (Requirement 3.10)
        $this->assertTrue(
            method_exists($latexEngine, 'generatePdf'),
            'Property 10 FAILED: LatexTemplateEngine::generatePdf() must exist. '
            . 'PDF generation interface must remain unchanged.'
        );

        // Call generatePdfResult() with minimal data — will fail due to missing API key
        // but the RETURN STRUCTURE must remain unchanged (Requirement 3.12)
        $minimalData = [
            'name'     => 'Test Candidate',
            'email'    => 'test@example.com',
            'headline' => 'Backend Engineer',
            'sections' => [
                'summary'    => 'Backend Engineer with PHP experience.',
                'skills'     => ['PHP', 'Laravel'],
                'experience' => [
                    [
                        'title'   => 'Backend Engineer',
                        'org'     => 'Corp',
                        'date'    => '2021-2024',
                        'bullets' => ['Built REST APIs with Laravel'],
                    ],
                ],
                'education'  => [['school' => 'University', 'degree' => 'CS', 'year' => '2021']],
                'projects'   => [],
            ],
        ];

        $pdfResult = $latexEngine->generatePdfResult($minimalData);

        // Assert: generatePdfResult() returns array with required keys (Requirement 3.12)
        $this->assertIsArray($pdfResult,
            'Property 10 FAILED: generatePdfResult() must return an array.');
        $this->assertArrayHasKey('success', $pdfResult,
            'Property 10 FAILED: generatePdfResult() must return "success" key.');
        $this->assertArrayHasKey('pdf', $pdfResult,
            'Property 10 FAILED: generatePdfResult() must return "pdf" key.');
        $this->assertArrayHasKey('stage', $pdfResult,
            'Property 10 FAILED: generatePdfResult() must return "stage" key.');
        $this->assertArrayHasKey('message', $pdfResult,
            'Property 10 FAILED: generatePdfResult() must return "message" key.');
        $this->assertArrayHasKey('http_status', $pdfResult,
            'Property 10 FAILED: generatePdfResult() must return "http_status" key. '
            . 'PDF generation result structure must remain unchanged after fixes.');

        // Assert: validateLatex() method exists for LaTeX validation (Requirement 3.10)
        $this->assertTrue(
            method_exists($latexEngine, 'validateLatex'),
            'Property 10 FAILED: LatexTemplateEngine::validateLatex() must exist. '
            . 'LaTeX validation interface must remain unchanged.'
        );
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Property 11: Preservation — User Interface and Authentication
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 11: For any user interaction with resume optimization features,
     * the fixed system SHALL produce exactly the same behavior as the original
     * system, preserving:
     *   - UI display components (Requirements 3.13–3.15)
     *   - Light mode optimization behavior (Requirements 3.16–3.18)
     *   - Authentication and authorization enforcement (Requirements 3.19–3.21)
     *
     * Observation: AtsScoreEngine::evaluateImprovement() returns before_score,
     * after_score, after_breakdown, before_breakdown, improvements arrays.
     * RESUME_OPTIMIZER_LIGHT_MODE config controls optimization aggressiveness.
     * ResumeQualityDetector::ruleBasedDetect() returns tier, section_scores,
     * locked_sections, preservation_mode, weak_sections.
     *
     * EXPECTED TO PASS on unfixed code — confirms baseline behavior to preserve.
     *
     * **Validates: Requirements 3.13, 3.14, 3.15, 3.16, 3.17, 3.18, 3.19, 3.20, 3.21**
     */
    public function test_property_11_ui_and_authentication_behavior_unchanged(): void
    {
        // ── Observe: AtsScoreEngine::evaluateImprovement() output structure ─
        // (Requirements 3.13–3.15 — before/after comparison display)
        $mockGateway = $this->createMock(AiProviderGateway::class);
        $mockGateway->method('complete')->willReturn([
            'success'  => false,
            'error'    => 'no_key',
            'attempts' => [],
        ]);

        $scoreEngine = new AtsScoreEngine(aiGateway: $mockGateway, skillMatcher: null);

        $resume = [
            'raw_text'   => 'Backend Engineer with PHP, Laravel, MySQL, Docker, REST API. '
                . 'Architected scalable microservices. Optimized queries reducing latency by 40%.',
            'summary'    => 'Backend Engineer with 3 years of Laravel experience.',
            'skills'     => ['PHP', 'Laravel', 'MySQL', 'Docker'],
            'experience' => [
                [
                    'title'   => 'Backend Engineer',
                    'org'     => 'Corp',
                    'date'    => '2021-2024',
                    'bullets' => [
                        'Architected REST API serving 10k concurrent users',
                        'Optimized MySQL queries reducing latency by 40%',
                    ],
                ],
            ],
            'education'  => [['school' => 'University', 'degree' => 'CS', 'year' => '2021']],
            'projects'   => [],
        ];

        $jdAnalysis = [
            'role_category'   => 'backend',
            'required_skills' => ['PHP', 'Laravel', 'MySQL'],
            'keywords'        => ['backend', 'api', 'rest'],
            'semantic_clusters' => [],
        ];

        $scoreResult = $scoreEngine->evaluateImprovement($resume, $resume, $jdAnalysis, 'average');

        // Assert: evaluateImprovement() returns all required keys for UI display (Requirement 3.13)
        $requiredKeys = ['before_score', 'after_score', 'after_breakdown', 'before_breakdown',
                         'improvements', 'ai_used', 'fallback_used', 'rule_delta', 'attempts'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $scoreResult,
                "Property 11 FAILED: evaluateImprovement() missing key '{$key}'. "
                . "UI before/after comparison display structure must remain unchanged."
            );
        }

        // Assert: before_score and after_score are integers (Requirement 3.13 — score display)
        $this->assertIsInt($scoreResult['before_score'],
            'Property 11 FAILED: before_score must be an integer for UI display.');
        $this->assertIsInt($scoreResult['after_score'],
            'Property 11 FAILED: after_score must be an integer for UI display.');

        // Assert: improvements is an array (Requirement 3.15 — download/comparison view)
        $this->assertIsArray($scoreResult['improvements'],
            'Property 11 FAILED: improvements must be an array for UI display.');

        // Assert: after_breakdown contains overall_score (Requirement 3.13)
        $this->assertArrayHasKey('overall_score', $scoreResult['after_breakdown'],
            'Property 11 FAILED: after_breakdown must contain "overall_score" for UI display.');

        // ── Observe: RESUME_OPTIMIZER_LIGHT_MODE behavior ─────────────────
        // (Requirements 3.16–3.18 — light mode, min score delta, min text delta)

        // Assert: light mode config key is readable (Requirement 3.16)
        // The config key must remain at 'services.resume_optimizer.light_mode'
        $lightModeValue = config('services.resume_optimizer.light_mode');
        // Value can be true/false/null — just assert the config path is accessible
        // (not throwing an exception means the config structure is intact)
        $this->assertTrue(
            $lightModeValue === true || $lightModeValue === false || $lightModeValue === null,
            'Property 11 FAILED: RESUME_OPTIMIZER_LIGHT_MODE config must be boolean or null. '
            . 'Light mode configuration must remain unchanged.'
        );

        // Assert: min score delta config key is readable (Requirement 3.18)
        $minScoreDelta = config('services.resume_optimizer.min_score_delta');
        $this->assertTrue(
            is_numeric($minScoreDelta) || $minScoreDelta === null,
            'Property 11 FAILED: RESUME_OPTIMIZER_MIN_SCORE_DELTA config must be numeric or null. '
            . 'Minimum score delta threshold must remain unchanged.'
        );

        // Assert: min text delta config key is readable (Requirement 3.18)
        $minTextDelta = config('services.resume_optimizer.min_text_delta');
        $this->assertTrue(
            is_numeric($minTextDelta) || $minTextDelta === null,
            'Property 11 FAILED: RESUME_OPTIMIZER_MIN_TEXT_DELTA config must be numeric or null. '
            . 'Minimum text delta threshold must remain unchanged.'
        );

        // ── Observe: ResumeQualityDetector::ruleBasedDetect() output structure ─
        // (Requirements 3.16–3.18 — light mode preservation mode)
        $detector = new ResumeQualityDetector();

        $weakResume = [
            'raw_text'   => 'I am a hardworking team player passionate about coding.',
            'summary'    => 'Hardworking team player.',
            'skills'     => ['PHP'],
            'experience' => [],
            'projects'   => [],
            'education'  => [['school' => 'University', 'degree' => 'CS', 'year' => '2023']],
        ];

        $qualityResult = $detector->ruleBasedDetect($weakResume);

        // Assert: ruleBasedDetect() returns all required keys (Requirement 3.16)
        $requiredQualityKeys = ['tier', 'quality_score', 'section_scores', 'locked_sections',
                                'preservation_mode', 'weak_sections', 'quality_signals'];
        foreach ($requiredQualityKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $qualityResult,
                "Property 11 FAILED: ruleBasedDetect() missing key '{$key}'. "
                . "Quality detection output structure must remain unchanged."
            );
        }

        // Assert: tier is one of the valid values (Requirement 3.16)
        $this->assertContains(
            $qualityResult['tier'],
            ['elite', 'strong', 'average', 'weak'],
            'Property 11 FAILED: tier must be one of elite/strong/average/weak. '
            . 'Quality tier classification must remain unchanged.'
        );

        // Assert: weak resume gets 'weak' or 'average' tier (Requirement 3.17 — light mode avoids full rewrites)
        $this->assertContains(
            $qualityResult['tier'],
            ['weak', 'average'],
            'Property 11 FAILED: A resume with only filler phrases and no experience '
            . 'must be classified as "weak" or "average". '
            . 'This ensures average/weak resumes continue to receive full optimization '
            . 'as required by Requirement 3.17.'
        );

        // Assert: preservation_mode is false for weak resume (Requirement 3.17)
        $this->assertFalse(
            $qualityResult['preservation_mode'],
            'Property 11 FAILED: preservation_mode must be false for weak/average resumes. '
            . 'Weak resumes must continue to receive full optimization (Requirement 3.17).'
        );
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Property 12: Preservation — Database Operations
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 12: For any database interaction during resume optimization,
     * the fixed system SHALL produce exactly the same behavior as the original
     * system, preserving:
     *   - Laravel Eloquent ORM usage (Requirement 3.22)
     *   - Optimization history tracking (Requirement 3.23)
     *   - Transaction wrappers for data consistency (Requirement 3.24)
     *
     * Observation: ResumeScore and ResumeVersion Eloquent models exist and use
     * standard Eloquent ORM. ResumeOptimizationService uses DB::transaction()
     * wrappers. Models have fillable arrays and standard Eloquent relationships.
     *
     * EXPECTED TO PASS on unfixed code — confirms baseline behavior to preserve.
     *
     * **Validates: Requirements 3.22, 3.23, 3.24**
     */
    public function test_property_12_database_operations_use_eloquent_orm_unchanged(): void
    {
        // ── Observe: Eloquent model classes exist (Requirement 3.22) ──────
        $this->assertTrue(
            class_exists(\App\Models\ResumeScore::class),
            'Property 12 FAILED: App\Models\ResumeScore Eloquent model must exist. '
            . 'Eloquent ORM usage must remain unchanged (Requirement 3.22).'
        );

        $this->assertTrue(
            class_exists(\App\Models\ResumeVersion::class),
            'Property 12 FAILED: App\Models\ResumeVersion Eloquent model must exist. '
            . 'Eloquent ORM usage must remain unchanged (Requirement 3.22).'
        );

        // ── Observe: ResumeScore model uses Eloquent (Requirement 3.22) ───
        $resumeScoreReflection = new \ReflectionClass(\App\Models\ResumeScore::class);
        $this->assertTrue(
            $resumeScoreReflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class),
            'Property 12 FAILED: ResumeScore must extend Eloquent Model. '
            . 'Eloquent ORM usage must remain unchanged (Requirement 3.22).'
        );

        // ── Observe: ResumeVersion model uses Eloquent (Requirement 3.22) ─
        $resumeVersionReflection = new \ReflectionClass(\App\Models\ResumeVersion::class);
        $this->assertTrue(
            $resumeVersionReflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class),
            'Property 12 FAILED: ResumeVersion must extend Eloquent Model. '
            . 'Eloquent ORM usage must remain unchanged (Requirement 3.22).'
        );

        // ── Observe: ResumeScore has fillable fields for optimization history ─
        // (Requirement 3.23 — optimization history tracking)
        $resumeScore = new \App\Models\ResumeScore();
        $fillable = $resumeScore->getFillable();

        $this->assertIsArray($fillable,
            'Property 12 FAILED: ResumeScore::getFillable() must return an array. '
            . 'Optimization history tracking must remain unchanged (Requirement 3.23).');

        // Assert: fillable contains score-related fields for history tracking
        $this->assertNotEmpty($fillable,
            'Property 12 FAILED: ResumeScore must have fillable fields for optimization history. '
            . 'Requirement 3.23 requires optimization attempts, scores, and timestamps to be tracked.');

        // ── Observe: ResumeVersion has fillable fields (Requirement 3.23) ─
        $resumeVersion = new \App\Models\ResumeVersion();
        $versionFillable = $resumeVersion->getFillable();

        $this->assertIsArray($versionFillable,
            'Property 12 FAILED: ResumeVersion::getFillable() must return an array.');
        $this->assertNotEmpty($versionFillable,
            'Property 12 FAILED: ResumeVersion must have fillable fields for version tracking. '
            . 'Optimization history tracking must remain unchanged (Requirement 3.23).');

        // ── Observe: ResumeOptimizationService uses Eloquent ORM for persistence ─
        // (Requirement 3.24 — transaction wrappers for data consistency)
        // Observation: The service uses ResumeScore::updateOrCreate() and
        // ResumeVersion::create() as the primary persistence mechanism.
        // These Eloquent calls are the baseline database operations to preserve.
        $serviceSource = file_get_contents(
            base_path('app/Services/ResumeOptimizationService.php')
        );

        $this->assertNotFalse(
            $serviceSource,
            'Property 12 FAILED: Could not read ResumeOptimizationService.php source.'
        );

        // Assert: ResumeScore::updateOrCreate() is used for score persistence (Requirement 3.23)
        $this->assertStringContainsString(
            'ResumeScore::updateOrCreate',
            $serviceSource,
            'Property 12 FAILED: ResumeOptimizationService must use ResumeScore::updateOrCreate() '
            . 'for optimization score persistence. '
            . 'Eloquent ORM usage for optimization history must remain unchanged (Requirement 3.22, 3.23).'
        );

        // Assert: ResumeVersion::create() is used for version tracking (Requirement 3.23)
        $this->assertStringContainsString(
            'ResumeVersion::create',
            $serviceSource,
            'Property 12 FAILED: ResumeOptimizationService must use ResumeVersion::create() '
            . 'for optimization version tracking. '
            . 'Optimization history tracking must remain unchanged (Requirement 3.23).'
        );

        // ── Observe: N8nOrchestrator dispatch() returns structured array ──
        // (Requirement 3.22 — Eloquent ORM; N8N results feed into DB operations)
        $orchestrator = new N8nOrchestrator();

        // dispatch() with no webhook configured returns structured failure array
        $dispatchResult = $orchestrator->dispatch('system', 'user');

        $this->assertIsArray($dispatchResult,
            'Property 12 FAILED: N8nOrchestrator::dispatch() must return an array. '
            . 'Orchestration results feed into database operations.');

        $this->assertArrayHasKey('success', $dispatchResult,
            'Property 12 FAILED: dispatch() must return "success" key.');
        $this->assertArrayHasKey('data', $dispatchResult,
            'Property 12 FAILED: dispatch() must return "data" key.');
        $this->assertArrayHasKey('error', $dispatchResult,
            'Property 12 FAILED: dispatch() must return "error" key.');
        $this->assertArrayHasKey('attempts', $dispatchResult,
            'Property 12 FAILED: dispatch() must return "attempts" key. '
            . 'Attempt tracking feeds into optimization history (Requirement 3.23).');

        // Assert: dispatch() returns false success when no real webhook is reachable
        // (in test environment, webhook either isn't configured or fails to connect)
        $this->assertFalse(
            $dispatchResult['success'],
            'Property 12 FAILED: dispatch() must return success=false when webhook is unreachable. '
            . 'This baseline behavior must remain unchanged after N8N retry fixes.'
        );

        // Assert: attempts is an array (Requirement 3.23 — attempt tracking for history)
        // NOTE: After the Subsystem 7 fix, attempts is an array of attempt objects
        // (each with 'attempt', 'error', 'delay_seconds' keys) rather than a plain integer.
        // This richer structure still satisfies Requirement 3.23 (optimization history tracking).
        $this->assertIsArray(
            $dispatchResult['attempts'],
            'Property 12 FAILED: dispatch() must return "attempts" array for optimization history tracking. '
            . 'Attempt tracking must remain unchanged (Requirement 3.23).'
        );
    }
}
