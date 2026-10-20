<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers the campaign a visitor arrived from (UTM parameters and Facebook's fbclid) in the session, so
 * it is still known when the order is placed pages later. A request that carries any of them replaces
 * the whole stored set (values of an older landing are not merged in). Storefront pages only.
 */
class CaptureAttribution
{
    public const SESSION_KEY = 'attribution';

    private const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'fbclid'];

    private const MAX_LENGTH = 255;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! $request->is('admin', 'admin/*')) {
            $this->capture($request);
        }

        return $next($request);
    }

    private function capture(Request $request): void
    {
        $found = [];

        foreach (self::KEYS as $key) {
            $value = $request->query($key);

            if (is_string($value) && trim($value) !== '') {
                $found[$key] = mb_substr(trim($value), 0, self::MAX_LENGTH);
            }
        }

        if ($found !== []) {
            $request->session()->put(self::SESSION_KEY, $found);
        }
    }
}
