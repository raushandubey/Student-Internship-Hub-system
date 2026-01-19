<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

// ============================================================================
// RUNTIME CONFIG OVERRIDE - Force no-database mode
// ============================================================================
// This runs BEFORE any config cache is loaded, ensuring Laravel boots
// without database dependency even if config is cached with DB settings.
// ============================================================================

// Force session to use array driver (in-memory, no DB)
$_ENV['SESSION_DRIVER'] = 'array';
$_SERVER['SESSION_DRIVER'] = 'array';
putenv('SESSION_DRIVER=array');

// Force cache to use array driver (in-memory, no DB)
$_ENV['CACHE_DRIVER'] = 'array';
$_SERVER['CACHE_DRIVER'] = 'array';
putenv('CACHE_DRIVER=array');

// Force queue to use sync (no DB)
$_ENV['QUEUE_CONNECTION'] = 'sync';
$_SERVER['QUEUE_CONNECTION'] = 'sync';
putenv('QUEUE_CONNECTION=sync');

// Disable database connection attempts
$_ENV['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';
$_SERVER['DB_DATABASE'] = ':memory:';
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        
        // Remove StartSession middleware from web group to prevent DB access
        $middleware->remove(\Illuminate\Session\Middleware\StartSession::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
