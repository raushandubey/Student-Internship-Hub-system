<?php

namespace App\Services;

use App\Models\Internship;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;

/**
 * InternshipService
 * 
 * Handles all business logic related to internships and recommendations.
 * 
 * Why Service Layer?
 * - Recommendation logic is complex and should be isolated
 * - Can be reused across Web, API, and scheduled jobs
 * - Easy to modify algorithm without touching controllers
 */
class InternshipService
{
    /**
     * Get active internships with optional filtering
     */
    public function getActiveInternships(array $filters = [])
    {
        $query = Internship::where('is_active', true);

        if (!empty($filters['organization'])) {
            $query->where('organization', 'like', '%' . $filters['organization'] . '%');
        }

        if (!empty($filters['location'])) {
            $query->where('location', 'like', '%' . $filters['location'] . '%');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get skill-based recommendations for a user profile
     * 
     * Algorithm (Rule-Based, NOT AI):
     * 1. Normalize user skills and internship requirements
     * 2. Calculate skill overlap using array intersection
     * 3. Compute similarity score: matching_skills / required_skills
     * 4. Add academic background bonus if keywords match
     * 5. Sort by score and return top results
     */
    public function getRecommendations(Profile $profile, int $limit = 10): array
    {
        if (empty($profile->skills)) {
            return [];
        }

        $internships = Internship::where('is_active', true)->get();

        if ($internships->isEmpty()) {
            return [];
        }

        // Normalize user skills
        $userSkills = $this->normalizeSkills($profile->skills);

        if (empty($userSkills)) {
            return [];
        }

        $recommendations = [];

        foreach ($internships as $internship) {
            if (empty($internship->required_skills)) {
                continue;
            }

            $requiredSkills = $this->normalizeSkills($internship->required_skills);

            if (empty($requiredSkills)) {
                continue;
            }

            // Calculate skill match
            $matchingSkills = array_intersect($userSkills, $requiredSkills);
            $score = count($requiredSkills) > 0
                ? count($matchingSkills) / count($requiredSkills)
                : 0;

            // Academic background bonus
            if (!empty($profile->academic_background)) {
                $academicKeywords = array_filter(explode(' ', strtolower($profile->academic_background)));
                $titleKeywords = array_filter(explode(' ', strtolower($internship->title)));
                if (count(array_intersect($academicKeywords, $titleKeywords)) > 0) {
                    $score += 0.2;
                }
            }

            if ($score > 0) {
                $recommendations[] = [
                    'internship' => $internship,
                    'score' => round($score, 2),
                    'matching_skills' => array_values($matchingSkills)
                ];
            }
        }

        // Sort by score (highest first)
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Normalize skills array for comparison
     */
    private function normalizeSkills($skills): array
    {
        if (is_array($skills)) {
            return array_map('trim', array_map('strtolower', $skills));
        }

        return array_filter(
            array_map('trim', array_map('strtolower', explode(',', $skills)))
        );
    }

    /**
     * Get internship statistics for admin dashboard
     */
    public function getStats(): array
    {
        return [
            'total' => Internship::count(),
            'active' => Internship::where('is_active', true)->count(),
            'inactive' => Internship::where('is_active', false)->count(),
        ];
    }
}
