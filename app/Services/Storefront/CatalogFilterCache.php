<?php

namespace App\Services\Storefront;

use App\Models\Category;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * The listing's "available filters" (price bounds, option heads) cached per category. The `database`
 * cache driver has no tags, so a flush forgets the key of every category plus the all-products key.
 */
class CatalogFilterCache
{
    private const TTL_SECONDS = 600;

    private const KEY_PREFIX = 'catalog.filters.';

    private const ALL_KEY_SUFFIX = 'all';

    /**
     * @param  Closure(): array<string, mixed>  $build
     * @return array<string, mixed>
     */
    public function remember(?int $categoryId, Closure $build): array
    {
        return Cache::remember(self::key($categoryId), self::TTL_SECONDS, $build);
    }

    /**
     * Called on every product, stock and category write.
     */
    public function flush(): void
    {
        Cache::forget(self::key(null));

        Category::withTrashed()->pluck('id')->each(fn (int $id) => Cache::forget(self::key($id)));
    }

    public static function key(?int $categoryId): string
    {
        return self::KEY_PREFIX.($categoryId ?? self::ALL_KEY_SUFFIX);
    }
}
