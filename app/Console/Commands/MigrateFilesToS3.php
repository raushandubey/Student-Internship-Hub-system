<?php

namespace App\Console\Commands;

use App\Models\Profile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateFilesToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:migrate-to-s3 {--dry-run : Preview migration without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing local resume files to S3';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be migrated');
            $this->newLine();
        }
        
        // Get all profiles with resume files
        $profiles = Profile::whereNotNull('resume_path')->get();
        $this->info("Found {$profiles->count()} profiles with resume files");
        $this->newLine();
        
        if ($profiles->count() === 0) {
            $this->info('No files to migrate.');
            return Command::SUCCESS;
        }
        
        $successCount = 0;
        $failureCount = 0;
        $skippedCount = 0;
        
        $progressBar = $this->output->createProgressBar($profiles->count());
        $progressBar->start();
        
        foreach ($profiles as $profile) {
            $localPath = $profile->resume_path;
            
            // Check if file exists locally
            if (!Storage::disk('public')->exists($localPath)) {
                $skippedCount++;
                $progressBar->advance();
                continue;
            }
            
            try {
                if (!$dryRun) {
                    // Read from local storage
                    $contents = Storage::disk('public')->get($localPath);
                    
                    // Write to S3
                    Storage::disk('s3')->put($localPath, $contents);
                    
                    \Log::info('File migrated to S3', [
                        'profile_id' => $profile->id,
                        'path' => $localPath
                    ]);
                }
                
                $successCount++;
                
            } catch (\Exception $e) {
                \Log::error('File migration failed', [
                    'profile_id' => $profile->id,
                    'path' => $localPath,
                    'error' => $e->getMessage()
                ]);
                
                $failureCount++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Report results
        $this->info('Migration Results:');
        $this->info("  ✓ Successful: {$successCount}");
        $this->error("  ✗ Failed: {$failureCount}");
        $this->warn("  ⊘ Skipped: {$skippedCount}");
        
        if ($dryRun) {
            $this->newLine();
            $this->info('Run without --dry-run to perform actual migration');
        }
        
        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
