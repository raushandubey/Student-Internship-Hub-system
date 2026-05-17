<?php

namespace Tests\Unit\Services;

use App\Models\Profile;
use App\Models\User;
use App\Services\ResumeOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class ResumeOptimizationStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_reads_resume_from_default_local_disk(): void
    {
        config(['filesystems.default' => 'local']);
        Storage::fake('local');
        Storage::fake('public');

        $path = 'resumes/local-server-resume.pdf';
        Storage::disk('local')->put($path, $this->samplePdf());

        $profile = $this->profileWithResume($path);
        $resolved = $this->resolveResumePath($profile);

        $this->assertIsString($resolved);
        $this->assertFileExists($resolved);
        $this->assertStringStartsWith('%PDF', file_get_contents($resolved));
    }

    public function test_resolver_reads_resume_content_from_r2_disk(): void
    {
        config(['filesystems.default' => 'r2']);
        Storage::fake('r2');

        $path = 'resumes/r2-resume.pdf';
        $content = $this->samplePdf();
        Storage::disk('r2')->put($path, $content);

        $profile = $this->profileWithResume($path);
        $resolved = $this->resolveResumePath($profile);

        $this->assertIsArray($resolved);
        $this->assertSame('s3_content', $resolved['mode']);
        $this->assertSame($path, $resolved['path']);
        $this->assertSame($content, $resolved['content']);
    }

    private function resolveResumePath(Profile $profile): string|array|null
    {
        $service = app(ResumeOptimizationService::class);
        $method = new ReflectionMethod($service, 'resolveResumePath');
        $method->setAccessible(true);

        return $method->invoke($service, $profile);
    }

    private function profileWithResume(string $path): Profile
    {
        $user = User::factory()->create(['role' => 'student']);

        return Profile::factory()->create([
            'user_id' => $user->id,
            'resume_path' => $path,
        ]);
    }

    private function samplePdf(): string
    {
        return file_get_contents(base_path('tests/fixtures/sample.pdf'));
    }
}
