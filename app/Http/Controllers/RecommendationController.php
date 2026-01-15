<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * RecommendationController
 * 
 * Implements a RULE-BASED recommendation system for matching students with internships.
 * 
 * Algorithm Overview:
 * 1. Skill Matching: Compares student skills with internship requirements using array intersection
 * 2. Similarity Scoring: Calculates percentage match (matching skills / required skills)
 * 3. Academic Bonus: Adds 0.2 to score if academic background keywords match internship title
 * 4. Filtering: Only shows active internships
 * 5. Ranking: Sorts by similarity score (highest first)
 * 
 * This is NOT an AI/ML system. It uses:
 * - Database queries (Laravel Eloquent)
 * - String comparison (case-insensitive)
 * - Array operations (intersection, filtering)
 * - Simple arithmetic for scoring
 * 
 * No machine learning, neural networks, or predictive algorithms are used.
 */
class RecommendationController extends Controller
{
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

        // Debug logging
        Log::info('=== RECOMMENDATION DEBUG START ===');
        Log::info('User ID: ' . $user->id);
        Log::info('Profile Skills: ', (array)$profile->skills);
        
        // Check total internships
        $totalInternships = Internship::count();
        $activeInternships = Internship::where('is_active', true)->count();
        
        Log::info('Total Internships: ' . $totalInternships);
        Log::info('Active Internships: ' . $activeInternships);

        $recommendations = $this->getRecommendations($profile);

        Log::info('Recommendations Count: ' . count($recommendations));
        Log::info('=== RECOMMENDATION DEBUG END ===');

        return view('recommendations.index', [
            'recommendations' => $recommendations,
            'message' => empty($recommendations) ? 'No matching internships found. Try updating your skills or check back later for new opportunities.' : null,
            'debug' => [
                'total_internships' => $totalInternships,
                'active_internships' => $activeInternships,
                'user_skills' => $profile->skills,
                'recommendations_found' => count($recommendations)
            ]
        ]);
    }

    /**
     * Get recommendations for the authenticated user
     * 
     * Rule-Based Matching Logic:
     * - Fetches all active internships from database
     * - Normalizes skills (lowercase, trim whitespace)
     * - Calculates skill overlap using array_intersect()
     * - Computes similarity score: (matching_skills / required_skills)
     * - Adds academic background bonus if keywords match
     * - Sorts by score and returns top 10
     */
    private function getRecommendations($profile)
    {
        // Fetch active internships
        $internships = Internship::where('is_active', true)->get();
        
        Log::info('Fetched internships: ' . $internships->count());

        // If no internships, return early
        if ($internships->isEmpty()) {
            Log::warning('No active internships found in database');
            return [];
        }

        // Normalize user skills
        $userSkills = is_array($profile->skills)
            ? array_map('trim', array_map('strtolower', $profile->skills))
            : array_map('trim', array_map('strtolower', explode(',', $profile->skills)));
        
        // Remove empty values
        $userSkills = array_filter($userSkills);
        
        Log::info('Normalized User Skills: ', $userSkills);

        $recommendations = [];

        foreach ($internships as $internship) {
            Log::info('Processing Internship ID: ' . $internship->id . ' - ' . $internship->title);
            
            // Check if required_skills column exists
            if (!isset($internship->required_skills)) {
                Log::warning('Internship ' . $internship->id . ' missing required_skills column');
                continue;
            }
            
            if (empty($internship->required_skills)) {
                Log::warning('Internship ' . $internship->id . ' has empty required_skills');
                continue;
            }
            
            // Normalize required skills
            $requiredSkills = is_array($internship->required_skills)
                ? array_map('trim', array_map('strtolower', $internship->required_skills))
                : array_map('trim', array_map('strtolower', explode(',', $internship->required_skills)));
            
            // Remove empty values
            $requiredSkills = array_filter($requiredSkills);
            
            Log::info('Required Skills for ' . $internship->id . ': ', $requiredSkills);

            $matchingSkills = array_intersect($userSkills, $requiredSkills);
            
            Log::info('Matching Skills: ', $matchingSkills);
            
            // Calculate similarity score using simple division
            // Formula: (number of matching skills) / (total required skills)
            // This is a basic rule-based calculation, not machine learning
            $similarityScore = count($requiredSkills) > 0
                ? count($matchingSkills) / count($requiredSkills)
                : 0;

            // Academic background keyword match (simple string comparison)
            // Adds 0.2 bonus if any academic keywords match internship title
            if (!empty($profile->academic_background)) {
                $academicKeywords = array_filter(explode(' ', strtolower($profile->academic_background)));
                $titleKeywords = array_filter(explode(' ', strtolower($internship->title)));
                if (count(array_intersect($academicKeywords, $titleKeywords)) > 0) {
                    $similarityScore += 0.2;
                    Log::info('Academic match bonus added');
                }
            }

            Log::info('Similarity Score: ' . $similarityScore);

            if ($similarityScore > 0) {
                $recommendations[] = [
                    'internship' => $internship,
                    'score' => round($similarityScore, 2),
                    'matching_skills' => $matchingSkills
                ];
                Log::info('Added to recommendations');
            } else {
                Log::info('Not added - score is 0');
            }
        }

        // Sort by score (highest first) and return top 10
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($recommendations, 0, 10);
    }
}
