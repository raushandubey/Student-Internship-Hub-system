<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Manual Job Triggers (for demo/testing)
 * 
 * CRITICAL: Do NOT import or instantiate Job classes at file level.
 * Use string class names to avoid autoload issues during build.
 */
Artisan::command('app:mark-stale', function () {
    $this->info('Dispatching MarkStaleApplications job...');
    $jobClass = \App\Jobs\MarkStaleApplications::class;
    dispatch(new $jobClass());
    $this->info('Job dispatched! Run "php artisan queue:work" to process.');
})->purpose('Manually trigger stale applications job');

Artisan::command('app:daily-summary', function () {
    $this->info('Dispatching GenerateDailyAdminSummary job...');
    $jobClass = \App\Jobs\GenerateDailyAdminSummary::class;
    dispatch(new $jobClass());
    $this->info('Job dispatched! Run "php artisan queue:work" to process.');
})->purpose('Manually trigger daily summary job');

Artisan::command('app:run-jobs-sync', function () {
    $this->info('Running jobs synchronously for demo...');
    
    $this->info('1. Marking stale applications...');
    $markStaleJob = new \App\Jobs\MarkStaleApplications();
    $markStaleJob->handle(app(\App\Services\ApplicationService::class));
    
    $this->info('2. Generating daily summary...');
    $summaryJob = new \App\Jobs\GenerateDailyAdminSummary();
    $summaryJob->handle();
    
    $this->info('Done! Check storage/logs/laravel.log for results.');
})->purpose('Run all scheduled jobs synchronously (for demo)');

/**
 * Scheduled Jobs Configuration
 * 
 * PRODUCTION SETUP:
 * Add this to crontab (Linux/Mac):
 * * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
 * 
 * For Windows Task Scheduler, create a task that runs every minute:
 * php artisan schedule:run
 * 
 * DEMO/TESTING:
 * php artisan app:run-jobs-sync    # Run all jobs immediately
 * php artisan schedule:list        # View scheduled jobs
 * php artisan schedule:test        # Test run a specific job
 */

// Run daily at 6 AM - Mark stale applications
// Use string class name to avoid instantiation during file load
Schedule::call(function () {
    $jobClass = \App\Jobs\MarkStaleApplications::class;
    dispatch(new $jobClass());
})->dailyAt('06:00')
    ->name('mark-stale-applications')
    ->withoutOverlapping()
    ->onOneServer();

// Run daily at 7 AM - Generate admin summary
// Use string class name to avoid instantiation during file load
Schedule::call(function () {
    $jobClass = \App\Jobs\GenerateDailyAdminSummary::class;
    dispatch(new $jobClass());
})->dailyAt('07:00')
    ->name('daily-admin-summary')
    ->withoutOverlapping()
    ->onOneServer();
