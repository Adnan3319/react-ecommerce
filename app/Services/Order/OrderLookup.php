<?php

namespace App\Services\Order;

use App\Models\Order;

class OrderLookup
{
    /** How long the thank-you link of an order keeps working. */
    public const SUCCESS_PAGE_DAYS = 7;

    /**
     * The order behind a thank-you link, or null when the token is unknown or older than a week.
     */
    public function forSuccessPage(string $token): ?Order
    {
        return Order::query()
            ->with(['items', 'customer'])
            ->where('public_token', $token)
            ->where('created_at', '>=', now()->subDays(self::SUCCESS_PAGE_DAYS))
            ->first();
    }
}
