<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\Cart\CartService;
use App\Services\Category\CategoryService;
use App\Services\Order\PendingOrderCount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly CategoryService $categories,
        private readonly PendingOrderCount $pendingOrders,
        private readonly CartService $cart,
    ) {}

    public function rootView(Request $request): string
    {
        return $request->is('admin', 'admin/*') ? 'admin' : 'storefront';
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'cart' => ['count' => fn () => $this->cart->count()],
            'miniCart' => Inertia::optional(fn () => $this->miniCart()),
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'username', 'role']),
            ],
            'admin' => fn () => $request->user() && $request->is('admin', 'admin/*')
                ? ['pendingOrders' => $this->pendingOrders->get()]
                : null,
            'categories' => fn () => $request->is('admin', 'admin/*')
                ? []
                : $this->categories->activeForStorefront(),
            'company' => fn () => Company::first(),
        ];
    }

    private function miniCart(): array
    {
        $lines = $this->cart->lines();

        return [
            'lines' => $lines,
            'subtotal' => $this->cart->subtotal($lines),
            'maxQty' => (int) config('shop.max_qty_per_item'),
        ];
    }
}