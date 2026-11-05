<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',   // all API routes get the /api prefix
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Security headers on every API response.
        $middleware->api(append: [
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The API always answers with JSON, never with an HTML page or a redirect.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));

        $exceptions->render(function (AuthenticationException $e, Request $request): ?JsonResponse {
            return $request->is('api/*')
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : null;
        });

        // Bad, expired or missing-signature JWTs become a clean 401.
        $exceptions->render(function (JWTException $e, Request $request): ?JsonResponse {
            return $request->is('api/*')
                ? response()->json(['message' => 'Invalid or expired token.'], 401)
                : null;
        });
    })->create();
