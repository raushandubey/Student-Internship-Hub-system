<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigrateFilesToS3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake both storage disks
        Storage::fake('public');
        Storage::fake('s3');
    }

    public function test_migration_with_sample_local_files()
    {
        // Create profiles with local resume files
        $user1 = User::factory()->create();
        $profile1 = Profile::factory()->create([
            'user_id' => $user1->id,
            'resume_path' => 'resumes/test1.pdf'
        ]);
        
        $user2 = User::factory()->create();
        $profile2 = Profile::factory()->create([
            'user_id' => $user2->id,
            'resume_path' => 'resumes/test2.pdf'
        ]);
        
        // Create local files
        Storage::disk('public')->put('resumes/test1.pdf', 'Resume content 1');
        Storage::disk('public')->put('resumes/test2.pdf', 'Resume content 2');
        
        // Run migration
        $this->artisan('storage:migrate-to-s3')
            ->expectsOutput('Found 2 profiles with resume files')
            ->expectsOutput('Migration Results:')
            ->expectsOutput('  ✓ Successful: 2')
            ->assertExitCode(0);
        
        // Verify files were copied to S3
        Storage::disk('s3')->assertExists('resumes/test1.pdf');
        Storage::disk('s3')->assertExists('resumes/test2.pdf');
        
        // Verify content matches
        $this->assertEquals('Resume content 1', Storage::disk('s3')->get('resumes/test1.pdf'));
        $this->assertEquals('Resume content 2', Storage::disk('s3')->get('resumes/test2.pdf'));
    }

    public function test_dry_run_mode_does_not_migrate_files()
    {
        // Create profile with local resume file
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => 'resumes/test.pdf'
        ]);
        
        // Create local file
        Storage::disk('public')->put('resumes/test.pdf', 'Resume content');
        
        // Run migration in dry-run mode
        $this->artisan('storage:migrate-to-s3', ['--dry-run' => true])
            ->expectsOutput('DRY RUN MODE - No files will be migrated')
            ->expectsOutput('Found 1 profiles with resume files')
            ->expectsOutput('  ✓ Successful: 1')
            ->expectsOutput('Run without --dry-run to perform actual migration')
            ->assertExitCode(0);
        
        // Verify file was NOT copied to S3
        Storage::disk('s3')->assertMissing('resumes/test.pdf');
    }

    public function test_error_handling_for_missing_files()
    {
        // Create profile with resume path but no actual file
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => 'resumes/missing.pdf'
        ]);
        
        // Run migration
        $this->artisan('storage:migrate-to-s3')
            ->expectsOutput('Found 1 profiles with resume files')
            ->expectsOutput('  ⊘ Skipped: 1')
            ->assertExitCode(0);
        
        // Verify nothing was copied to S3
        Storage::disk('s3')->assertMissing('resumes/missing.pdf');
    }

    public function test_statistics_reporting()
    {
        // Create mix of profiles: some with files, some without, some with missing files
        $user1 = User::factory()->create();
        $profile1 = Profile::factory()->create([
            'user_id' => $user1->id,
            'resume_path' => 'resumes/exists.pdf'
        ]);
        Storage::disk('public')->put('resumes/exists.pdf', 'Content');
        
        $user2 = User::factory()->create();
        $profile2 = Profile::factory()->create([
            'user_id' => $user2->id,
            'resume_path' => 'resumes/missing.pdf'
        ]);
        // No file created for this one
        
        $user3 = User::factory()->create();
        $profile3 = Profile::factory()->create([
            'user_id' => $user3->id,
            'resume_path' => null
        ]);
        
        // Run migration
        $this->artisan('storage:migrate-to-s3')
            ->expectsOutput('Found 2 profiles with resume files')
            ->expectsOutput('Migration Results:')
            ->expectsOutput('  ✓ Successful: 1')
            ->expectsOutput('  ✗ Failed: 0')
            ->expectsOutput('  ⊘ Skipped: 1')
            ->assertExitCode(0);
    }

    public function test_migration_continues_after_individual_failures()
    {
        // Create multiple profiles - mix of success and failure scenarios
        $user1 = User::factory()->create();
        $profile1 = Profile::factory()->create([
            'user_id' => $user1->id,
            'resume_path' => 'resumes/success.pdf'
        ]);
        Storage::disk('public')->put('resumes/success.pdf', 'Content');
        
        // This profile has a path but no file (will be skipped)
        $user2 = User::factory()->create();
        $profile2 = Profile::factory()->create([
            'user_id' => $user2->id,
            'resume_path' => 'resumes/missing.pdf'
        ]);
        
        // Run migration - should handle mixed scenarios
        $this->artisan('storage:migrate-to-s3')
            ->expectsOutput('Found 2 profiles with resume files')
            ->expectsOutput('Migration Results:')
            ->expectsOutput('  ✓ Successful: 1')
            ->expectsOutput('  ⊘ Skipped: 1')
            ->assertExitCode(0);
        
        // Verify successful file was migrated
        Storage::disk('s3')->assertExists('resumes/success.pdf');
        Storage::disk('s3')->assertMissing('resumes/missing.pdf');
    }

    public function test_migration_with_no_profiles()
    {
        // Run migration with no profiles
        $this->artisan('storage:migrate-to-s3')
            ->expectsOutput('Found 0 profiles with resume files')
            ->expectsOutput('No files to migrate.')
            ->assertExitCode(0);
    }
}
