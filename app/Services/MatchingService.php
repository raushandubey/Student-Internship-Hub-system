<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Internship;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * MatchingService
 * 
 * Handles skill-based matching between students and internships.
 * 
 * Algorithm Design:
 * - Core skills (first 3) have 2x weight
 * - Optional skills (rest) have 1x weight
 * - Academic background bonus: +10% if keywords match
 * 
 * Dynamic Recommendations (Phase 8 Enhancement):
 * - Excludes internships the student has already applied to
 * - Falls back to additional internships if matches < limit
 * - Cached per user for 5 minutes
 * 
 * Why separate service?
 * - Single Responsibility: matching logic isolated
 * - Testable: can unit test matching algorithm
 * - Reusable: used by recommendations, applications, analytics
 */
class MatchingService
{
    /** Cache TTL for recommendations (5 minutes) */
    private const CACHE_TTL = 300;
    
    /** Cache key prefix */
    private const CACHE_PREFIX = 'recommendations_';

    /**
     * Calculate match score between a user profile and internship
     * 
     * @return array{score: float, matching_skills: array, missing_skills: array}
     */
    public function calculateMatch(Profile $profile, Internship $internship): array
    {
        $userSkills = $this->normalizeSkills($profile->skills ?? []);
        $requiredSkills = $this->normalizeSkills($internship->required_skills ?? []);

        if (empty($requiredSkills)) {
            return [
                'score' => 0,
                'percentage' => 0,
                'matching_skills' => [],
                'missing_skills' => [],
            ];
        }

        // Split into core (first 3) and optional skills
        $coreSkills = array_slice($requiredSkills, 0, 3);
        $optionalSkills = array_slice($requiredSkills, 3);

        // Calculate weighted matches
        $coreMatches = array_intersect($userSkills, $coreSkills);
        $optionalMatches = array_intersect($userSkills, $optionalSkills);

        // Weighted scoring: core skills = 2 points, optional = 1 point
        $coreWeight = 2;
        $optionalWeight = 1;

        $maxScore = (count($coreSkills) * $coreWeight) + (count($optionalSkills) * $optionalWeight);
        $actualScore = (count($coreMatches) * $coreWeight) + (count($optionalMatches) * $optionalWeight);

        $baseScore = $maxScore > 0 ? ($actualScore / $maxScore) : 0;

        // Academic background bonus (+10%)
        $academicBonus = 0;
        if (!empty($profile->academic_background) && !empty($internship->title)) {
            $academicKeywords = $this->extractKeywords($profile->academic_background);
            $titleKeywords = $this->extractKeywords($internship->title);
            if (count(array_intersect($academicKeywords, $titleKeywords)) > 0) {
                $academicBonus = 0.10;
            }
        }

        // Final score (capped at 1.0)
        $finalScore = min(1.0, $baseScore + $academicBonus);

        // Calculate missing skills
        $allMatches = array_merge($coreMatches, $optionalMatches);
        $missingSkills = array_diff($requiredSkills, $allMatches);

        return [
            'score' => round($finalScore, 4),
            'percentage' => round($finalScore * 100),
            'matching_skills' => array_values($allMatches),
            'missing_skills' => array_values($missingSkills),
            'core_match_count' => count($coreMatches),
            'total_core' => count($coreSkills),
        ];
    }

    /**
     * Get ranked recommendations for a user (DYNAMIC - excludes applied internships)
     * 
     * WHY EXCLUDE APPLIED INTERNSHIPS?
     * - Students shouldn't see internships they've already applied to
     * - Creates a realistic job board experience
     * - Encourages exploring new opportunities
     * 
     * WHY FALLBACK LOGIC?
     * - If skill-matched internships < limit, we still want to show LIMIT items
     * - Fallback fetches additional internships ordered by recency
     * - Ensures the recommendations page is never empty
     * 
     * WHY THIS IS NOT AI?
     * - Uses simple array intersection for skill matching
     * - Uses database queries with whereNotIn() for exclusion
     * - No machine learning, neural networks, or predictive algorithms
     * 
     * WHY THIS IS SCALABLE?
     * - Cached per user for 5 minutes (reduces DB queries)
     * - Uses indexed columns (is_active, id)
     * - Excludes applied IDs in single query (not N+1)
     * 
     * @param User $user The authenticated user
     * @param int $limit Maximum recommendations to return (default 10)
     * @return array Ranked recommendations with match data
     */
    public function getRecommendations(User $user, int $limit = 10): array
    {
        // Use cached recommendations if available
        return Cache::remember(
            self::CACHE_PREFIX . $user->id,
            self::CACHE_TTL,
            fn() => $this->buildRecommendations($user, $limit)
        );
    }

    /**
     * Build recommendations (called by cache)
     */
    private function buildRecommendations(User $user, int $limit): array
    {
        $profile = $user->profile;
        
        if (!$profile || empty($profile->skills)) {
            return [];
        }

        // Step 1: Get IDs of internships the user has already applied to
        // WHY? To exclude them from recommendations - no duplicates
        $appliedInternshipIds = Application::where('user_id', $user->id)
            ->pluck('internship_id')
            ->toArray();

        // Step 2: Fetch active internships EXCLUDING applied ones
        // WHY whereNotIn? Single query exclusion, not N+1
        $internships = Internship::where('is_active', true)
            ->whereNotIn('id', $appliedInternshipIds)
            ->get();

        // Step 3: Calculate match scores for each internship
        $recommendations = [];
        foreach ($internships as $internship) {
            $match = $this->calculateMatch($profile, $internship);
            
            if ($match['score'] > 0) {
                $recommendations[] = [
                    'internship' => $internship,
                    'match' => $match,
                ];
            }
        }

        // Step 4: Sort by score DESC (best matches first)
        usort($recommendations, fn($a, $b) => $b['match']['score'] <=> $a['match']['score']);

        // Step 5: If we have enough matches, return them
        if (count($recommendations) >= $limit) {
            return array_slice($recommendations, 0, $limit);
        }

        // Step 6: FALLBACK - If matches < limit, add more internships
        // WHY? To always show LIMIT items, even if skill matches are few
        $matchedIds = array_map(fn($r) => $r['internship']->id, $recommendations);
        $excludeIds = array_merge($appliedInternshipIds, $matchedIds);
        
        $remaining = $limit - count($recommendations);
        
        $fallbackInternships = Internship::where('is_active', true)
            ->whereNotIn('id', $excludeIds)
            ->orderBy('created_at', 'desc') // Most recent first
            ->limit($remaining)
            ->get();

        // Add fallback internships with zero score (they're still valid opportunities)
        foreach ($fallbackInternships as $internship) {
            $match = $this->calculateMatch($profile, $internship);
            $recommendations[] = [
                'internship' => $internship,
                'match' => $match,
            ];
        }

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Calculate match score for an application (to store in DB)
     */
    public function calculateApplicationScore(User $user, Internship $internship): float
    {
        $profile = $user->profile;
        
        if (!$profile) {
            return 0;
        }

        $match = $this->calculateMatch($profile, $internship);
        return $match['score'] * 100; // Store as percentage
    }

    /**
     * Clear recommendations cache for a user
     * 
     * Called when:
     * - User submits an application (new exclusion needed)
     * - User cancels an application (internship should reappear)
     */
    public static function clearCache(int $userId): void
    {
        Cache::forget(self::CACHE_PREFIX . $userId);
    }

    /**
     * Normalize skills array for comparison
     */
    private function normalizeSkills(array|string|null $skills): array
    {
        if (empty($skills)) {
            return [];
        }

        if (is_string($skills)) {
            $skills = explode(',', $skills);
        }

        return array_filter(
            array_map(fn($s) => strtolower(trim($s)), $skills)
        );
    }

    /**
     * Extract keywords from text
     */
    private function extractKeywords(string $text): array
    {
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'in', 'at', 'for', 'to', 'of'];
        $words = preg_split('/\s+/', strtolower($text));
        
        return array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
    }
}
