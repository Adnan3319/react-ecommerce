<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

final class ApiResponse
{
    private function __construct() {}

    /**
     * Success Response
     */
    public static function success(
        mixed $data = null,
        string $message = '',
        int $status = 200,
        array $meta = []
    ): JsonResponse {

        if ($data instanceof ResourceCollection) {
            $response = $data->response()->getData(true);

            $payload = [
                'success' => true,
                'status'  => $status,
                'message' => $message,
                'data'    => $response['data'] ?? [],
            ];

            if (!empty($response['meta'])) {
                $payload['meta'] = [
                    'pagination' => [
                        'total'        => $response['meta']['total'] ?? 0,
                        'per_page'     => $response['meta']['per_page'] ?? 0,
                        'current_page' => $response['meta']['current_page'] ?? 1,
                        'last_page'    => $response['meta']['last_page'] ?? 1,
                        'from'         => $response['meta']['from'] ?? null,
                        'to'           => $response['meta']['to'] ?? null,
                    ],
                ] + $meta;
            }

            if (!empty($response['links'])) {
                $payload['links'] = [
                    'first' => $response['links']['first'] ?? null,
                    'last'  => $response['links']['last'] ?? null,
                    'prev'  => $response['links']['prev'] ?? null,
                    'next'  => $response['links']['next'] ?? null,
                ];
            }

            return response()->json(self::filter($payload), $status);
        }

        return response()->json(self::filter([
            'success' => true,
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
            'meta'    => $meta,
        ]), $status);
    }

    /**
     * Error Response
     */
    public static function error(
        ApiError $error,
        int $status = 400,
        array $meta = []
    ): JsonResponse {
        return response()->json(self::filter([
            'success' => false,
            'status' => $status,
            'message' => $error->message,
            'errors' => $error->errors,
            'meta' => $meta,
        ]), $status);
    }

    /**
     * Unhandled Exception Response (debug-aware)
     */
    public static function exception(
        Throwable $e,
        int $status = 500
    ): JsonResponse {

        return response()->json(self::filter([
            'success' => false,
            'status'  => $status,
            'message' => 'Something went wrong',
            'errors'  => config('app.debug')
                ? [
                    'exception' => class_basename($e),
                    'message'   => $e->getMessage(),
                ]
                : null,
        ]), $status);
    }

    /**
     * Remove null, empty array, and empty string values.
     */
    private static function filter(array $payload): array
    {
        return array_filter(
            $payload,
            fn ($value) => $value !== null && $value !== [] && $value !== ''
        );
    }
}