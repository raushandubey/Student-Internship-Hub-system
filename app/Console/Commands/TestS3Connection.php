<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestS3Connection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:test-s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test S3 storage connectivity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing S3 connectivity...');
        
        $testFile = 'test-' . time() . '.txt';
        $testContent = 'S3 connectivity test';
        
        try {
            // Test write
            $this->info('1. Writing test file...');
            Storage::disk('s3')->put($testFile, $testContent);
            $this->info('   ✓ Write successful');
            
            // Test exists
            $this->info('2. Checking file exists...');
            if (!Storage::disk('s3')->exists($testFile)) {
                throw new \Exception('File not found after write');
            }
            $this->info('   ✓ File exists');
            
            // Test read
            $this->info('3. Reading file contents...');
            $contents = Storage::disk('s3')->get($testFile);
            if ($contents !== $testContent) {
                throw new \Exception('File contents do not match');
            }
            $this->info('   ✓ Read successful');
            
            // Test delete
            $this->info('4. Deleting test file...');
            Storage::disk('s3')->delete($testFile);
            $this->info('   ✓ Delete successful');
            
            $this->newLine();
            $this->info('✅ S3 connectivity test PASSED');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ S3 connectivity test FAILED');
            $this->error('Error: ' . $e->getMessage());
            
            // Cleanup
            try {
                Storage::disk('s3')->delete($testFile);
            } catch (\Exception $cleanupError) {
                // Ignore cleanup errors
            }
            
            return Command::FAILURE;
        }
    }
}
