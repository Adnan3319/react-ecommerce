<?php

namespace App\Http\Responses;

final class ApiError
{
    public function __construct(
        public readonly string $message,
        public readonly array $errors = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'errors'  => $this->errors,
        ];
    }
}
