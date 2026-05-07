<?php

use App\Exceptions\DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (DomainException $e, Request $request): ?JsonResponse {
            return $e->render($request);
        });

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
                return null;
            }

            if (app()->hasDebugModeEnabled() || app()->environment('local', 'testing')) {
                return null;
            }

            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            if ($status < 500) {
                return null;
            }

            return new JsonResponse(
                ['message' => 'Server error.', 'code' => 'INTERNAL'],
                $status,
            );
        });
    })->create();
