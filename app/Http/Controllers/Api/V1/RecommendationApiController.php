<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InternshipResource;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecommendationApiController extends Controller
{
    protected MatchingService $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * GET /api/v1/recommendations
     * Fetch skill-matched recommendations for the authenticated student
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Recommendations are only available for students.'
            ], 403);
        }

        $profile = $user->profile;

        if (!$profile || empty($profile->skills)) {
            return response()->json([
                'success' => true,
                'profile_complete' => false,
                'recommendations' => [],
                'message' => 'Please complete your profile with skills to get recommendations.'
            ]);
        }

        $recommendations = $this->matchingService->getRecommendations($user, 10);

        $viewRecommendations = array_map(function ($rec) {
            $percentage = $rec['match']['percentage'];

            return [
                'internship'         => new InternshipResource($rec['internship']),
                'score'              => $rec['match']['score'],
                'percentage'         => $percentage,
                'matching_skills'    => $rec['match']['matching_skills'] ?? [],
                'missing_skills'     => $rec['match']['missing_skills'] ?? [],
                'location_match'     => $rec['match']['location_match'] ?? 0,
                'location_fit_label' => $rec['match']['location_fit_label'] ?? 'Unknown',
                'confidence'         => $this->getConfidenceBadge($percentage),
                'why_recommended'    => $this->getWhyRecommended($rec['match']),
            ];
        }, $recommendations);

        return response()->json([
            'success' => true,
            'profile_complete' => true,
            'skills' => $profile->skills,
            'recommendations' => $viewRecommendations,
        ]);
    }

    /**
     * Get confidence badge based on match percentage
     */
    private function getConfidenceBadge(int $percentage): array
    {
        if ($percentage >= 80) {
            return ['level' => 'excellent', 'label' => 'Excellent Match', 'color' => '#48bb78'];
        }
        if ($percentage >= 60) {
            return ['level' => 'good', 'label' => 'Good Match', 'color' => '#667eea'];
        }
        if ($percentage >= 40) {
            return ['level' => 'fair', 'label' => 'Fair Match', 'color' => '#ecc94b'];
        }
        return ['level' => 'low', 'label' => 'Low Match', 'color' => '#a0aec0'];
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
