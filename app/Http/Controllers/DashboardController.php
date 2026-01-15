<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Internship;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Redirect admin to admin dashboard
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        
        // Student dashboard logic
        $profile = $user->profile;
        $recommendations = 0;
        $profileCompletion = 0;
        
        // Calculate profile completion
        if ($profile) {
            $fields = ['name', 'academic_background', 'skills', 'career_interests', 'resume_path', 'aadhaar_number'];
            $completed = 0;
            foreach ($fields as $field) {
                if (!empty($profile->$field)) {
                    $completed++;
                }
            }
            $profileCompletion = round(($completed / count($fields)) * 100);
            
            // Get recommendations count if profile has skills
            if (!empty($profile->skills)) {
                $recommendations = $this->getRecommendationsCount($profile);
            }
        }
        
        // Count applications from applications table (single source of truth)
        $appliedJobs = Application::where('user_id', Auth::id())->count();
        
        // Count approved applications for interviews
        $interviews = Application::where('user_id', Auth::id())
            ->where('status', 'approved')
            ->count();
        
        // Calculate application rate (if user has applied to any internships)
        $applicationRate = $recommendations > 0 
            ? round(($appliedJobs / $recommendations) * 100) . '%'
            : '0%';
        
        // Calculate response rate (approved + rejected / total applications)
        $respondedApplications = Application::where('user_id', Auth::id())
            ->whereIn('status', ['approved', 'rejected'])
            ->count();
        $responseRate = $appliedJobs > 0
            ? round(($respondedApplications / $appliedJobs) * 100) . '%'
            : '0%';
        
        return view('student.dashboard', [
            'profileCompletion' => $profileCompletion,
            'recommendations' => $recommendations,
            'appliedJobs' => $appliedJobs,
            'interviews' => $interviews,
            'profileViews' => 0,
            'applicationRate' => $applicationRate,
            'responseRate' => $responseRate,
        ]);
    }
    
    private function getRecommendationsCount($profile)
    {
        Log::info('=== DASHBOARD RECOMMENDATIONS DEBUG ===');
        
        // Fetch active internships
        $internships = Internship::where('is_active', true)->get();
        Log::info('Active internships found: ' . $internships->count());
        
        if ($internships->isEmpty()) {
            Log::warning('No active internships in database');
            return 0;
        }
        
        // Normalize user skills
        $userSkills = is_array($profile->skills)
            ? array_map('trim', array_map('strtolower', $profile->skills))
            : array_map('trim', array_map('strtolower', explode(',', $profile->skills)));
        
        $userSkills = array_filter($userSkills);
        Log::info('User skills: ' . json_encode($userSkills));
        
        if (empty($userSkills)) {
            Log::warning('User has no skills');
            return 0;
        }
        
        $count = 0;
        
        foreach ($internships as $internship) {
            if (empty($internship->required_skills)) {
                continue;
            }
            
            // Normalize required skills
            $requiredSkills = is_array($internship->required_skills)
                ? array_map('trim', array_map('strtolower', $internship->required_skills))
                : array_map('trim', array_map('strtolower', explode(',', $internship->required_skills)));
            
            $requiredSkills = array_filter($requiredSkills);
            
            if (empty($requiredSkills)) {
                continue;
            }
            
            $matchingSkills = array_intersect($userSkills, $requiredSkills);
            
            $similarityScore = count($requiredSkills) > 0
                ? count($matchingSkills) / count($requiredSkills)
                : 0;
            
            // Academic background keyword match
            if (!empty($profile->academic_background)) {
                $academicKeywords = array_filter(explode(' ', strtolower($profile->academic_background)));
                $titleKeywords = array_filter(explode(' ', strtolower($internship->title)));
                if (count(array_intersect($academicKeywords, $titleKeywords)) > 0) {
                    $similarityScore += 0.2;
                }
            }
            
            if ($similarityScore > 0) {
                $count++;
                Log::info("Match found for: {$internship->title} (Score: {$similarityScore})");
            }
        }
        
        Log::info('Total recommendations found: ' . $count);
        Log::info('=== END DASHBOARD RECOMMENDATIONS DEBUG ===');
        
        return min($count, 10);
    }
}