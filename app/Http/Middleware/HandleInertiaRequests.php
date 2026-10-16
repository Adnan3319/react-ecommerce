<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Pick the root template per route group: admin panel vs storefront.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     */
    public function rootView(Request $request): string
    {
        return $request->is('admin', 'admin/*') ? 'admin' : 'storefront';
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            // Placeholder until the server-side cart exists.
            'cart' => ['count' => 0],
            'auth' => [
                'user' => $request->user(),
            ],
        ];
    }
}