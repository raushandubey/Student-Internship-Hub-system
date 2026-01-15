<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Internship;

class CheckRecommendations extends Command
{
    protected $signature = 'check:recommendations {user_id?}';
    protected $description = 'Check recommendation system status';

    public function handle()
    {
        $userId = $this->argument('user_id') ?? User::where('role', 'student')->first()?->id;
        
        if (!$userId) {
            $this->error('No student users found!');
            return 1;
        }
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("User {$userId} not found!");
            return 1;
        }
        
        $this->info("=== Checking Recommendations for User: {$user->name} (ID: {$user->id}) ===\n");
        
        // Check profile
        $profile = $user->profile;
        if (!$profile) {
            $this->error('❌ No profile found!');
            $this->info('Solution: Create a profile for this user');
            return 1;
        }
        
        $this->info('✅ Profile exists');
        
        // Check skills
        if (empty($profile->skills)) {
            $this->error('❌ Profile has no skills!');
            $this->info('Solution: Add skills to the profile');
            return 1;
        }
        
        $skills = is_array($profile->skills) ? $profile->skills : explode(',', $profile->skills);
        $this->info('✅ Profile has skills: ' . implode(', ', $skills));
        
        // Check internships
        $totalInternships = Internship::count();
        $activeInternships = Internship::where('is_active', true)->count();
        
        $this->info("\n📊 Internship Statistics:");
        $this->info("   Total internships: {$totalInternships}");
        $this->info("   Active internships: {$activeInternships}");
        
        if ($activeInternships === 0) {
            $this->error('❌ No active internships found!');
            $this->info('Solution: Run: php artisan db:seed --class=InternshipSeeder');
            return 1;
        }
        
        // Check for matches
        $userSkills = array_map('trim', array_map('strtolower', $skills));
        $matches = 0;
        
        $this->info("\n🔍 Checking for matches...\n");
        
        $internships = Internship::where('is_active', true)->take(5)->get();
        
        $this->info("First 5 internships for debugging:");
        foreach ($internships as $internship) {
            $this->info("\n📌 {$internship->title}");
            $this->info("   Required skills (raw): " . json_encode($internship->required_skills));
            
            if (empty($internship->required_skills)) {
                $this->warn("   ⚠️  No required skills");
                continue;
            }
            
            $requiredSkills = is_array($internship->required_skills) 
                ? $internship->required_skills 
                : explode(',', $internship->required_skills);
            
            $this->info("   Required skills (array): " . json_encode($requiredSkills));
            
            $requiredSkills = array_map('trim', array_map('strtolower', $requiredSkills));
            $this->info("   Required skills (normalized): " . json_encode($requiredSkills));
            $this->info("   User skills (normalized): " . json_encode($userSkills));
            
            $matchingSkills = array_intersect($userSkills, $requiredSkills);
            $this->info("   Matching: " . json_encode($matchingSkills));
            
            if (!empty($matchingSkills)) {
                $matches++;
                $this->info("   ✅ MATCH!");
            } else {
                $this->warn("   ❌ No match");
            }
        }
        
        $this->info("\n📈 Results:");
        if ($matches > 0) {
            $this->info("✅ Found {$matches} matching internships!");
            $this->info('The recommendation system should be working.');
        } else {
            $this->warn('⚠️  No matching internships found!');
            $this->info('Your skills: ' . implode(', ', $userSkills));
            $this->info("\nSample internship skills:");
            $sample = Internship::where('is_active', true)->first();
            if ($sample && $sample->required_skills) {
                $sampleSkills = is_array($sample->required_skills) 
                    ? $sample->required_skills 
                    : explode(',', $sample->required_skills);
                $this->info('   ' . implode(', ', $sampleSkills));
            }
            $this->info("\nSolution: Update your profile skills to match available internships");
        }
        
        return 0;
    }
}
