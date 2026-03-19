<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Session\TokenMismatchException;
use Modules\Shared\Presentation\Exception\InputValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \Modules\Shared\Infrastructure\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'owner' => \Modules\Shared\Infrastructure\Http\Middleware\EnsureActorIsOwner::class,
            'portal' => \Modules\Shared\Infrastructure\Http\Middleware\EnsureActorIsGuest::class,
            'tenant' => \Modules\Shared\Infrastructure\Http\Middleware\SetTenantContext::class,
            'type' => \Modules\Shared\Infrastructure\Http\Middleware\EnsureActorType::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (InputValidationException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'errors' => $e->errors,
            ], 422);
        });

        $exceptions->renderable(function (TokenMismatchException $e, $request) {
            if ($request->inertia()) {
                return redirect('/login');
            }
        });
    })->create();
