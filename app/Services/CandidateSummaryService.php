<?php

namespace App\Services;

use App\Models\Profile;
use Illuminate\Support\Facades\Log;
use OpenAI;

/**
 * CandidateSummaryService
 * 
 * Generates AI-powered candidate summaries highlighting strengths and weaknesses.
 * Integrates with OpenAI API for intelligent candidate analysis.
 * 
 * Architecture: ProfileService → CandidateSummaryService → OpenAI API
 */
class CandidateSummaryService
{
    /**
     * Generate AI summary from profile data
     * Returns null on failure (graceful degradation)
     * 
     * @param Profile $profile
     * @return array|null
     */
    public function generateSummary(Profile $profile): ?array
    {
        try {
            Log::info('Starting AI summary generation', [
                'profile_id' => $profile->id,
                'user_id' => $profile->user_id
            ]);

            // Build the prompt for AI analysis
            $prompt = $this->buildPrompt($profile);

            // Call OpenAI API with timeout
            $response = $this->callOpenAI($prompt);
            
            // Parse and return structured response
            $parsedResponse = $this->parseAIResponse($response);

            Log::info('AI summary generated successfully', [
                'profile_id' => $profile->id,
                'user_id' => $profile->user_id,
                'has_strengths' => !empty($parsedResponse['strengths']),
                'has_weaknesses' => !empty($parsedResponse['weaknesses'])
            ]);

            return $parsedResponse;
        } catch (\Exception $e) {
            Log::error('Candidate summary generation failed', [
                'profile_id' => $profile->id,
                'user_id' => $profile->user_id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);

            // Graceful degradation: return null on failure
            return null;
        }
    }

    /**
     * Call OpenAI API with configured timeout
     * 
     * @param string $prompt
     * @return string
     * @throws \Exception
     */
    protected function callOpenAI(string $prompt): string
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            Log::error('OpenAI API key not configured');
            throw new \Exception('OpenAI API key not configured');
        }

        try {
            Log::info('Calling OpenAI API', [
                'model' => 'gpt-3.5-turbo',
                'timeout' => 5
            ]);

            // Configure OpenAI client with 5-second timeout
            $client = OpenAI::factory()
                ->withApiKey($apiKey)
                ->withHttpClient(new \GuzzleHttp\Client([
                    'timeout' => 5,
                ]))
                ->make();

            // Call chat completion API
            $result = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a recruitment assistant analyzing candidate profiles. Provide structured assessments in JSON format.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            Log::info('OpenAI API call successful', [
                'response_length' => strlen($result->choices[0]->message->content)
            ]);

            return $result->choices[0]->message->content;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error('OpenAI API timeout', [
                'error' => $e->getMessage(),
                'timeout' => 5
            ]);
            throw new \Exception('AI API request timed out');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('OpenAI API request failed', [
                'error' => $e->getMessage(),
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null
            ]);
            throw new \Exception('AI API request failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('OpenAI API call failed', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);
            throw $e;
        }
    }

    /**
     * Build prompt for AI analysis
     * 
     * @param Profile $profile
     * @return string
     */
    public function buildPrompt(Profile $profile): string
    {
        $skills = is_array($profile->skills) 
            ? implode(', ', $profile->skills) 
            : (string) $profile->skills;

        $prompt = "Analyze the following candidate profile and provide a structured assessment:\n\n";
        $prompt .= "Name: {$profile->name}\n";
        $prompt .= "Academic Background: {$profile->academic_background}\n";
        $prompt .= "Skills: {$skills}\n";
        $prompt .= "Career Interests: {$profile->career_interests}\n\n";
        $prompt .= "Please provide:\n";
        $prompt .= "1. A list of key strengths (2-4 points)\n";
        $prompt .= "2. A list of potential weaknesses or gaps (1-3 points)\n";
        $prompt .= "3. An overall assessment (2-3 sentences)\n\n";
        $prompt .= "Format your response as JSON with keys: strengths (array), weaknesses (array), overall_assessment (string)";

        return $prompt;
    }

    /**
     * Parse AI response into structured format
     * 
     * @param string $response
     * @return array
     */
    public function parseAIResponse(string $response): array
    {
        try {
            // Attempt to parse JSON response
            $decoded = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse AI response - invalid JSON', [
                    'json_error' => json_last_error_msg(),
                    'response_preview' => substr($response, 0, 200)
                ]);
                throw new \Exception('Invalid JSON response from AI');
            }

            // Validate required keys
            if (!isset($decoded['strengths']) || !isset($decoded['weaknesses']) || !isset($decoded['overall_assessment'])) {
                Log::error('Failed to parse AI response - missing required keys', [
                    'available_keys' => array_keys($decoded),
                    'response_preview' => substr($response, 0, 200)
                ]);
                throw new \Exception('Missing required keys in AI response');
            }

            // Ensure arrays are properly formatted
            return [
                'strengths' => is_array($decoded['strengths']) ? $decoded['strengths'] : [],
                'weaknesses' => is_array($decoded['weaknesses']) ? $decoded['weaknesses'] : [],
                'overall_assessment' => (string) $decoded['overall_assessment'],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to parse AI response', [
                'response' => $response,
                'error' => $e->getMessage()
            ]);

            // Return empty structure on parse failure
            return [
                'strengths' => [],
                'weaknesses' => [],
                'overall_assessment' => '',
            ];
        }
    }
}
