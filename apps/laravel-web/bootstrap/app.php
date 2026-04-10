<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureUserRole;

$envPath = dirname(__DIR__).'/.env';

if (empty($_ENV['APP_KEY']) && is_file($envPath)) {
    $envContents = file_get_contents($envPath);

    if ($envContents !== false && preg_match('/^APP_KEY=(.*)$/m', $envContents, $matches) === 1) {
        $appKey = trim($matches[1], "\"'\r\n");

        if ($appKey !== '') {
            $_ENV['APP_KEY'] = $appKey;
            $_SERVER['APP_KEY'] = $appKey;
            putenv('APP_KEY='.$appKey);
        }
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
