<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\Cache;

/**
 * The product domain's cache key. Anything that changes a product or its stock flushes it.
 */
class ProductCache
{
    public const FEATURED_KEY = 'products.featured';

    public function flush(): void
    {
        Cache::forget(self::FEATURED_KEY);
    }
}
