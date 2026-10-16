<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ValidateMimeType
{
    public function handle(Request $request, Closure $next,  ...$allowedMimes)
    {
        foreach ($request->allFiles() as $file) {
            $this->validate($file, $allowedMimes);
        }

        return $next($request);
    }

    private function validate(mixed $file, array $allowedMimes): void
    {
        if ($file instanceof UploadedFile && ! in_array($file->getMimeType(), $allowedMimes, true)) {
            abort(415, 'Unsupported media type');
        }

        if (is_array($file)) {
            foreach ($file as $single) {
                $this->validate($single, $allowedMimes);
            }
        }
    }
}
