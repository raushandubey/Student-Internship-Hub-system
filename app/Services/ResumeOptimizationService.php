<?php

namespace App\Services;

use App\Models\Internship;
use App\Models\Profile;
use App\Models\ResumeScore;
use App\Models\ResumeVersion;
use App\Models\User;
use App\Services\Resume\AiRewriteEngine;
use App\Services\Resume\AtsScoreEngine;
use App\Services\Resume\JobDescriptionAnalyzer;
use App\Services\Resume\ResumeParserEngine;
use App\Services\Resume\ResumeQualityDetector;
use App\Services\Resume\ResumeOptimizationQualityGate;
use App\Services\Resume\WeaknessDetectionEngine;
use App\Support\ResumeStoragePaths;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ResumeOptimizationService — Master Pipeline Orchestrator (Rebuilt).
 *
 * Full Pipeline:
 *  1. ResumeParserEngine     → extract + structure resume text
 *  2. JobDescriptionAnalyzer → extract JD intelligence
 *  3. ResumeQualityDetector  → classify resume tier (elite/strong/average/weak)
 *                              + lock strong sections from AI rewriting
 *  4. WeaknessDetectionEngine→ detect only genuinely weak areas
 *  5. AtsScoreEngine         → realistic weighted ATS score (intrinsic + JD alignment)
 *  6. AiRewriteEngine        → selective enhancement (locked sections preserved)
 *  7. Persist results
 */
class ResumeOptimizationService
{
    private ?string $resumeAccessIssue = null;

    public function __construct(
        private readonly ResumeParserEngine      $parser,
        private readonly JobDescriptionAnalyzer  $jdAnalyzer,
        private readonly ResumeQualityDetector   $qualityDetector,
        private readonly WeaknessDetectionEngine $weaknessEngine,
        private readonly AtsScoreEngine          $scoreEngine,
        private readonly AiRewriteEngine         $rewriteEngine,
        private readonly ResumeOptimizationQualityGate $qualityGate,
    ) {}

    /* ------------------------------------------------------------------ */
    /*  Analyse Resume (Score Only)                                         */
    /* ------------------------------------------------------------------ */

    public function analyseResume(User $user, Internship $internship): array
    {
        try {
            Log::info('[Pipeline] START: Resume Analysis', ['user_id' => $user->id, 'internship_id' => $internship->id]);

            $profile = $user->profile;
            if (!$profile || !$profile->resume_path) {
                Log::warning('[Pipeline] FAIL: No resume path found', ['user_id' => $user->id]);
                return $this->emptyScore('', 'RESUME_NOT_FOUND');
            }

            $absolutePath = $this->resolveResumePath($profile);
            if (!$absolutePath) {
                Log::warning('[Pipeline] FAIL: Resume file not found on disk', [
                    'user_id' => $user->id,
                    'issue' => $this->resumeAccessIssue,
                ]);
                return $this->emptyScore($this->resumeAccessIssue ?? '', 'RESUME_DOWNLOAD_FAILED');
            }

            // ── Stage 1: Parsing ──────────────────────────────────────────
            if (is_array($absolutePath) && ($absolutePath['mode'] ?? '') === 's3_content') {
                $parsedResume = $this->parser->parseFromContent($absolutePath['content']);
            } else {
                $parsedResume = $this->parser->parse($absolutePath);
            }
            Log::info('[Pipeline] Stage 1 COMPLETE: Resume Parsed', ['text_length' => strlen($parsedResume['raw_text'] ?? '')]);
            Log::info('[PDF_PARSE_SUCCESS]', [
                'user_id' => $user->id,
                'internship_id' => $internship->id,
                'text_length' => strlen($parsedResume['raw_text'] ?? ''),
            ]);

            if (!empty($parsedResume['parse_error']) && empty(trim($parsedResume['raw_text']))) {
                Log::error('[Pipeline] Stage 1 FAIL: Parser error', [
                    'error' => $parsedResume['parse_error'],
                    'user_id' => $user->id,
                    'path' => is_array($absolutePath) ? ($absolutePath['path'] ?? 'unknown') : basename($absolutePath),
                ]);
                return $this->emptyScore('', 'PARSER_FAILED');
            }

            // ── Stage 2: JD Intelligence ──────────────────────────────────
            $jdAnalysis = $this->jdAnalyzer->analyze($internship);
            Log::info('[Pipeline] Stage 2 COMPLETE: JD Analyzed', ['role' => $jdAnalysis['role_category'] ?? 'unknown']);

            // ── Stage 3: Quality Detection ───────────────────────────────
            $qualityReport = $this->qualityDetector->detect($parsedResume);
            Log::info('[Pipeline] Stage 3 COMPLETE: Quality Detected', ['tier' => $qualityReport['tier'] ?? 'unknown']);

            // ── Stage 4: Weakness Detection ──────────────────────────────
            $weaknessReport = $this->weaknessEngine->detect($parsedResume, $jdAnalysis);
            Log::info('[Pipeline] Stage 4 COMPLETE: Weaknesses Detected', ['count' => count($weaknessReport['weak_areas'] ?? [])]);

            // ── Stage 5: ATS Scoring ─────────────────────────────────────
            $scoreBreakdown = $this->scoreEngine->ruleBasedScore($parsedResume, $jdAnalysis);
            Log::info('[Pipeline] Stage 5 COMPLETE: ATS Scored', ['score' => $scoreBreakdown['overall_score'] ?? 0]);

            // ── Persist Score ─────────────────────────────────────────────
            $score = ResumeScore::updateOrCreate(
                ['user_id' => $user->id, 'internship_id' => $internship->id],
                [
                    'overall_score'     => $scoreBreakdown['overall_score'],
                    'skill_match_score' => $scoreBreakdown['skill_match'],
                    'keyword_score'     => $scoreBreakdown['keyword_score'],
                    'format_score'      => $scoreBreakdown['format_score'],
                    'matching_skills'   => $scoreBreakdown['matching_skills'],
                    'missing_skills'    => $scoreBreakdown['missing_skills'],
                    'issues'            => $scoreBreakdown['issues'],
                    'strengths'         => $scoreBreakdown['strengths'],
                    'match_tier'        => ResumeScore::scoreTier($scoreBreakdown['overall_score']),
                ]
            );

            Log::info('[Pipeline] SUCCESS: Resume Analysis Finished');

            return [
                'score'              => $scoreBreakdown['overall_score'],
                'skill_match'        => $scoreBreakdown['skill_match'],
                'keyword_score'      => $scoreBreakdown['keyword_score'],
                'format_score'       => $scoreBreakdown['format_score'],
                'exp_score'          => $scoreBreakdown['exp_score'],
                'proj_score'         => $scoreBreakdown['proj_score'],
                'intrinsic_score'    => $scoreBreakdown['intrinsic_score'] ?? 0,
                'overall_score'      => $scoreBreakdown['overall_score'],
                'matching_skills'    => $scoreBreakdown['matching_skills'],
                'missing_skills'    => $scoreBreakdown['missing_skills'],
                'issues'             => $scoreBreakdown['issues'],
                'strengths'          => $scoreBreakdown['strengths'],
                'weak_areas'         => $weaknessReport['weak_areas'],
                'recommendations'    => $weaknessReport['recommendations'],
                'role_category'      => $jdAnalysis['role_category'],
                'tier'               => $score->match_tier,
                'gate_message'       => $score->gateMessage(),
                'badge_class'        => $score->badgeClass(),
                'resume_text'        => $parsedResume['raw_text'],
                'quality_tier'       => $qualityReport['tier'],
                'quality_score'      => $qualityReport['quality_score'],
                'section_scores'     => $qualityReport['section_scores'],
                'locked_sections'    => $qualityReport['locked_sections'],
                'preservation_mode'  => $qualityReport['preservation_mode'],
            ];

        } catch (\Exception $e) {
            Log::error('[Pipeline] FATAL: Analysis crashed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->emptyScore('Internal processing error: ' . $e->getMessage(), 'CRITICAL_SYSTEM_FAILURE');
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Rewrite Resume (AI Optimize)                                        */
    /* ------------------------------------------------------------------ */

    public function rewriteResume(User $user, Internship $internship): array
    {
        try {
            Log::info('[Pipeline] START: Resume Rewrite', ['user_id' => $user->id, 'internship_id' => $internship->id]);

            $profile = $user->profile;
            if (!$profile || !$profile->resume_path) {
                Log::warning('[Pipeline] FAIL: No resume path found');
                return ['success' => false, 'error' => 'No resume found. Please upload a resume first.'];
            }

            $absolutePath = $this->resolveResumePath($profile);
            if (!$absolutePath) {
                Log::warning('[Pipeline] FAIL: Resume file not found on disk');
                return ['success' => false, 'error' => 'Resume file not found. Please re-upload your resume.'];
            }

            // ── Stage 1: Pre-Analysis ────────────────────────────────────
            if (is_array($absolutePath) && ($absolutePath['mode'] ?? '') === 's3_content') {
                $parsedResume = $this->parser->parseFromContent($absolutePath['content']);
            } else {
                $parsedResume = $this->parser->parse($absolutePath);
                // Clean up temp file if we created one for S3/R2
                if (str_contains($absolutePath, 'temp_resume_')) {
                    @unlink($absolutePath);
                }
            }

            if (!empty($parsedResume['parse_error']) && empty(trim($parsedResume['raw_text']))) {
                Log::error('[Pipeline] Stage 1 FAIL: Parser error during rewrite', ['error' => $parsedResume['parse_error']]);
                return ['success' => false, 'error' => 'Unable to read your resume PDF. Please ensure it is a valid, non-encrypted PDF.'];
            }

            Log::info('[PDF_PARSE_SUCCESS]', [
                'user_id' => $user->id,
                'internship_id' => $internship->id,
                'text_length' => strlen($parsedResume['raw_text'] ?? ''),
                'pipeline' => 'rewrite',
            ]);

            $jdAnalysis     = $this->jdAnalyzer->analyze($internship);
            $qualityReport  = $this->qualityDetector->detect($parsedResume);
            $weaknessReport = $this->weaknessEngine->detect($parsedResume, $jdAnalysis);
            $beforeBreakdown= $this->scoreEngine->ruleBasedScore($parsedResume, $jdAnalysis);
            $beforeScore    = $beforeBreakdown['overall_score'];

            Log::info('[Pipeline] Stage 1 COMPLETE: Pre-Analysis Done', [
                'quality_tier' => $qualityReport['tier'],
                'before_score' => $beforeScore
            ]);
            Log::info('[ATS_BEFORE]', [
                'score' => $beforeScore,
                'skill_match' => $beforeBreakdown['skill_match'] ?? null,
                'keyword_score' => $beforeBreakdown['keyword_score'] ?? null,
            ]);

            // ── Stage 2: AI Enhancement ──────────────────────────────────
            $rewriteResult = null;
            $rewrittenText = null;
            $afterParsed = null;
            $aiScoreData = null;
            $afterScore = 0;
            $afterBreakdown = [];
            $improvements = [];
            $qualityGate = null;

            for ($attempt = 1; $attempt <= 2; $attempt++) {
                Log::info('[Pipeline] Stage 2 START: AI Enhancement Request', ['attempt' => $attempt]);

                $rewriteResult = $this->rewriteEngine->rewrite(
                    $parsedResume,
                    $jdAnalysis,
                    $weaknessReport,
                    $qualityReport,
                    $attempt > 1
                );

                if (!$rewriteResult['success']) {
                    Log::error('[Pipeline] Stage 2 FAIL: AI Rewrite failed closed', [
                        'attempt' => $attempt,
                        'stage_failed' => $rewriteResult['stage_failed'] ?? 'AI_UNAVAILABLE',
                        'error' => $rewriteResult['error'] ?? 'unknown',
                    ]);

                    return [
                        'success' => false,
                        'stage_failed' => $rewriteResult['stage_failed'] ?? 'AI_UNAVAILABLE',
                        'error' => 'AI optimization is temporarily unavailable. No fallback resume was created.',
                        'provider_attempts' => $rewriteResult['attempts'] ?? [],
                    ];
                }

                $rewrittenText = $rewriteResult['rewritten_text'];
                $validationIssues = $this->rewriteEngine->validateOutput($rewrittenText);

                if (!empty($validationIssues)) {
                    Log::warning('[Pipeline] Stage 3 WARNING: AI output validation failed', [
                        'attempt' => $attempt,
                        'issues' => $validationIssues,
                    ]);

                    if ($attempt === 1) {
                        continue;
                    }

                    return [
                        'success' => false,
                        'stage_failed' => 'AI_OUTPUT_VALIDATION_FAILED',
                        'error' => 'AI returned a malformed optimization. No optimized version was created.',
                        'validation_issues' => $validationIssues,
                    ];
                }

                $rewrittenText = $this->rewriteEngine->sanitizeForStorage($rewrittenText);
                $afterParsed = json_decode($rewrittenText, true);

                if (!is_array($afterParsed)) {
                    if ($attempt === 1) {
                        continue;
                    }

                    return [
                        'success' => false,
                        'stage_failed' => 'AI_OUTPUT_JSON_INVALID',
                        'error' => 'AI optimization could not be converted into structured resume data.',
                    ];
                }

                $afterParsed['raw_text'] = $this->qualityGate->canonicalRawText($afterParsed);
                $rewrittenText = json_encode($afterParsed);

                Log::info('[Pipeline] Stage 4 START: Semantic Scoring', ['attempt' => $attempt]);
                $aiScoreData    = $this->scoreEngine->evaluateImprovement($parsedResume, $afterParsed, $jdAnalysis);
                $beforeScore    = $aiScoreData['before_score'];
                $afterScore     = $aiScoreData['after_score'];
                $afterBreakdown = $aiScoreData['after_breakdown'];
                $improvements   = $aiScoreData['improvements'];
                $qualityGate    = $this->qualityGate->evaluate($parsedResume, $afterParsed, $jdAnalysis, $beforeScore, $afterScore);

                Log::info('[ATS_AFTER]', [
                    'score' => $afterScore,
                    'score_delta' => $qualityGate['score_delta'],
                    'quality_gate_passed' => $qualityGate['passed'],
                    'attempt' => $attempt,
                ]);

                if ($qualityGate['passed']) {
                    Log::info('[Pipeline] Stage 4 COMPLETE: Optimization quality gate passed', [
                        'after_score' => $afterScore,
                        'quality_gate' => $qualityGate,
                    ]);
                    break;
                }

                Log::warning('[Pipeline] Stage 4 WARNING: Optimization quality gate failed', [
                    'attempt' => $attempt,
                    'quality_gate' => $qualityGate,
                ]);
            }

            if (!$qualityGate || !$qualityGate['passed']) {
                return [
                    'success' => false,
                    'stage_failed' => 'OPTIMIZATION_QUALITY_GATE_FAILED',
                    'error' => 'AI output did not materially improve ATS alignment. No optimized version was created.',
                    'quality_gate' => $qualityGate,
                ];
            }

            // ── Stage 5: Persistence ─────────────────────────────────────
            $this->saveOriginalSnapshot($user->id, $internship->id, $parsedResume['raw_text'], $beforeScore);

            $nextVersion = (ResumeVersion::where('user_id', $user->id)
                ->where('internship_id', $internship->id)
                ->max('version_number') ?? 0) + 1;

            $newVersion = ResumeVersion::create([
                'user_id'           => $user->id,
                'internship_id'     => $internship->id,
                'version_number'    => $nextVersion,
                'label'             => $this->buildVersionLabel($qualityReport['tier'], $rewriteResult),
                'content'           => $rewrittenText,
                'score_at_creation' => $afterScore,
                'type'              => 'ai_rewrite',
            ]);

            ResumeScore::updateOrCreate(
                ['user_id' => $user->id, 'internship_id' => $internship->id],
                [
                    'overall_score'     => $afterScore,
                    'skill_match_score' => $afterBreakdown['skill_match'],
                    'keyword_score'     => $afterBreakdown['keyword_score'],
                    'format_score'      => $afterBreakdown['format_score'],
                    'matching_skills'   => $afterBreakdown['matching_skills'] ?? [],
                    'missing_skills'    => $afterBreakdown['missing_skills'] ?? [],
                    'issues'            => $afterBreakdown['issues'] ?? [],
                    'strengths'         => $afterBreakdown['strengths'] ?? [],
                    'match_tier'        => ResumeScore::scoreTier($afterScore),
                    'resume_version_id' => $newVersion->id,
                ]
            );

            Log::info('[Pipeline] SUCCESS: Resume Rewrite Finished');

            return [
                'success'           => true,
                'rewritten_text'    => $rewrittenText,
                'before_score'      => $beforeScore,
                'after_score'       => $afterScore,
                'improvements'      => $improvements,
                'version_id'        => $newVersion->id,
                'ai_disabled'       => !($rewriteResult['ai_used'] ?? false),
                'ai_used'           => (bool) ($rewriteResult['ai_used'] ?? false),
                'ai_provider'       => $rewriteResult['provider'] ?? $rewriteResult['engine'] ?? null,
                'ai_model'          => $rewriteResult['model'] ?? null,
                'quality_gate'      => $qualityGate,
                'role_category'     => $jdAnalysis['role_category'],
                'quality_tier'      => $qualityReport['tier'],
                'preservation_mode' => $qualityReport['preservation_mode'],
                'rewrite_mode'      => $rewriteResult['mode'] ?? 'rule_based',
                'locked_sections'   => $qualityReport['locked_sections'],
            ];

        } catch (\Exception $e) {
            Log::error('[Pipeline] FATAL: Rewrite crashed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success'      => false,
                'stage_failed' => 'REWRITE_ORCHESTRATION_FAILURE',
                'error'        => 'An internal system error occurred during optimization: ' . $e->getMessage()
            ];
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    public function getExistingScore(User $user, Internship $internship): ?ResumeScore
    {
        return ResumeScore::where('user_id', $user->id)
            ->where('internship_id', $internship->id)
            ->latest()
            ->first();
    }

    private function resolveResumePath(Profile $profile): string|array|null
    {
        $disk = config('filesystems.default');
        $pathCandidates = ResumeStoragePaths::candidates($profile->resume_path);
        $normalizedPath = $pathCandidates[0] ?? ltrim($profile->resume_path, '/');
        $this->resumeAccessIssue = null;

        Log::info('[Pipeline] Resolving resume path', [
            'disk' => $disk,
            'path' => $normalizedPath,
            'path_candidates' => $pathCandidates,
            'profile_id' => $profile->id,
        ]);

        if ($this->isCloudDisk($disk)) {
            foreach ($pathCandidates as $pathCandidate) {
                $content = $this->readCloudResumeContent($disk, $pathCandidate);

                if ($content !== null) {
                    return ['content' => $content, 'mode' => 's3_content', 'path' => $pathCandidate];
                }
            }

            $this->resumeAccessIssue = 'Your resume record exists, but the PDF could not be read from cloud storage. Please re-upload your resume from Profile.';
            return null;
        }

        $localPath = $this->resolveLocalResumePath($disk, $pathCandidates);

        if ($localPath) {
            return $localPath;
        }

        $this->resumeAccessIssue = 'Your resume record points to a file that is missing from server storage. Please re-upload your resume from Profile.';

        return null;
    }

    private function isCloudDisk(string $disk): bool
    {
        return in_array($disk, ['s3', 'r2'], true)
            || config("filesystems.disks.{$disk}.driver") === 's3';
    }

    private function readCloudResumeContent(string $disk, string $normalizedPath): ?string
    {
        try {
            $exists = Storage::disk($disk)->exists($normalizedPath);

            if (!$exists) {
                Log::warning("[Pipeline] {$disk} exists() returned false; trying direct read", [
                    'path' => $normalizedPath,
                    'disk' => $disk,
                ]);
            }

            // Some S3-compatible providers can fail HEAD/exists while GET still works.
            $content = Storage::disk($disk)->get($normalizedPath);

            if ($this->isValidPdfContent($content)) {
                Log::info("[Pipeline] {$disk} content loaded successfully", [
                    'path' => $normalizedPath,
                    'size' => strlen($content),
                    'disk' => $disk,
                    'source' => 'storage_disk',
                ]);

                return $content;
            }

            Log::error("[Pipeline] {$disk} file read but invalid or empty", [
                'path' => $normalizedPath,
                'disk' => $disk,
                'first_bytes' => is_string($content) ? substr($content, 0, 10) : null,
            ]);
        } catch (\Throwable $e) {
            Log::error("[Pipeline] {$disk} storage read failed", [
                'path' => $normalizedPath,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->readCloudResumePublicUrl($disk, $normalizedPath);
    }

    private function readCloudResumePublicUrl(string $disk, string $normalizedPath): ?string
    {
        $url = $this->cloudResumePublicUrl($disk, $normalizedPath);

        if (!$url) {
            Log::error("[Pipeline] {$disk} public URL fallback unavailable", [
                'path' => $normalizedPath,
                'disk' => $disk,
            ]);

            return null;
        }

        try {
            $response = Http::timeout(15)->retry(2, 250)->get($url);

            if (!$response->successful()) {
                Log::error("[Pipeline] {$disk} public URL read failed", [
                    'path' => $normalizedPath,
                    'disk' => $disk,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $content = $response->body();

            if (!$this->isValidPdfContent($content)) {
                Log::error("[Pipeline] {$disk} public URL returned invalid PDF", [
                    'path' => $normalizedPath,
                    'disk' => $disk,
                    'first_bytes' => substr($content, 0, 10),
                ]);

                return null;
            }

            Log::info("[Pipeline] {$disk} content loaded from public URL", [
                'path' => $normalizedPath,
                'size' => strlen($content),
                'disk' => $disk,
                'source' => 'public_url',
            ]);

            return $content;
        } catch (\Throwable $e) {
            Log::error("[Pipeline] {$disk} public URL download failed", [
                'path' => $normalizedPath,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function cloudResumePublicUrl(string $disk, string $normalizedPath): ?string
    {
        $baseUrl = config("filesystems.disks.{$disk}.r2_public_url")
            ?: config("filesystems.disks.{$disk}.url");

        if (!$baseUrl) {
            return null;
        }

        $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        if ($baseHost && $appHost && strcasecmp($baseHost, $appHost) === 0) {
            return null;
        }

        $encodedPath = ResumeStoragePaths::encode($normalizedPath);

        return rtrim($baseUrl, '/') . '/' . $encodedPath;
    }

    private function resolveLocalResumePath(string $defaultDisk, array $pathCandidates): ?string
    {
        $directPaths = [];

        foreach ($pathCandidates as $pathCandidate) {
            $directPaths[] = storage_path('app/public/' . $pathCandidate);
            $directPaths[] = storage_path('app/' . $pathCandidate);

            foreach (array_slice($directPaths, -2) as $path) {
                if (file_exists($path) && is_readable($path)) {
                    Log::info('[Pipeline] Local file found', ['path' => $path]);
                    return $path;
                }
            }
        }

        $candidateDisks = array_values(array_unique(array_filter([$defaultDisk, 'public', 'local'])));

        foreach ($candidateDisks as $candidateDisk) {
            $driver = config("filesystems.disks.{$candidateDisk}.driver");

            if ($driver !== 'local') {
                continue;
            }

            try {
                foreach ($pathCandidates as $pathCandidate) {
                    if (!Storage::disk($candidateDisk)->exists($pathCandidate)) {
                        continue;
                    }

                    $path = Storage::disk($candidateDisk)->path($pathCandidate);

                    if (is_readable($path)) {
                        Log::info('[Pipeline] Local disk file found', [
                            'path' => $path,
                            'disk' => $candidateDisk,
                            'storage_path' => $pathCandidate,
                        ]);

                        return $path;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[Pipeline] Local disk lookup failed', [
                    'disk' => $candidateDisk,
                    'path_candidates' => $pathCandidates,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::error('[Pipeline] Resume file not found in any local location', [
            'path_candidates' => $pathCandidates,
            'default_disk' => $defaultDisk,
            'checked_paths' => $directPaths,
            'checked_disks' => $candidateDisks,
        ]);

        return null;
    }

    private function isValidPdfContent(mixed $content): bool
    {
        if (!is_string($content) || $content === '') {
            return false;
        }

        return str_starts_with(ltrim(substr($content, 0, 1024)), '%PDF');
    }

    private function buildVersionLabel(string $tier, array $rewriteResult): string
    {
        $mode   = $rewriteResult['mode'] ?? 'rule_based';
        $engine = $rewriteResult['provider'] ?? $rewriteResult['engine'] ?? 'ai';

        if ($mode === 'keyword_only') return 'Elite Preservation — Keywords Enhanced';
        if ($mode === 'selective')    return 'Selective AI Enhancement — v' . now()->format('H:i');

        return ucfirst($tier) . ' Resume — ' . ucfirst((string) $engine) . ' Optimized';
    }

    private function buildImprovementsList(array $before, array $after, array $weakness, array $quality, array $rewriteResult): array
    {
        $list = [];
        $tier = $quality['tier'] ?? 'average';
        $mode = $rewriteResult['mode'] ?? 'rule_based';

        // Quality tier context
        $tierLabels = ['elite' => '🏆 Elite', 'strong' => '⭐ Strong', 'average' => '📊 Average', 'weak' => '⚠️ Weak'];
        $list[] = "Resume Quality: {$tierLabels[$tier]} — " . ucfirst($mode) . " mode applied";

        // Score improvements
        $skillDiff = $after['skill_match'] - $before['skill_match'];
        if ($skillDiff > 0) $list[] = "+ Improved skill match by {$skillDiff}%";

        $kwDiff = $after['keyword_score'] - $before['keyword_score'];
        if ($kwDiff > 0) $list[] = "+ Increased ATS keyword coverage by {$kwDiff}%";

        // Locked sections
        $locked = $quality['locked_sections'] ?? [];
        if (!empty($locked)) {
            $list[] = "🔒 Preserved " . count($locked) . " high-quality section(s): " . implode(', ', $locked);
        }

        // Rewrites
        $weakCount = count($weakness['weak_bullets'] ?? []);
        if ($weakCount > 0 && $mode !== 'keyword_only') {
            $list[] = "+ Rewrote {$weakCount} weak bullet(s) with action verbs and technical depth";
        }

        if ($weakness['summary_weak'] ?? false) {
            $list[] = "+ Rewrote Professional Summary to target the specific role";
        }

        if (!empty($weakness['missing_skills']) && $mode !== 'keyword_only') {
            $added = implode(', ', array_slice($weakness['missing_skills'], 0, 3));
            $list[] = "+ Added missing skills to skills section: {$added}";
        }

        if ($mode === 'keyword_only') {
            $list[] = "🔒 Elite resume preserved — only injected missing ATS keywords into skills";
        }

        $engine = $rewriteResult['engine'] ?? null;
        if ($engine === 'anthropic') $list[] = "+ Optimized by Anthropic with surgical precision";
        if ($engine === 'openai')    $list[] = "+ Optimized by OpenAI for ATS compatibility";
        if ($engine === 'openrouter') $list[] = "+ Optimized by OpenRouter fallback provider";

        return $list;
    }

    private function saveOriginalSnapshot(int $userId, int $internshipId, string $content, int $score): void
    {
        $exists = ResumeVersion::where('user_id', $userId)
            ->where('internship_id', $internshipId)
            ->where('type', 'original')
            ->exists();

        if (!$exists) {
            ResumeVersion::create([
                'user_id'           => $userId,
                'internship_id'     => $internshipId,
                'version_number'    => 1,
                'label'             => 'Original',
                'content'           => $content,
                'score_at_creation' => $score,
                'type'              => 'original',
            ]);
        }
    }

    public function checkPipelineHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'dependencies' => [],
        ];

        foreach (['openai', 'anthropic', 'openrouter'] as $provider) {
            $endpoint = config("services.{$provider}.endpoint");
            $health['dependencies'][$provider] = [
                'status' => filled(config("services.{$provider}.api_key")) ? 'configured' : 'missing',
                'endpoint' => $endpoint ? parse_url($endpoint, PHP_URL_HOST) : null,
                'key_present' => filled(config("services.{$provider}.api_key")),
                'model' => config("services.{$provider}.model"),
            ];
        }

        // Check LaTeX Rendering
        $latexUrl = config('services.latexlite.url');
        $latexKey = config('services.latexlite.api_key');
        
        $health['dependencies']['latex_render'] = [
            'status' => !empty($latexUrl) ? 'configured' : 'missing',
            'endpoint' => $latexUrl ? parse_url($latexUrl, PHP_URL_HOST) : null,
            'key_present' => !empty($latexKey),
        ];

        // 3. Overall status
        foreach ($health['dependencies'] as $dep) {
            if ($dep['status'] !== 'configured' || !$dep['key_present']) {
                $health['status'] = 'degraded';
                break;
            }
        }

        return $health;
    }

    public function debugResumeAccess(User $user): array
    {
        $profile = $user->profile;
        $defaultDisk = config('filesystems.default');
        $pathCandidates = ResumeStoragePaths::candidates($profile?->resume_path);
        $diskNames = array_values(array_unique(array_filter([$defaultDisk, 's3', 'r2', 'public', 'local'])));
        $diskChecks = [];

        foreach ($diskNames as $diskName) {
            $driver = config("filesystems.disks.{$diskName}.driver");

            if (!$driver) {
                continue;
            }

            $checks = [];
            foreach ($pathCandidates as $pathCandidate) {
                $check = [
                    'path' => $pathCandidate,
                    'exists' => false,
                    'readable_pdf' => false,
                    'error' => null,
                ];

                try {
                    $check['exists'] = Storage::disk($diskName)->exists($pathCandidate);

                    if ($check['exists']) {
                        if ($driver === 'local') {
                            $path = Storage::disk($diskName)->path($pathCandidate);
                            $check['readable_pdf'] = is_readable($path)
                                && $this->isValidPdfContent(file_get_contents($path));
                        } else {
                            $check['readable_pdf'] = $this->isValidPdfContent(Storage::disk($diskName)->get($pathCandidate));
                        }
                    }
                } catch (\Throwable $e) {
                    $check['error'] = $e->getMessage();
                }

                $checks[] = $check;
            }

            $diskChecks[$diskName] = [
                'driver' => $driver,
                'is_default' => $diskName === $defaultDisk,
                'is_cloud' => $this->isCloudDisk($diskName),
                'configured' => [
                    'bucket' => (bool) config("filesystems.disks.{$diskName}.bucket"),
                    'endpoint' => (bool) config("filesystems.disks.{$diskName}.endpoint"),
                    'url' => (bool) config("filesystems.disks.{$diskName}.url"),
                    'r2_public_url' => (bool) config("filesystems.disks.{$diskName}.r2_public_url"),
                    'key' => (bool) config("filesystems.disks.{$diskName}.key"),
                    'secret' => (bool) config("filesystems.disks.{$diskName}.secret"),
                ],
                'checks' => $checks,
            ];
        }

        return [
            'patch_version' => 'resume-storage-v2-path-candidates',
            'user_id' => $user->id,
            'profile_id' => $profile?->id,
            'has_resume_path' => !empty($profile?->resume_path),
            'stored_resume_path' => $profile?->resume_path,
            'path_candidates' => $pathCandidates,
            'default_disk' => $defaultDisk,
            'disk_checks' => $diskChecks,
            'next_step' => $this->debugNextStep($diskChecks),
        ];
    }

    private function debugNextStep(array $diskChecks): string
    {
        foreach ($diskChecks as $diskName => $diskCheck) {
            foreach ($diskCheck['checks'] as $check) {
                if (($check['readable_pdf'] ?? false) === true) {
                    return "Resume is readable on disk [{$diskName}] at path [{$check['path']}]. If the modal still fails, redeploy and clear Laravel config/view caches.";
                }
            }
        }

        foreach ($diskChecks as $diskName => $diskCheck) {
            foreach ($diskCheck['checks'] as $check) {
                if (($check['exists'] ?? false) === true) {
                    return "A file exists on disk [{$diskName}], but it is not a readable PDF. Re-upload a valid text-based PDF resume.";
                }
            }
        }

        return 'No checked disk contains the resume PDF. Re-upload the resume after confirming production FILESYSTEM_DISK points to the intended persistent disk.';
    }

    private function emptyScore(string $reason = '', string $stage = 'UNKNOWN'): array
    {
        // Provide more helpful error messages based on stage
        $userMessage = match($stage) {
            'RESUME_NOT_FOUND' => 'Resume file not found. Please re-upload your resume from your profile page.',
            'RESUME_DOWNLOAD_FAILED' => $reason ?: 'Unable to access your resume file. Please re-upload your resume from your profile page.',
            'INVALID_PDF' => 'The uploaded file appears to be corrupted or is not a valid PDF. Please re-upload your resume.',
            'PARSER_FAILED' => 'Unable to extract text from your PDF. If your resume is image-based (scanned), please upload a text-based PDF instead.',
            'CRITICAL_SYSTEM_FAILURE' => 'A system error occurred. Our team has been notified. Please try again later.',
            default => $reason ?: 'Unable to analyse resume. Please ensure your resume is a valid, text-based PDF.',
        };

        return [
            'success'         => false,
            'stage_failed'    => $stage,
            'score' => 0, 'skill_match' => 0, 'keyword_score' => 0,
            'format_score' => 0, 'exp_score' => 0, 'proj_score' => 0,
            'intrinsic_score' => 0, 'overall_score' => 0,
            'matching_skills' => [], 'missing_skills' => [],
            'issues'          => [$userMessage],
            'strengths'       => [],
            'weak_areas'      => [], 'recommendations' => [], 'role_category' => 'general',
            'tier'            => 'low',
            'gate_message'    => '🔴 Analysis Failed — ' . ($stage !== 'UNKNOWN' ? "[{$stage}] " : '') . $userMessage,
            'badge_class'     => 'bg-red-100 text-red-800',
            'resume_text'     => '',
            'quality_tier'    => 'unknown', 'quality_score' => 0,
            'section_scores'  => [], 'locked_sections' => [], 'preservation_mode' => false,
        ];
    }
}
