<?php

namespace App\Exceptions;

use App\Http\Responses\ApiError;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return parent::render($request, $e);
        }

        if ($e instanceof ApiException) {
            return ApiResponse::error($e->getApiError(), $e->getCode());
        }

        $e = $this->prepareException($e);

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
    }
}