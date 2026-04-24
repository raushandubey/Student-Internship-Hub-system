<?php

namespace App\Console\Commands;

use App\Models\Profile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Fix S3 Resume Permissions
 * 
 * Makes all existing resume files publicly accessible on S3
 * Run this after deploying to production to fix any private files
 */
class FixS3ResumePermissions extends Command
{
    protected $signature = 'resumes:fix-s3-permissions';
    protected $description = 'Make all S3 resume files publicly accessible';

    public function handle()
    {
        $disk = config('filesystems.default');
        
        if ($disk !== 's3') {
            $this->error('This command only works with S3 storage (FILESYSTEM_DISK=s3)');
            $this->info('Current disk: ' . $disk);
            return 1;
        }
        
        $this->info('Fixing S3 resume permissions...');
        $this->info('Bucket: ' . config('filesystems.disks.s3.bucket'));
        $this->info('Region: ' . config('filesystems.disks.s3.region'));
        $this->newLine();
        
        $profiles = Profile::whereNotNull('resume_path')->get();
        
        if ($profiles->isEmpty()) {
            $this->warn('No profiles with resumes found');
            return 0;
        }
        
        $this->info('Found ' . $profiles->count() . ' profiles with resumes');
        $this->newLine();
        
        $bar = $this->output->createProgressBar($profiles->count());
        $bar->start();
        
        $fixed = 0;
        $errors = 0;
        $skipped = 0;
        
        foreach ($profiles as $profile) {
            $path = ltrim($profile->resume_path, '/');
            
            try {
                // Check if file exists
                if (!Storage::disk('s3')->exists($path)) {
                    $this->newLine();
                    $this->warn("File not found: {$path} (Profile ID: {$profile->id})");
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Set public ACL
                Storage::disk('s3')->setVisibility($path, 'public');
                
                // Verify URL is accessible
                $url = Storage::disk('s3')->url($path);
                
                $fixed++;
                $bar->advance();
                
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to fix: {$path} (Profile ID: {$profile->id})");
                $this->error('Error: ' . $e->getMessage());
                $errors++;
                $bar->advance();
            }
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Summary
        $this->info('=== Summary ===');
        $this->info("Total profiles: {$profiles->count()}");
        $this->info("Fixed: {$fixed}");
        $this->warn("Skipped (not found): {$skipped}");
        $this->error("Errors: {$errors}");
        $this->newLine();
        
        if ($fixed > 0) {
            $this->info('✓ Successfully fixed ' . $fixed . ' resume files');
            $this->info('All files are now publicly accessible via direct S3 URLs');
        }
        
        if ($errors > 0) {
            $this->error('✗ ' . $errors . ' files failed to update');
            $this->error('Check AWS credentials and IAM permissions');
        }
        
        // Test a sample URL
        if ($fixed > 0) {
            $this->newLine();
            $this->info('=== Testing Sample URL ===');
            $sampleProfile = Profile::whereNotNull('resume_path')->first();
            $sampleUrl = $sampleProfile->getResumeUrl();
            $this->info('Sample URL: ' . $sampleUrl);
            $this->info('Open this URL in browser to verify public access');
        }
        
        return 0;
    }
}
