<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Endpoint ini dipanggil oleh visibilitychange/pagehide dan sendBeacon.
        // Pada kondisi halaman ditinggalkan, browser dapat mengirim request
        // tanpa token form terbaru. Endpoint tetap aman karena berada di balik
        // middleware student dan controller memeriksa kepemilikan exam group.
        $middleware->validateCsrfTokens(except: [
            'student/examination-violation',
        ]);

        // Trust Cloudflare proxy
        $middleware->trustProxies(
            at: '*'
        );

        // Middleware alias
        $middleware->alias([
            'student' => \App\Http\Middleware\AuthStudent::class,
            'admin.only' => \App\Http\Middleware\AdminOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
