<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\Internship;
use App\Policies\ApplicationPolicy;
use App\Policies\InternshipPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies (Phase 9: Authorization)
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(Internship::class, InternshipPolicy::class);

        // Define gates for admin actions
        Gate::define('admin-access', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('student-access', function ($user) {
            return $user->role === 'student';
        });

        // Gate for analytics access (admin only)
        Gate::define('view-analytics', function ($user) {
            return $user->role === 'admin';
        });
    }
}
