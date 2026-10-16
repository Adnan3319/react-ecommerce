<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RejectXss
{
    protected string $pattern = '/(<\s*script\b|javascript\s*:|on\w+\s*=|<\s*iframe\b|<\s*object\b|<\s*embed\b)/i';

    public function handle(Request $request, Closure $next)
    {
        foreach ($request->all() as $key => $value) {

            //Skip files entirely
            if ($value instanceof UploadedFile) {
                continue;
            }

            //Skip arrays (handled below)
            if (is_array($value)) {
                $this->scanArray($value);
                continue;
            }

            //Only scan strings
            if (!is_string($value)) {
                continue;
            }

            // Allow editor fields
            if (str_ends_with($key, '_editor')) {
                if (preg_match($this->pattern, $value)) {
                    $this->fail($key);
                }
                continue;
            }

            if ($value !== strip_tags($value) || preg_match($this->pattern, $value)) {
                $this->fail($key);
            }
        }

        return $next($request);
    }

    protected function scanArray(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($value instanceof UploadedFile || !is_string($value)) {
                continue;
            }

            if ($value !== strip_tags($value) || preg_match($this->pattern, $value)) {
                $this->fail($key);
            }
        }
    }

    protected function fail(string $key): void
    {
        throw ValidationException::withMessages([
            $key => ['Unsafe input detected.'],
        ]);
    }
}
