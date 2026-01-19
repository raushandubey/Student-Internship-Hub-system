<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\Internship;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DemoDataSeeder
 * 
 * Phase 10: Demo Readiness
 * 
 * Generates realistic demo data for viva/interview demonstrations.
 * Creates varied scenarios to showcase all features.
 * 
 * Usage: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎬 Seeding demo data...');

        // Create demo students with varied profiles
        $students = $this->createDemoStudents();
        $this->command->info('✅ Created ' . count($students) . ' demo students');

        // Create demo internships
        $internships = $this->createDemoInternships();
        $this->command->info('✅ Created ' . count($internships) . ' demo internships');

        // Create applications with varied statuses and timelines
        $this->createDemoApplications($students, $internships);
        $this->command->info('✅ Created demo applications with varied statuses');

        $this->command->info('🎉 Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('Demo Accounts:');
        $this->command->info('Student 1: demo.student1@sih.com / password');
        $this->command->info('Student 2: demo.student2@sih.com / password');
        $this->command->info('Student 3: demo.student3@sih.com / password');
        $this->command->info('Admin: admin@sih.com / admin123');
    }

    /**
     * Create demo students with varied skill profiles
     */
    private function createDemoStudents(): array
    {
        $students = [];

        // Student 1: Strong profile (high match scores)
        $user1 = User::create([
            'name' => 'Rahul Sharma',
            'email' => 'demo.student1@sih.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'email_verified_at' => now(),
        ]);

        Profile::create([
            'user_id' => $user1->id,
            'academic_background' => 'B.Tech Computer Science, IIT Delhi, CGPA: 8.5',
            'skills' => 'Laravel,PHP,MySQL,JavaScript,React,Docker,Git,REST API',
            'career_interests' => 'Full-stack development, Cloud computing, DevOps',
            'aadhaar_number' => '1234-5678-9012',
        ]);

        $students[] = $user1;

        // Student 2: Moderate profile (mixed match scores)
        $user2 = User::create([
            'name' => 'Priya Patel',
            'email' => 'demo.student2@sih.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'email_verified_at' => now(),
        ]);

        Profile::create([
            'user_id' => $user2->id,
            'academic_background' => 'B.Tech Information Technology, NIT Trichy, CGPA: 7.8',
            'skills' => 'Python,Django,PostgreSQL,HTML,CSS,Bootstrap',
            'career_interests' => 'Backend development, Data analysis',
            'aadhaar_number' => '2345-6789-0123',
        ]);

        $students[] = $user2;

        // Student 3: Developing profile (lower match scores, more gaps)
        $user3 = User::create([
            'name' => 'Amit Kumar',
            'email' => 'demo.student3@sih.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'email_verified_at' => now(),
        ]);

        Profile::create([
            'user_id' => $user3->id,
            'academic_background' => 'B.Tech Electronics, VIT Vellore, CGPA: 7.2',
            'skills' => 'C,C++,Java,HTML,CSS',
            'career_interests' => 'Software development, Learning new technologies',
            'aadhaar_number' => '3456-7890-1234',
        ]);

        $students[] = $user3;

        return $students;
    }

    /**
     * Create demo internships with varied requirements
     */
    private function createDemoInternships(): array
    {
        $internships = [];

        $internships[] = Internship::create([
            'title' => 'Full Stack Developer Intern',
            'organization' => 'TechCorp Solutions',
            'description' => 'Work on cutting-edge web applications using modern frameworks.',
            'required_skills' => 'Laravel,PHP,MySQL,JavaScript,React,Git',
            'location' => 'Bangalore',
            'duration' => '6 months',
            'stipend' => 25000,
            'work_type' => 'hybrid',
            'is_active' => true,
            'posted_by' => 1,
        ]);

        $internships[] = Internship::create([
            'title' => 'Backend Developer Intern',
            'organization' => 'DataFlow Systems',
            'description' => 'Build scalable backend services and APIs.',
            'required_skills' => 'Python,Django,PostgreSQL,REST API,Docker',
            'location' => 'Pune',
            'duration' => '4 months',
            'stipend' => 20000,
            'work_type' => 'remote',
            'is_active' => true,
            'posted_by' => 1,
        ]);

        $internships[] = Internship::create([
            'title' => 'DevOps Engineer Intern',
            'organization' => 'CloudScale Inc',
            'description' => 'Learn cloud infrastructure and CI/CD pipelines.',
            'required_skills' => 'Docker,Kubernetes,AWS,Linux,Git,Jenkins',
            'location' => 'Hyderabad',
            'duration' => '6 months',
            'stipend' => 30000,
            'work_type' => 'onsite',
            'is_active' => true,
            'posted_by' => 1,
        ]);

        $internships[] = Internship::create([
            'title' => 'Frontend Developer Intern',
            'organization' => 'DesignHub',
            'description' => 'Create beautiful and responsive user interfaces.',
            'required_skills' => 'JavaScript,React,HTML,CSS,Bootstrap,Figma',
            'location' => 'Mumbai',
            'duration' => '3 months',
            'stipend' => 18000,
            'work_type' => 'hybrid',
            'is_active' => true,
            'posted_by' => 1,
        ]);

        $internships[] = Internship::create([
            'title' => 'Software Development Intern',
            'organization' => 'StartupXYZ',
            'description' => 'Join our fast-paced startup and learn multiple technologies.',
            'required_skills' => 'Java,Spring Boot,MySQL,Git,REST API',
            'location' => 'Delhi',
            'duration' => '5 months',
            'stipend' => 22000,
            'work_type' => 'onsite',
            'is_active' => true,
            'posted_by' => 1,
        ]);

        return $internships;
    }

    /**
     * Create demo applications with varied statuses and timelines
     */
    private function createDemoApplications(array $students, array $internships): void
    {
        // Student 1: Strong candidate - multiple applications, high success
        $this->createApplicationWithTimeline(
            $students[0],
            $internships[0],
            ApplicationStatus::APPROVED,
            85,
            [
                ['from' => null, 'to' => ApplicationStatus::PENDING, 'days_ago' => 20],
                ['from' => ApplicationStatus::PENDING, 'to' => ApplicationStatus::UNDER_REVIEW, 'days_ago' => 18],
                ['from' => ApplicationStatus::UNDER_REVIEW, 'to' => ApplicationStatus::SHORTLISTED, 'days_ago' => 15],
                ['from' => ApplicationStatus::SHORTLISTED, 'to' => ApplicationStatus::INTERVIEW_SCHEDULED, 'days_ago' => 10],
                ['from' => ApplicationStatus::INTERVIEW_SCHEDULED, 'to' => ApplicationStatus::APPROVED, 'days_ago' => 5],
            ]
        );

        $this->createApplicationWithTimeline(
            $students[0],
            $internships[2],
            ApplicationStatus::INTERVIEW_SCHEDULED,
            78,
            [
                ['from' => null, 'to' => ApplicationStatus::PENDING, 'days_ago' => 15],
                ['from' => ApplicationStatus::PENDING, 'to' => ApplicationStatus::UNDER_REVIEW, 'days_ago' => 12],
                ['from' => ApplicationStatus::UNDER_REVIEW, 'to' => ApplicationStatus::SHORTLISTED, 'days_ago' => 8],
                ['from' => ApplicationStatus::SHORTLISTED, 'to' => ApplicationStatus::INTERVIEW_SCHEDULED, 'days_ago' => 3],
            ]
        );

        // Student 2: Moderate candidate - mixed results
        $this->createApplicationWithTimeline(
            $students[1],
            $internships[1],
            ApplicationStatus::SHORTLISTED,
            72,
            [
                ['from' => null, 'to' => ApplicationStatus::PENDING, 'days_ago' => 12],
                ['from' => ApplicationStatus::PENDING, 'to' => ApplicationStatus::UNDER_REVIEW, 'days_ago' => 9],
                ['from' => ApplicationStatus::UNDER_REVIEW, 'to' => ApplicationStatus::SHORTLISTED, 'days_ago' => 5],
            ]
        );

        $this->createApplicationWithTimeline(
            $students[1],
            $internships[3],
            ApplicationStatus::REJECTED,
            58,
            [
                ['from' => null, 'to' => ApplicationStatus::PENDING, 'days_ago' => 25],
                ['from' => ApplicationStatus::PENDING, 'to' => ApplicationStatus::UNDER_REVIEW, 'days_ago' => 22],
                ['from' => ApplicationStatus::UNDER_REVIEW, 'to' => ApplicationStatus::REJECTED, 'days_ago' => 18],
            ]
        );

        $this->createApplicationWithTimeline(
            $students[1],
            $internships[4],
            ApplicationStatus::UNDER_REVIEW,
            65,
            [
                ['from' => null, 'to' => ApplicationStatus::PENDING, 'days_ago' => 8],
                ['from' => ApplicationStatus::PENDING, 'to' => ApplicationStatus::UNDER_REVIEW, 'days_ago' => 5],
            ]
        );

        // Student 3: Developing candidate - mostly pending/early stage
        $this->createApplicationWithTimeline(
            $students[2],
            $internships[3],
            ApplicationStatus::PENDING,
            45,
            [
                ['from' => null, 'to' => ApplicationStatus::PENDING, 'days_ago' => 5],
            ]
        );

        $this->createApplicationWithTimeline(
            $students[2],
            $internships[4],
            ApplicationStatus::UNDER_REVIEW,
            52,
            [
                ['from' => null, 'to' => ApplicationStatus::PENDING, 'days_ago' => 10],
                ['from' => ApplicationStatus::PENDING, 'to' => ApplicationStatus::UNDER_REVIEW, 'days_ago' => 7],
            ]
        );

        $this->createApplicationWithTimeline(
            $students[2],
            $internships[1],
            ApplicationStatus::REJECTED,
            38,
            [
                ['from' => null, 'to' => ApplicationStatus::PENDING, 'days_ago' => 30],
                ['from' => ApplicationStatus::PENDING, 'to' => ApplicationStatus::REJECTED, 'days_ago' => 28],
            ]
        );
    }

    /**
     * Create application with status timeline
     */
    private function createApplicationWithTimeline(
        User $user,
        Internship $internship,
        ApplicationStatus $finalStatus,
        int $matchScore,
        array $timeline
    ): void {
        // Create application
        $application = Application::create([
            'user_id' => $user->id,
            'internship_id' => $internship->id,
            'status' => $finalStatus,
            'match_score' => $matchScore,
            'created_at' => now()->subDays($timeline[0]['days_ago']),
            'updated_at' => now()->subDays($timeline[count($timeline) - 1]['days_ago']),
        ]);

        // Create status logs for timeline
        foreach ($timeline as $entry) {
            ApplicationStatusLog::create([
                'application_id' => $application->id,
                'from_status' => $entry['from']?->value,
                'to_status' => $entry['to']->value,
                'changed_by' => $entry['to'] === ApplicationStatus::PENDING ? $user->id : 1,
                'actor_type' => $entry['to'] === ApplicationStatus::PENDING ? 'student' : 'admin',
                'notes' => $this->getStatusChangeNote($entry['to']),
                'created_at' => now()->subDays($entry['days_ago']),
                'updated_at' => now()->subDays($entry['days_ago']),
            ]);
        }
    }

    /**
     * Get appropriate note for status change
     */
    private function getStatusChangeNote(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::PENDING => 'Application submitted',
            ApplicationStatus::UNDER_REVIEW => 'Application moved to review',
            ApplicationStatus::SHORTLISTED => 'Candidate shortlisted for interview',
            ApplicationStatus::INTERVIEW_SCHEDULED => 'Interview scheduled',
            ApplicationStatus::APPROVED => 'Congratulations! Application approved',
            ApplicationStatus::REJECTED => 'Application not selected at this time',
        };
    }
}
