<?php

namespace App\Http\Middleware;

use App\Http\Resources\Admin\Notification\NotificationResource;
use App\Models\Company;
use App\Services\Cart\CartService;
use App\Services\Category\CategoryService;
use App\Services\Company\CompanyBranding;
use App\Services\Contact\UnreadMessageCount;
use App\Services\Notification\NotificationService;
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
        private readonly CompanyBranding $branding,
        private readonly UnreadMessageCount $unreadMessages,
        private readonly NotificationService $notifications,
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
            'admin' => fn () => $this->isAdminSession($request)
                ? ['pendingOrders' => $this->pendingOrders->get(), 'unreadMessages' => $this->unreadMessages->get()]
                : null,
            'notifications' => fn () => $this->isAdminSession($request)
                ? ['unreadCount' => $this->notifications->unreadCount($request->user())]
                : null,
            // Loaded only when the bell opens (router.reload with `only`).
            'notificationItems' => Inertia::optional(fn () => $this->isAdminSession($request)
                ? NotificationResource::collection($this->notifications->latest($request->user()))->resolve()
                : []),
            'categories' => fn () => $request->is('admin', 'admin/*')
                ? []
                : $this->categories->activeForStorefront(),
            'company' => fn () => Company::first(),
            'branding' => fn () => $this->branding->data(),
        ];
    }

    private function isAdminSession(Request $request): bool
    {
        return $request->user() !== null && $request->is('admin', 'admin/*');
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