<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\CheckWorkspaceActive::class,
            \App\Http\Middleware\CheckTrialExpiration::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '/billing/webhook',
            '/billing/checkout',
            '/billing/success',
            '/billing/cancel',
        ]);

        $middleware->alias([
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'workspace_admin' => \App\Http\Middleware\EnsureWorkspaceAdmin::class,
        ]);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('banking:sync')->dailyAt('07:00');
        $schedule->command('banking:sync')->dailyAt('14:00');
    })->create();
