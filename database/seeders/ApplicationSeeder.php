<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\User;
use App\Models\Internship;
use App\Enums\ApplicationStatus;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run()
    {
        // Get or create a student user
        $student = User::where('role', 'student')->first();
        if (!$student) {
            $student = User::factory()->create([
                'role' => 'student',
                'email' => 'student@example.com',
                'name' => 'Test Student'
            ]);
        }

        // Get some internships
        $internships = Internship::take(5)->get();

        if ($internships->isEmpty()) {
            $this->command->warn('No internships found. Please run InternshipSeeder first.');
            return;
        }

        // Create applications in different statuses
        $statuses = [
            ApplicationStatus::PENDING,
            ApplicationStatus::UNDER_REVIEW,
            ApplicationStatus::SHORTLISTED,
            ApplicationStatus::INTERVIEW_SCHEDULED,
            ApplicationStatus::APPROVED,
        ];

        foreach ($internships as $index => $internship) {
            // Check if application already exists
            $exists = Application::where('user_id', $student->id)
                ->where('internship_id', $internship->id)
                ->exists();

            if (!$exists) {
                Application::create([
                    'user_id' => $student->id,
                    'internship_id' => $internship->id,
                    'status' => $statuses[$index % count($statuses)],
                    'match_score' => rand(70, 95) + (rand(0, 9) / 10),
                ]);
            }
        }

        $this->command->info('Successfully seeded applications!');
    }
}
