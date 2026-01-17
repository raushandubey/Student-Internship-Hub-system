<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Services\MatchingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * RecommendationController
 * 
 * Uses MatchingService for skill-based matching between students and internships.
 * Phase 8: Enhanced with match confidence badges and "why recommended" explanations.
 */
class RecommendationController extends Controller
{
    protected MatchingService $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    public function index()
    {
        $user = Auth::user();
        $profile = $user->profile;

        // Profile check
        if (!$profile || empty($profile->skills)) {
            return view('recommendations.index', [
                'recommendations' => [],
                'message' => '⚠️ Please complete your profile with skills to get recommendations.'
            ]);
        }

        // Get recommendations using MatchingService
        $recommendations = $this->matchingService->getRecommendations($user, 10);

        // Transform for view with confidence badges and explanations
        $viewRecommendations = array_map(function ($rec) {
            $percentage = $rec['match']['percentage'];
            
            return [
                'internship' => $rec['internship'],
                'score' => $rec['match']['score'],
                'matching_skills' => $rec['match']['matching_skills'],
                'missing_skills' => $rec['match']['missing_skills'],
                'percentage' => $percentage,
                // Phase 8: Match confidence badge
                'confidence' => $this->getConfidenceBadge($percentage),
                // Phase 8: Why recommended explanation
                'why_recommended' => $this->getWhyRecommended($rec['match']),
            ];
        }, $recommendations);

        // Sort by match score DESC, then by recent (already sorted by MatchingService)
        Log::info('Recommendations generated', [
            'user_id' => $user->id,
            'count' => count($viewRecommendations)
        ]);

        return view('recommendations.index', [
            'recommendations' => $viewRecommendations,
            'message' => empty($viewRecommendations) 
                ? 'No matching internships found. Try updating your skills or check back later for new opportunities.' 
                : null,
            'debug' => [
                'total_internships' => Internship::count(),
                'active_internships' => Internship::where('is_active', true)->count(),
                'user_skills' => $profile->skills,
                'recommendations_found' => count($viewRecommendations)
            ]
        ]);
    }

    /**
     * Get confidence badge based on match percentage
     */
    private function getConfidenceBadge(int $percentage): array
    {
        if ($percentage >= 80) {
            return ['level' => 'excellent', 'label' => 'Excellent Match', 'color' => 'green'];
        }
        if ($percentage >= 60) {
            return ['level' => 'good', 'label' => 'Good Match', 'color' => 'blue'];
        }
        if ($percentage >= 40) {
            return ['level' => 'fair', 'label' => 'Fair Match', 'color' => 'yellow'];
        }
        return ['level' => 'low', 'label' => 'Low Match', 'color' => 'gray'];
    }

    /**
     * Generate "why recommended" explanation
     */
    private function getWhyRecommended(array $match): string
    {
        $reasons = [];
        
        if (!empty($match['matching_skills'])) {
            $topSkills = array_slice($match['matching_skills'], 0, 3);
            $reasons[] = 'Matched: ' . implode(', ', array_map('ucfirst', $topSkills));
        }
        
        if (!empty($match['missing_skills'])) {
            $topMissing = array_slice($match['missing_skills'], 0, 2);
            $reasons[] = 'Learn: ' . implode(', ', array_map('ucfirst', $topMissing));
        }
        
        return implode(' | ', $reasons);
    }
}
