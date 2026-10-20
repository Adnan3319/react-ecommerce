<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\CaptureAttribution;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Responses\ApiError;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('home'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.dashboard')
            : route('home'));

        $middleware->web(append: [
            CaptureAttribution::class,
            CaptureAttribution::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Shudhu /api/* er jonno custom JSON response. Web/Inertia ager moto cholbe.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ApiException) {
                return ApiResponse::error($e->getApiError(), $e->getCode());
            }

            if ($e instanceof ValidationException) {
                return ApiResponse::error(
                    new ApiError('Validation failed', $e->errors()),
                    422
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error(new ApiError('Unauthenticated'), 401);
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponse::error(new ApiError('Forbidden'), 403);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return ApiResponse::error(new ApiError('Resource not found'), 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                return ApiResponse::error(
                    new ApiError($e->getMessage() ?: 'Request failed'),
                    $e->getStatusCode()
                );
            }

            return ApiResponse::exception($e);
        });
    })->create();
