<?php

namespace App\Services\Product;

use App\Services\Dashboard\DashboardCache;
use Illuminate\Support\Facades\Cache;

/**
 * The product domain's cache keys. Anything that changes a product or its stock flushes them.
 */
class ProductCache
{
    public const FEATURED_KEY = 'products.featured';

    public const NEW_ARRIVALS_KEY = 'products.new_arrivals';

    private const PAGE_KEY_PREFIX = 'products.page.';

    public function __construct(private readonly DashboardCache $dashboard) {}

    public static function pageKey(int $productId): string
    {
        return self::PAGE_KEY_PREFIX.$productId;
    }

    /**
     * Clears the Home product lists and, when the product is known, its product page.
     */
    public function flush(?int $productId = null): void
    {
        Cache::forget(self::FEATURED_KEY);
        Cache::forget(self::NEW_ARRIVALS_KEY);
        // The dashboard lists low-stock variants.
        $this->dashboard->forget();

        if ($productId !== null) {
            Cache::forget(self::pageKey($productId));
        }
    }
}
