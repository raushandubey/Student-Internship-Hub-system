<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\RecruiterProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RecruiterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test recruiter account
        $recruiter = User::create([
            'name' => 'Test Recruiter',
            'email' => 'recruiter@test.com',
            'password' => Hash::make('password'),
            'role' => 'recruiter',
        ]);

        // Create recruiter profile
        RecruiterProfile::create([
            'user_id' => $recruiter->id,
            'organization' => 'Tech Corp',
            'description' => 'Leading technology company specializing in software development and innovation.',
            'website' => 'https://techcorp.example.com',
        ]);

        $this->command->info('Test recruiter created: recruiter@test.com / password');
    }
}
