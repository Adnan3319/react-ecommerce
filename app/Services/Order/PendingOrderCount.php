<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

/**
 * Pending orders for the admin sidebar badge. Cached for a minute and forgotten whenever an order
 * is placed or changes status, so the badge is never stale after an action.
 */
class PendingOrderCount
{
    private const CACHE_KEY = 'orders.pending_count';

    private const TTL_SECONDS = 60;

    public function get(): int
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::TTL_SECONDS,
            fn (): int => Order::query()->where('status', OrderStatus::Pending)->count(),
        );
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
