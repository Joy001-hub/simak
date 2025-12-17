<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

require_once __DIR__ . '/mbstring_polyfill.php';

// Set NATIVEPHP_STORAGE_PATH only in production builds (no .env file)
$isProductionBuild = !file_exists(__DIR__ . '/../.env');

if ($isProductionBuild && getenv('APPDATA')) {
    $nativeStorage = rtrim(getenv('APPDATA'), '\\/') . DIRECTORY_SEPARATOR . 'Simak' . DIRECTORY_SEPARATOR . 'storage';

    if (!is_dir($nativeStorage)) {
        @mkdir($nativeStorage, 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'app', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'license', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'framework', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'sessions', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'logs', 0755, true);
    }

    $dbPath = $nativeStorage . DIRECTORY_SEPARATOR . 'database.sqlite';
    if (!file_exists($dbPath)) {
        @touch($dbPath);
    }

    putenv("NATIVEPHP_STORAGE_PATH={$nativeStorage}");
    $_ENV['NATIVEPHP_STORAGE_PATH'] = $nativeStorage;
    $_SERVER['NATIVEPHP_STORAGE_PATH'] = $nativeStorage;

    // Only set NATIVEPHP_RUNNING in production - REMOVED from development!
    // NativePHP Electron will set this automatically when running the packaged app
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(\App\Console\Commands\ValidateLicenseCommand::class)->everyFiveMinutes();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureDatabaseMigrated::class,
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\ContentSecurityPolicy::class,
            \App\Http\Middleware\EnsureNativeCookie::class,
            \App\Http\Middleware\EnsureLicenseIsValid::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
