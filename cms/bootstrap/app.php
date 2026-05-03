<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
        // A rota de page-view é pública (POST externo do site estático)
        $middleware->validateCsrfTokens(except: [
            'api/page-view',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
