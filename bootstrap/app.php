<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hanya X-Forwarded-Proto yang dipercaya (deteksi HTTPS di balik proxy). X-Forwarded-For tidak,
        // supaya IP klien tidak bisa dipalsukan dan pembatas percobaan login tetap berlaku.
        $middleware->trustProxies('*', SymfonyRequest::HEADER_X_FORWARDED_PROTO);

        $middleware->redirectGuestsTo('/admin/login');

        $middleware->validateCsrfTokens(except: [
            'webhook/paywuz',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
