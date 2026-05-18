<?php

namespace App\Services\Resume;

use Illuminate\Support\Facades\Log;

/**
 * Extracts and validates candidate identity to prevent role corruption during optimization.
 *
 * API Contract for buildContext():
 * return [
 *   'optimization_mode' => string,  // 'standard' or 'identity_preserving'
 *   'cross_domain'      => bool,
 *   'identity'          => [
 *     'candidate_type'   => string,
 *     'role_category'    => string,
 *     'seniority'        => string,  // 'junior', 'mid', 'senior', 'lead', 'principal'
 *     'forbidden_domains'=> array,
 *   ]
 * ];
 *
 * API Contract for validate():
 * return [
 *   'valid'      => bool,
 *   'violations' => array<string>
 * ];
 */
class IdentityPreservationEngine
{
    /**
     * Forbidden domain transition pairs: [from_domain => [forbidden_target_domains]]
     * Engineering roles must not be rewritten as design/marketing/HR roles.
     */
    private const FORBIDDEN_TRANSITIONS = [
        'backend'   => ['design', 'hr', 'marketing'],
        'frontend'  => ['hr', 'marketing'],
        'fullstack' => ['design', 'hr', 'marketing'],
        'devops'    => ['design', 'hr', 'marketing'],
        'data'      => ['design', 'hr', 'marketing'],
        'mobile'    => ['design', 'hr', 'marketing'],
    ];

    /**
     * Seniority signals mapped to seniority level.
     * Checked in order — first match wins.
     */
    private const SENIORITY_SIGNALS = [
        'principal' => ['principal', 'distinguished', 'fellow'],
        'lead'      => ['lead ', 'tech lead', 'team lead', 'engineering lead', 'staff engineer'],
        'senior'    => ['senior', 'sr.', 'sr ', 'architect'],
        'mid'       => ['mid-level', 'mid level', 'intermediate'],
        'junior'    => ['junior', 'jr.', 'jr ', 'entry level', 'entry-level', 'associate', 'intern'],
    ];

    public function __construct(
        private ?ResumeDomainClassifier $classifier = null,
    ) {}

    private function classifier(): ResumeDomainClassifier
    {
        return $this->classifier ??= new ResumeDomainClassifier();
    }

    /**
     * Detect seniority level from resume text and experience entries.
     * Returns one of: 'junior', 'mid', 'senior', 'lead', 'principal'
     */
    private function detectSeniority(array $parsedResume): string
    {
        $corpus = strtolower(
            ($parsedResume['raw_text'] ?? '') . ' ' .
            ($parsedResume['summary'] ?? '') . ' ' .
            implode(' ', array_map(
                fn($exp) => ($exp['title'] ?? ''),
                $parsedResume['experience'] ?? []
            ))
        );

        // Check explicit seniority signals in order of precedence
        foreach (self::SENIORITY_SIGNALS as $level => $signals) {
            foreach ($signals as $signal) {
                if (str_contains($corpus, $signal)) {
                    return $level;
                }
            }
        }

        // Infer from experience count when no explicit signal found
        $expCount = count($parsedResume['experience'] ?? []);
        if ($expCount >= 4) {
            return 'senior';
        }
        if ($expCount >= 2) {
            return 'mid';
        }

        return 'junior';
    }

    /**
     * Detect domain specialization from resume content.
     * Returns one of: 'backend', 'frontend', 'fullstack', 'devops', 'data', 'mobile', 'design', 'general'
     */
    private function detectDomainSpecialization(array $parsedResume): string
    {
        $classified = $this->classifier()->classify($parsedResume);
        return $classified['role_category'] ?? 'general';
    }

    /**
     * Extract career trajectory pattern from experience titles.
     * Returns 'ascending', 'lateral', or 'stable'.
     */
    private function detectCareerTrajectory(array $parsedResume): string
    {
        $titles = array_map(
            fn($exp) => strtolower(trim((string) ($exp['title'] ?? ''))),
            $parsedResume['experience'] ?? []
        );

        if (count($titles) < 2) {
            return 'stable';
        }

        $seniorityOrder = ['intern' => 0, 'junior' => 1, 'associate' => 1, 'mid' => 2, 'senior' => 3, 'lead' => 4, 'principal' => 5, 'architect' => 4, 'staff' => 4];
        $levels = [];

        foreach ($titles as $title) {
            foreach ($seniorityOrder as $keyword => $level) {
                if (str_contains($title, $keyword)) {
                    $levels[] = $level;
                    break;
                }
            }
        }

        if (count($levels) < 2) {
            return 'stable';
        }

        // Most recent experience is first in the array
        $first = $levels[0];
        $last  = $levels[count($levels) - 1];

        if ($first > $last) {
            return 'ascending';
        }
        if ($first < $last) {
            return 'lateral';
        }

        return 'stable';
    }

    /**
     * @return array<string, mixed>
     */
    public function extract(array $parsedResume): array
    {
        $classified = $this->classifier()->classify($parsedResume);

        // Map 'experience_level' from classifier to the required 'seniority' key
        // and enhance with our own more robust seniority detection
        $seniority = $this->detectSeniority($parsedResume);

        // Build the identity object conforming to the design API contract
        $identity = [
            'candidate_type'     => $classified['candidate_type'],
            'role_category'      => $classified['role_category'],
            'seniority'          => $seniority,
            'forbidden_domains'  => $classified['forbidden_domains'],
            // Additional enrichment fields (not part of core contract but useful downstream)
            'primary_domain'     => $classified['primary_domain'],
            'core_stack'         => $classified['core_stack'],
            'architecture_depth' => $classified['architecture_depth'],
            'confidence'         => $classified['confidence'],
            'career_trajectory'  => $this->detectCareerTrajectory($parsedResume),
            'optimization_mode'  => 'standard',
        ];

        return $identity;
    }

    /**
     * Build optimization context including identity, mode, and cross-domain flag.
     *
     * @return array{
     *   optimization_mode: string,
     *   cross_domain: bool,
     *   identity: array{
     *     candidate_type: string,
     *     role_category: string,
     *     seniority: string,
     *     forbidden_domains: array<int, string>
     *   }
     * }
     */
    public function buildContext(array $parsedResume, array $jdAnalysis): array
    {
        $identity    = $this->extract($parsedResume);
        $crossDomain = $this->classifier()->isCrossDomain($identity, $jdAnalysis);

        $optimizationMode = $crossDomain ? 'identity_preserving' : 'standard';
        $identity['optimization_mode'] = $optimizationMode;

        return [
            'optimization_mode' => $optimizationMode,
            'cross_domain'      => $crossDomain,
            'identity'          => [
                'candidate_type'    => $identity['candidate_type'],
                'role_category'     => $identity['role_category'],
                'seniority'         => $identity['seniority'],
                'forbidden_domains' => $identity['forbidden_domains'],
                // Pass through enrichment fields for downstream use
                'primary_domain'    => $identity['primary_domain'],
                'core_stack'        => $identity['core_stack'],
                'architecture_depth'=> $identity['architecture_depth'],
                'confidence'        => $identity['confidence'],
                'career_trajectory' => $identity['career_trajectory'],
                'optimization_mode' => $optimizationMode,
            ],
        ];
    }

    /**
     * Validate that the optimized resume preserves the candidate's professional identity.
     * Acts as a hard gate — violations must block the AI rewrite pipeline.
     *
     * @return array{valid: bool, violations: array<int, string>}
     */
    public function validate(array $identity, array $optimized, array $original): array
    {
        $violations    = [];
        $summary       = strtolower((string) ($optimized['summary'] ?? ''));
        $candidateType = strtolower((string) ($identity['candidate_type'] ?? ''));
        $roleCategory  = strtolower((string) ($identity['role_category'] ?? ''));

        // ── Rule 1: Forbidden domain labels must not appear in summary ────
        foreach ($identity['forbidden_domains'] ?? [] as $forbidden) {
            $forbiddenLower = strtolower($forbidden);
            if ($forbiddenLower !== '' && str_contains($summary, $forbiddenLower)) {
                $violations[] = "Summary introduces forbidden domain label: {$forbidden}";
            }
        }

        // ── Rule 2: Summary must not replace candidate domain identity ────
        if ($this->summaryReplacesDomain($summary, $candidateType, $original)) {
            $violations[] = 'Summary replaced candidate domain identity with JD role persona';
        }

        // ── Rule 3: Forbidden domain transitions (engineering → design) ───
        $forbiddenTargets = self::FORBIDDEN_TRANSITIONS[$roleCategory] ?? [];
        if (!empty($forbiddenTargets)) {
            foreach ($forbiddenTargets as $forbiddenTarget) {
                if ($this->summaryIndicatesDomain($summary, $forbiddenTarget)) {
                    $violations[] = "Summary indicates forbidden domain transition: {$roleCategory} → {$forbiddenTarget}";
                }
            }
        }

        // ── Rule 4: Experience title consistency ──────────────────────────
        $originalTitles  = $this->experienceTitles($original);
        $optimizedTitles = $this->experienceTitles($optimized);

        foreach ($originalTitles as $idx => $title) {
            if ($title === '') {
                continue;
            }
            $newTitle = $optimizedTitles[$idx] ?? $title;
            if ($newTitle !== '' && $this->domainShifted($title, $newTitle, $identity)) {
                $violations[] = "Experience title corrupted: \"{$title}\" → \"{$newTitle}\"";
            }
        }

        // ── Rule 5: Summary alignment with original identity ──────────────
        if ($this->summaryLosesEngineeringIdentity($summary, $candidateType, $original)) {
            $violations[] = 'Summary lost engineering identity markers present in original resume';
        }

        // ── Log identity corruption attempts for monitoring ───────────────
        if (!empty($violations)) {
            Log::warning('[IdentityPreservationEngine] IDENTITY_CORRUPTION_DETECTED', [
                'candidate_type'  => $identity['candidate_type'] ?? 'unknown',
                'role_category'   => $identity['role_category'] ?? 'unknown',
                'seniority'       => $identity['seniority'] ?? 'unknown',
                'violation_count' => count($violations),
                'violations'      => $violations,
            ]);
        }

        return [
            'valid'      => empty($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Check if the optimized summary indicates a transition to a forbidden domain.
     */
    private function summaryIndicatesDomain(string $summary, string $targetDomain): bool
    {
        $domainKeywords = [
            'design'    => ['designer', 'ui/ux', 'ux designer', 'ui designer', 'visual design', 'graphic design'],
            'hr'        => ['human resources', 'hr manager', 'talent acquisition', 'recruitment'],
            'marketing' => ['marketing', 'digital marketing', 'content marketing', 'seo specialist'],
        ];

        $keywords = $domainKeywords[$targetDomain] ?? [];
        foreach ($keywords as $keyword) {
            if (str_contains($summary, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function summaryReplacesDomain(string $optimizedSummary, string $candidateType, array $original): bool
    {
        $originalSummary = strtolower(trim((string) ($original['summary'] ?? '')));
        if ($originalSummary === '' || strlen($originalSummary) < 40) {
            return false;
        }

        $engineeringTypes = ['backend engineer', 'frontend engineer', 'full stack engineer', 'software engineer', 'devops engineer'];
        $designTypes      = ['ui/ux designer', 'designer'];

        $wasEngineering = false;
        foreach ($engineeringTypes as $type) {
            if (str_contains($originalSummary, $type) || str_contains($candidateType, explode(' ', $type)[0])) {
                $wasEngineering = true;
                break;
            }
        }

        if (!$wasEngineering) {
            return false;
        }

        foreach ($designTypes as $design) {
            if (str_contains($optimizedSummary, $design) && !str_contains($originalSummary, $design)) {
                return true;
            }
        }

        if (str_contains($optimizedSummary, 'ui/ux designer') && str_contains($candidateType, 'engineer')) {
            return true;
        }

        return false;
    }

    /**
     * Check if the optimized summary lost engineering identity markers that were in the original.
     */
    private function summaryLosesEngineeringIdentity(string $optimizedSummary, string $candidateType, array $original): bool
    {
        $originalSummary = strtolower(trim((string) ($original['summary'] ?? '')));
        if ($originalSummary === '' || strlen($originalSummary) < 40) {
            return false;
        }

        // Only check engineering candidates
        if (!str_contains($candidateType, 'engineer') && !str_contains($candidateType, 'developer')) {
            return false;
        }

        $engineeringMarkers = ['engineer', 'developer', 'backend', 'frontend', 'full stack', 'fullstack', 'devops', 'software'];

        $originalHasMarker  = false;
        $optimizedHasMarker = false;

        foreach ($engineeringMarkers as $marker) {
            if (str_contains($originalSummary, $marker)) {
                $originalHasMarker = true;
            }
            if (str_contains($optimizedSummary, $marker)) {
                $optimizedHasMarker = true;
            }
        }

        // Violation only if original had engineering markers but optimized lost all of them
        return $originalHasMarker && !$optimizedHasMarker;
    }

    private function domainShifted(string $originalTitle, string $newTitle, array $identity): bool
    {
        $orig = strtolower($originalTitle);
        $new  = strtolower($newTitle);

        if ($orig === $new) {
            return false;
        }

        $engineering  = preg_match('/\b(engineer|developer|architect|backend|frontend|full[\s-]?stack)\b/i', $orig);
        $becameDesign = preg_match('/\b(designer|ui|ux|graphic)\b/i', $new);

        if ($engineering && $becameDesign) {
            $resumeDomain = $identity['role_category'] ?? '';
            if (in_array($resumeDomain, ['backend', 'frontend', 'fullstack', 'devops', 'data'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function experienceTitles(array $parsed): array
    {
        $titles = [];
        foreach ($parsed['experience'] ?? [] as $exp) {
            $titles[] = trim((string) ($exp['title'] ?? ''));
        }

        return $titles;
    }
}
