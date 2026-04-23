<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TestS3ConnectionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_succeeds_when_all_operations_pass()
    {
        Storage::fake('s3');

        $this->artisan('storage:test-s3')
            ->expectsOutput('Testing S3 connectivity...')
            ->expectsOutput('1. Writing test file...')
            ->expectsOutput('   ✓ Write successful')
            ->expectsOutput('2. Checking file exists...')
            ->expectsOutput('   ✓ File exists')
            ->expectsOutput('3. Reading file contents...')
            ->expectsOutput('   ✓ Read successful')
            ->expectsOutput('4. Deleting test file...')
            ->expectsOutput('   ✓ Delete successful')
            ->expectsOutput('✅ S3 connectivity test PASSED')
            ->assertExitCode(0);
    }

    public function test_command_fails_when_write_operation_fails()
    {
        // Mock S3 disk that throws exception on put
        Storage::shouldReceive('disk')
            ->with('s3')
            ->andReturnSelf();
        
        Storage::shouldReceive('put')
            ->andThrow(new \Exception('S3 write failed'));

        $this->artisan('storage:test-s3')
            ->expectsOutput('Testing S3 connectivity...')
            ->expectsOutput('1. Writing test file...')
            ->expectsOutput('❌ S3 connectivity test FAILED')
            ->expectsOutputToContain('Error: S3 write failed')
            ->assertExitCode(1);
    }

    public function test_command_fails_when_file_not_found_after_write()
    {
        Storage::fake('s3');
        
        // Mock exists to return false
        Storage::shouldReceive('disk')
            ->with('s3')
            ->andReturnSelf();
        
        Storage::shouldReceive('put')
            ->andReturn(true);
        
        Storage::shouldReceive('exists')
            ->andReturn(false);

        $this->artisan('storage:test-s3')
            ->expectsOutput('Testing S3 connectivity...')
            ->expectsOutput('1. Writing test file...')
            ->expectsOutput('   ✓ Write successful')
            ->expectsOutput('2. Checking file exists...')
            ->expectsOutput('❌ S3 connectivity test FAILED')
            ->expectsOutputToContain('Error: File not found after write')
            ->assertExitCode(1);
    }

    public function test_command_fails_when_file_contents_do_not_match()
    {
        Storage::fake('s3');
        
        // Mock get to return wrong content
        Storage::shouldReceive('disk')
            ->with('s3')
            ->andReturnSelf();
        
        Storage::shouldReceive('put')
            ->andReturn(true);
        
        Storage::shouldReceive('exists')
            ->andReturn(true);
        
        Storage::shouldReceive('get')
            ->andReturn('wrong content');

        $this->artisan('storage:test-s3')
            ->expectsOutput('Testing S3 connectivity...')
            ->expectsOutput('1. Writing test file...')
            ->expectsOutput('   ✓ Write successful')
            ->expectsOutput('2. Checking file exists...')
            ->expectsOutput('   ✓ File exists')
            ->expectsOutput('3. Reading file contents...')
            ->expectsOutput('❌ S3 connectivity test FAILED')
            ->expectsOutputToContain('Error: File contents do not match')
            ->assertExitCode(1);
    }

    public function test_command_cleans_up_test_file_on_failure()
    {
        Storage::fake('s3');
        
        // Create a test file
        $testFile = 'test-cleanup.txt';
        Storage::disk('s3')->put($testFile, 'test content');
        
        // Mock to throw exception after write
        Storage::shouldReceive('disk')
            ->with('s3')
            ->andReturnSelf();
        
        Storage::shouldReceive('put')
            ->andReturn(true);
        
        Storage::shouldReceive('exists')
            ->andThrow(new \Exception('Test failure'));
        
        Storage::shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $this->artisan('storage:test-s3')
            ->assertExitCode(1);
    }
}
