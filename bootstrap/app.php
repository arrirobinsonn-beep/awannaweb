<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureProfileComplete;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias middleware
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'check.role' => CheckRole::class,
            'active' => EnsureUserIsActive::class,
            'profile.complete' => EnsureProfileComplete::class,
        ]);

        // Jalankan di semua web route (urutan: active dulu, baru profile.complete)
        $middleware->appendToGroup('web', EnsureUserIsActive::class);
        $middleware->appendToGroup('web', EnsureProfileComplete::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
