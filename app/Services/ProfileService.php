<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProfileService
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    private const CACHE_TTL = 300;

    /**
     * Candidate summary service for AI-powered analysis
     */
    protected CandidateSummaryService $summaryService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(CandidateSummaryService $summaryService)
    {
        $this->summaryService = $summaryService;
    }

    /**
     * Get complete profile data for admin view
     * Includes caching and eager loading
     * 
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function getProfileForAdmin(int $userId): array
    {
        try {
            Log::info('Fetching profile for admin view', [
                'user_id' => $userId
            ]);

            // Check cache first
            $cachedData = $this->getCachedProfile($userId);
            
            if ($cachedData !== null) {
                Log::info('Profile cache hit', [
                    'user_id' => $userId
                ]);
                return $cachedData;
            }

            Log::info('Profile cache miss, fetching from database', [
                'user_id' => $userId
            ]);

            // Fetch profile with eager loaded user relationship
            $profile = Profile::with('user')
                ->where('user_id', $userId)
                ->first();

            if (!$profile) {
                Log::warning('Profile not found', [
                    'user_id' => $userId
                ]);
                throw new \Exception("Profile not found for user ID: {$userId}");
            }

            // Generate AI summary (graceful degradation on failure)
            $aiSummary = $this->summaryService->generateSummary($profile);

            // Format the profile data with AI summary
            $formattedData = $this->formatProfileData($profile, $profile->user, $aiSummary);

            // Cache the formatted data
            $this->cacheProfile($userId, $formattedData);

            Log::info('Profile successfully fetched and cached', [
                'user_id' => $userId,
                'has_ai_summary' => $aiSummary !== null
            ]);

            return $formattedData;
        } catch (\Exception $e) {
            Log::error('Profile fetch failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get cached profile data
     * 
     * @param int $userId
     * @return array|null
     */
    protected function getCachedProfile(int $userId): ?array
    {
        try {
            $cacheKey = "admin_profile_{$userId}";
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            Log::warning('Cache retrieval failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache profile data with TTL
     * 
     * @param int $userId
     * @param array $data
     * @return void
     */
    protected function cacheProfile(int $userId, array $data): void
    {
        try {
            $cacheKey = "admin_profile_{$userId}";
            Cache::put($cacheKey, $data, self::CACHE_TTL);
        } catch (\Exception $e) {
            Log::warning('Cache storage failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            // Continue execution - caching is not critical
        }
    }

    /**
     * Format profile data for JSON response
     * PRODUCTION-SAFE: Proper URL generation with fallback
     * 
     * @param Profile $profile
     * @param User $user
     * @param array|null $aiSummary
     * @return array
     */
    protected function formatProfileData(Profile $profile, User $user, ?array $aiSummary = null): array
    {
        // Use the model's getResumeUrl() method for consistent URL generation
        $resumeUrl = $profile->getResumeUrl();
        
        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'profile' => [
                'academic_background' => $profile->academic_background,
                'skills' => $profile->skills ?? [],
                'career_interests' => $profile->career_interests,
                'resume_path' => $resumeUrl,
                'has_resume' => $profile->hasResumeFile(),
            ],
        ];

        // Include AI summary only if generation succeeded
        if ($aiSummary !== null) {
            $data['ai_summary'] = $aiSummary;
        }

        return $data;
    }
}
