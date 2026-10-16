<?php

namespace App\Exceptions;

use App\Http\Responses\ApiError;
use RuntimeException;
use Throwable;

final class ApiException extends RuntimeException
{
    private ApiError $apiError;

    public function __construct(
        string $message,
        int $code = 400,
        array $errors = [],
        ?Throwable $previous = null
    ) {
        if ($code < 400 || $code > 599) {
            $code = 400;
        }

        parent::__construct($message, $code, $previous);
        $this->apiError = new ApiError($message, $errors);
    }

    public function getApiError(): ApiError
    {
        return $this->apiError;
    }
}