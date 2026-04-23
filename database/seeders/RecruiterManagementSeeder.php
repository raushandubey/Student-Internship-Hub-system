<?php

namespace Database\Seeders;

use App\Models\AdminAuditLog;
use App\Models\Application;
use App\Models\Internship;
use App\Models\RecruiterProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RecruiterManagementSeeder extends Seeder
{
    /**
     * Seed recruiters with various approval statuses, internships, applications,
     * and admin audit logs for testing the recruiter management admin panel.
     *
     * Requirements: Testing support
     */
    public function run(): void
    {
        // ------------------------------------------------------------------
        // 1. Ensure an admin user exists (reuse or create)
        // ------------------------------------------------------------------
        $admin = User::firstOrCreate(
            ['email' => 'admin@sih.com'],
            [
                'name'               => 'Admin User',
                'password'           => Hash::make('admin123'),
                'role'               => 'admin',
                'email_verified_at'  => now(),
            ]
        );

        // ------------------------------------------------------------------
        // 2. Ensure a student user exists for applications
        // ------------------------------------------------------------------
        $student = User::firstOrCreate(
            ['email' => 'student@test.com'],
            [
                'name'     => 'Test Student',
                'password' => Hash::make('password'),
                'role'     => 'student',
            ]
        );

        // ------------------------------------------------------------------
        // 3. Seed recruiters with various approval statuses
        // ------------------------------------------------------------------
        $recruitersData = [
            [
                'user' => [
                    'name'     => 'Alice Approved',
                    'email'    => 'alice.approved@recruiter.test',
                    'password' => Hash::make('password'),
                    'role'     => 'recruiter',
                ],
                'profile' => [
                    'organization'    => 'Approved Tech Ltd',
                    'description'     => 'A fully approved technology company.',
                    'website'         => 'https://approvedtech.example.com',
                    'approval_status' => 'approved',
                    'approved_by'     => $admin->id,
                    'approved_at'     => now()->subDays(30),
                ],
            ],
            [
                'user' => [
                    'name'     => 'Bob Approved',
                    'email'    => 'bob.approved@recruiter.test',
                    'password' => Hash::make('password'),
                    'role'     => 'recruiter',
                ],
                'profile' => [
                    'organization'    => 'Bob\'s Software House',
                    'description'     => 'Software development and consulting.',
                    'website'         => 'https://bobsoftware.example.com',
                    'approval_status' => 'approved',
                    'approved_by'     => $admin->id,
                    'approved_at'     => now()->subDays(20),
                ],
            ],
            [
                'user' => [
                    'name'     => 'Carol Pending',
                    'email'    => 'carol.pending@recruiter.test',
                    'password' => Hash::make('password'),
                    'role'     => 'recruiter',
                ],
                'profile' => [
                    'organization'    => 'Carol Consulting',
                    'description'     => 'Management consulting firm awaiting approval.',
                    'website'         => 'https://carolconsulting.example.com',
                    'approval_status' => 'pending',
                ],
            ],
            [
                'user' => [
                    'name'     => 'Dave Pending',
                    'email'    => 'dave.pending@recruiter.test',
                    'password' => Hash::make('password'),
                    'role'     => 'recruiter',
                ],
                'profile' => [
                    'organization'    => 'Dave Digital Agency',
                    'description'     => 'Digital marketing agency, registration pending.',
                    'website'         => 'https://davedigital.example.com',
                    'approval_status' => 'pending',
                ],
            ],
            [
                'user' => [
                    'name'     => 'Eve Rejected',
                    'email'    => 'eve.rejected@recruiter.test',
                    'password' => Hash::make('password'),
                    'role'     => 'recruiter',
                ],
                'profile' => [
                    'organization'    => 'Eve Enterprises',
                    'description'     => 'Company whose application was rejected.',
                    'website'         => 'https://eveenterprises.example.com',
                    'approval_status' => 'rejected',
                    'rejection_reason' => 'Incomplete company documentation provided.',
                ],
            ],
            [
                'user' => [
                    'name'     => 'Frank Suspended',
                    'email'    => 'frank.suspended@recruiter.test',
                    'password' => Hash::make('password'),
                    'role'     => 'recruiter',
                ],
                'profile' => [
                    'organization'    => 'Frank\'s Firm',
                    'description'     => 'Company suspended for policy violations.',
                    'website'         => 'https://frankfirm.example.com',
                    'approval_status' => 'suspended',
                    'approved_by'     => $admin->id,
                    'approved_at'     => now()->subDays(60),
                    'suspended_at'    => now()->subDays(5),
                    'suspension_reason' => 'Multiple reports of misleading internship descriptions.',
                ],
            ],
        ];

        $createdRecruiters = [];

        foreach ($recruitersData as $data) {
            $recruiter = User::firstOrCreate(
                ['email' => $data['user']['email']],
                $data['user']
            );

            // Create or update profile
            $profile = RecruiterProfile::firstOrCreate(
                ['user_id' => $recruiter->id],
                array_merge(['user_id' => $recruiter->id], $data['profile'])
            );

            $createdRecruiters[] = [
                'user'    => $recruiter,
                'profile' => $profile,
            ];
        }

        // ------------------------------------------------------------------
        // 4. Seed recruiter-posted internships (for approved recruiters)
        // ------------------------------------------------------------------
        $approvedRecruiters = array_filter($createdRecruiters, function ($r) {
            return $r['profile']->approval_status === 'approved';
        });

        $internshipTemplates = [
            [
                'title'           => 'Frontend Developer Intern',
                'organization'    => null, // filled from recruiter profile
                'required_skills' => ['React', 'TypeScript', 'CSS', 'HTML'],
                'duration'        => '3 months',
                'location'        => 'Remote',
                'description'     => 'Build modern UI components using React and TypeScript.',
                'is_active'       => true,
            ],
            [
                'title'           => 'Backend Developer Intern',
                'organization'    => null,
                'required_skills' => ['PHP', 'Laravel', 'MySQL', 'REST APIs'],
                'duration'        => '6 months',
                'location'        => 'Bangalore',
                'description'     => 'Develop scalable backend services using Laravel.',
                'is_active'       => true,
            ],
            [
                'title'           => 'Data Analyst Intern',
                'organization'    => null,
                'required_skills' => ['Python', 'Pandas', 'SQL', 'Tableau'],
                'duration'        => '4 months',
                'location'        => 'Mumbai',
                'description'     => 'Analyze business data and create insightful reports.',
                'is_active'       => false,
                'deactivation_reason' => 'Position filled ahead of schedule.',
                'deactivated_by'  => $admin->id,
                'deactivated_at'  => now()->subDays(3),
            ],
        ];

        $createdInternships = [];
        $templateIndex = 0;

        foreach ($approvedRecruiters as $recruiterData) {
            $recruiter = $recruiterData['user'];
            $profile   = $recruiterData['profile'];

            // Each approved recruiter gets 2 internships
            for ($i = 0; $i < 2; $i++) {
                $template = $internshipTemplates[$templateIndex % count($internshipTemplates)];
                $templateIndex++;

                $internshipData = array_merge($template, [
                    'organization' => $profile->organization,
                    'recruiter_id' => $recruiter->id,
                ]);

                $internship = Internship::create($internshipData);
                $createdInternships[] = $internship;
            }
        }

        // ------------------------------------------------------------------
        // 5. Seed applications to recruiter-posted internships
        // ------------------------------------------------------------------
        $statuses = ['pending', 'under_review', 'shortlisted', 'approved', 'rejected'];

        foreach ($createdInternships as $internship) {
            if (!$internship->is_active) {
                continue; // skip inactive internships
            }

            // Create 2–3 applications per active internship
            $appCount = rand(2, 3);
            for ($j = 0; $j < $appCount; $j++) {
                // Avoid duplicate applications from the same student to the same internship
                $exists = Application::where('user_id', $student->id)
                    ->where('internship_id', $internship->id)
                    ->exists();

                if ($exists) {
                    // Create a fresh student for additional applications
                    $extraStudent = User::firstOrCreate(
                        ['email' => 'student' . $j . '_' . $internship->id . '@test.com'],
                        [
                            'name'     => 'Student ' . $j,
                            'password' => Hash::make('password'),
                            'role'     => 'student',
                        ]
                    );
                    $applicantId = $extraStudent->id;
                } else {
                    $applicantId = $student->id;
                }

                Application::create([
                    'user_id'       => $applicantId,
                    'internship_id' => $internship->id,
                    'status'        => $statuses[array_rand($statuses)],
                    'match_score'   => round(rand(50, 100) / 100, 2),
                ]);
            }
        }

        // ------------------------------------------------------------------
        // 6. Seed admin audit logs with sample actions
        // ------------------------------------------------------------------
        $auditLogs = [];

        foreach ($createdRecruiters as $recruiterData) {
            $recruiter = $recruiterData['user'];
            $profile   = $recruiterData['profile'];

            switch ($profile->approval_status) {
                case 'approved':
                    $auditLogs[] = [
                        'admin_user_id'       => $admin->id,
                        'action_type'         => AdminAuditLog::APPROVED,
                        'target_recruiter_id' => $recruiter->id,
                        'reason'              => null,
                        'ip_address'          => '127.0.0.1',
                        'created_at'          => $profile->approved_at ?? now()->subDays(20),
                        'updated_at'          => $profile->approved_at ?? now()->subDays(20),
                    ];
                    break;

                case 'rejected':
                    $auditLogs[] = [
                        'admin_user_id'       => $admin->id,
                        'action_type'         => AdminAuditLog::REJECTED,
                        'target_recruiter_id' => $recruiter->id,
                        'reason'              => $profile->rejection_reason,
                        'ip_address'          => '127.0.0.1',
                        'created_at'          => now()->subDays(10),
                        'updated_at'          => now()->subDays(10),
                    ];
                    break;

                case 'suspended':
                    // First approved, then suspended
                    $auditLogs[] = [
                        'admin_user_id'       => $admin->id,
                        'action_type'         => AdminAuditLog::APPROVED,
                        'target_recruiter_id' => $recruiter->id,
                        'reason'              => null,
                        'ip_address'          => '127.0.0.1',
                        'created_at'          => $profile->approved_at ?? now()->subDays(60),
                        'updated_at'          => $profile->approved_at ?? now()->subDays(60),
                    ];
                    $auditLogs[] = [
                        'admin_user_id'       => $admin->id,
                        'action_type'         => AdminAuditLog::SUSPENDED,
                        'target_recruiter_id' => $recruiter->id,
                        'reason'              => $profile->suspension_reason,
                        'ip_address'          => '127.0.0.1',
                        'created_at'          => $profile->suspended_at ?? now()->subDays(5),
                        'updated_at'          => $profile->suspended_at ?? now()->subDays(5),
                    ];
                    break;
            }
        }

        // Add internship deactivation log for deactivated internships
        foreach ($createdInternships as $internship) {
            if (!$internship->is_active && $internship->deactivated_by) {
                $auditLogs[] = [
                    'admin_user_id'       => $admin->id,
                    'action_type'         => AdminAuditLog::INTERNSHIP_DEACTIVATED,
                    'target_recruiter_id' => $internship->recruiter_id,
                    'reason'              => $internship->deactivation_reason,
                    'ip_address'          => '127.0.0.1',
                    'created_at'          => $internship->deactivated_at ?? now()->subDays(3),
                    'updated_at'          => $internship->deactivated_at ?? now()->subDays(3),
                ];
            }
        }

        foreach ($auditLogs as $log) {
            AdminAuditLog::create($log);
        }

        // ------------------------------------------------------------------
        // Summary
        // ------------------------------------------------------------------
        $this->command->info('RecruiterManagementSeeder completed:');
        $this->command->info('  Recruiters created: ' . count($createdRecruiters));
        $this->command->info('  Internships created: ' . count($createdInternships));
        $this->command->info('  Audit log entries:  ' . count($auditLogs));
    }
}
