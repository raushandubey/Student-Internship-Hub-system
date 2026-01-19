<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * StudentAnalyticsService
 * 
 * Provides intelligent career analytics for students.
 * All data derived from applications, internships, and status logs.
 * 
 * Features:
 * - Skill strength/weakness analysis
 * - Application outcome statistics
 * - Career readiness scoring
 * - Timeline predictions
 */
class StudentAnalyticsService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'student_analytics_';

    /**
     * Get skill strengths (most matched skills across applications)
     * 
     * PRODUCTION SAFETY: Always returns consistent structure to prevent
     * "Undefined array key" errors in dependent methods.
     */
    public function getSkillStrengths(int $userId): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . "strengths_{$userId}",
            self::CACHE_TTL,
            function () use ($userId) {
                // Defensive: Initialize default structure first
                $defaultResult = [
                    'strongest' => null,
                    'skills' => [],
                    'total_applications' => 0,
                ];

                $user = User::with('profile')->find($userId);
                if (!$user || !$user->profile || empty($user->profile->skills)) {
                    return $defaultResult; // Return consistent structure
                }

                $userSkills = $this->normalizeSkills($user->profile->skills);
                $skillMatches = [];

                // Get all applications with internship data
                $applications = Application::with('internship')
                    ->where('user_id', $userId)
                    ->get();

                foreach ($applications as $app) {
                    if (!$app->internship || empty($app->internship->required_skills)) {
                        continue;
                    }
                    
                    $requiredSkills = $this->normalizeSkills($app->internship->required_skills);
                    $matched = array_intersect($userSkills, $requiredSkills);
                    
                    foreach ($matched as $skill) {
                        $skillMatches[$skill] = ($skillMatches[$skill] ?? 0) + 1;
                    }
                }

                arsort($skillMatches);
                $topSkills = array_slice($skillMatches, 0, 5, true);

                // Always return complete structure
                return [
                    'strongest' => !empty($topSkills) ? array_key_first($topSkills) : null,
                    'skills' => $topSkills,
                    'total_applications' => $applications->count(),
                ];
            }
        );
    }

    /**
     * Get skill gaps (most frequently missing skills)
     * 
     * PRODUCTION SAFETY: Always returns consistent structure to prevent
     * "Undefined array key" errors in dependent methods (getCareerReadinessScore).
     * Even when no gaps exist, returns total_gaps: 0 and empty arrays.
     */
    public function getSkillGaps(int $userId): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . "gaps_{$userId}",
            self::CACHE_TTL,
            function () use ($userId) {
                // Defensive: Initialize default structure first
                $defaultResult = [
                    'weakest' => null,
                    'gaps' => [],
                    'total_gaps' => 0, // CRITICAL: Always present for readiness calculation
                ];

                $user = User::with('profile')->find($userId);
                if (!$user || !$user->profile) {
                    return $defaultResult; // Return consistent structure
                }

                $userSkills = $this->normalizeSkills($user->profile->skills ?? []);
                $skillGaps = [];

                $applications = Application::with('internship')
                    ->where('user_id', $userId)
                    ->get();

                foreach ($applications as $app) {
                    if (!$app->internship || empty($app->internship->required_skills)) {
                        continue;
                    }
                    
                    $requiredSkills = $this->normalizeSkills($app->internship->required_skills);
                    $missing = array_diff($requiredSkills, $userSkills);
                    
                    foreach ($missing as $skill) {
                        $skillGaps[$skill] = ($skillGaps[$skill] ?? 0) + 1;
                    }
                }

                arsort($skillGaps);
                $topGaps = array_slice($skillGaps, 0, 5, true);

                // Always return complete structure
                return [
                    'weakest' => !empty($topGaps) ? array_key_first($topGaps) : null,
                    'gaps' => $topGaps,
                    'total_gaps' => count($skillGaps), // Total unique skills missing
                ];
            }
        );
    }

    /**
     * Get application outcome statistics
     * 
     * PRODUCTION SAFETY: Always returns consistent structure with all keys present.
     * Prevents undefined key errors when dashboard accesses statistics.
     */
    public function getApplicationOutcomeStats(int $userId): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . "outcomes_{$userId}",
            self::CACHE_TTL,
            function () use ($userId) {
                $applications = Application::where('user_id', $userId)->get();
                
                $total = $applications->count();
                $approved = $applications->where('status.value', 'approved')->count();
                $rejected = $applications->where('status.value', 'rejected')->count();
                $pending = $applications->whereIn('status.value', ['pending', 'under_review', 'shortlisted', 'interview_scheduled'])->count();

                // Defensive: Use null coalescing for safe calculation
                $avgMatchScore = $applications->avg('match_score') ?? 0;
                $highMatchApps = $applications->where('match_score', '>=', 70)->count();

                // Always return complete structure with all expected keys
                return [
                    'total' => $total,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'pending' => $pending,
                    'success_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
                    'avg_match_score' => round($avgMatchScore, 1),
                    'high_match_applications' => $highMatchApps,
                ];
            }
        );
    }

    /**
     * Calculate Career Readiness Score (0-100)
     * 
     * Factors:
     * - Profile completeness (25%)
     * - Average match score (25%)
     * - Application success rate (25%)
     * - Skill coverage (25%)
     */
    public function getCareerReadinessScore(int $userId): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . "readiness_{$userId}",
            self::CACHE_TTL,
            function () use ($userId) {
                $user = User::with('profile')->find($userId);
                $profile = $user?->profile;

                // Factor 1: Profile Completeness (25 points)
                $profileScore = 0;
                if ($profile) {
                    $fields = ['name', 'academic_background', 'skills', 'career_interests', 'resume_path', 'aadhaar_number'];
                    $completed = 0;
                    foreach ($fields as $field) {
                        if (!empty($profile->$field)) $completed++;
                    }
                    $profileScore = ($completed / count($fields)) * 25;
                }

                // Factor 2: Average Match Score (25 points)
                $outcomes = $this->getApplicationOutcomeStats($userId);
                $matchScore = ($outcomes['avg_match_score'] / 100) * 25;

                // Factor 3: Success Rate (25 points)
                $successScore = ($outcomes['success_rate'] / 100) * 25;

                // Factor 4: Skill Coverage (25 points)
                // Defensive: Use null coalescing to safely access array keys
                $strengths = $this->getSkillStrengths($userId);
                $gaps = $this->getSkillGaps($userId);
                
                // PRODUCTION SAFETY: Use defaults to prevent undefined key errors
                $totalStrengths = count($strengths['skills'] ?? []);
                $totalGaps = $gaps['total_gaps'] ?? 0; // Safe access with default
                
                $skillCoverage = $totalStrengths + $totalGaps > 0 
                    ? ($totalStrengths / ($totalStrengths + $totalGaps)) * 25 
                    : 12.5; // Default to 50% if no data

                $totalScore = round($profileScore + $matchScore + $successScore + $skillCoverage);

                return [
                    'score' => min(100, max(0, $totalScore)),
                    'breakdown' => [
                        'profile_completeness' => round($profileScore, 1),
                        'match_quality' => round($matchScore, 1),
                        'success_rate' => round($successScore, 1),
                        'skill_coverage' => round($skillCoverage, 1),
                    ],
                    'improvements' => $this->getImprovementSuggestions($profileScore, $matchScore, $successScore, $skillCoverage),
                    'level' => $this->getReadinessLevel($totalScore),
                ];
            }
        );
    }

    /**
     * Get improvement suggestions based on scores
     */
    private function getImprovementSuggestions(float $profile, float $match, float $success, float $skills): array
    {
        $suggestions = [];
        
        if ($profile < 20) {
            $suggestions[] = ['type' => 'profile', 'message' => 'Complete your profile to improve visibility', 'impact' => 'high'];
        }
        if ($match < 15) {
            $suggestions[] = ['type' => 'skills', 'message' => 'Add more relevant skills to improve match scores', 'impact' => 'high'];
        }
        if ($skills < 15) {
            $suggestions[] = ['type' => 'learning', 'message' => 'Learn skills frequently required by employers', 'impact' => 'medium'];
        }
        if ($success < 10 && $success > 0) {
            $suggestions[] = ['type' => 'targeting', 'message' => 'Apply to internships with higher match scores', 'impact' => 'medium'];
        }

        return $suggestions;
    }

    /**
     * Get readiness level label
     */
    private function getReadinessLevel(float $score): string
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Developing';
        return 'Getting Started';
    }

    /**
     * Get all analytics for dashboard (single call)
     * 
     * Phase 10: Feature flag support
     * Returns null for disabled features instead of throwing errors
     */
    public function getDashboardAnalytics(int $userId): array
    {
        // Feature flag check: Career Intelligence
        if (!config('features.career_intelligence_enabled', true)) {
            return [
                'strengths' => ['strongest' => null, 'skills' => [], 'total_applications' => 0],
                'gaps' => ['weakest' => null, 'gaps' => [], 'total_gaps' => 0],
                'outcomes' => ['total' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0, 'success_rate' => 0, 'avg_match_score' => 0, 'high_match_applications' => 0],
                'readiness' => ['score' => 0, 'breakdown' => [], 'improvements' => [], 'level' => 'N/A'],
                'feature_disabled' => true,
            ];
        }

        return [
            'strengths' => $this->getSkillStrengths($userId),
            'gaps' => $this->getSkillGaps($userId),
            'outcomes' => $this->getApplicationOutcomeStats($userId),
            'readiness' => $this->getCareerReadinessScore($userId),
        ];
    }

    /**
     * Clear cache for a user (called on application changes)
     */
    public static function clearCache(int $userId): void
    {
        Cache::forget(self::CACHE_PREFIX . "strengths_{$userId}");
        Cache::forget(self::CACHE_PREFIX . "gaps_{$userId}");
        Cache::forget(self::CACHE_PREFIX . "outcomes_{$userId}");
        Cache::forget(self::CACHE_PREFIX . "readiness_{$userId}");
    }

    /**
     * Normalize skills for comparison
     */
    private function normalizeSkills(array|string|null $skills): array
    {
        if (empty($skills)) return [];
        if (is_string($skills)) $skills = explode(',', $skills);
        return array_filter(array_map(fn($s) => strtolower(trim($s)), $skills));
    }
}
