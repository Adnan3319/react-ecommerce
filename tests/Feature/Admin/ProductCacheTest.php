<?php

use App\Models\Product;
use App\Services\Home\HomeService;
use App\Services\Product\ProductCache;
use App\Services\Product\StockService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
    actingAsAdmin();
    Cache::put(ProductCache::FEATURED_KEY, ['stale'], 3600);
    Cache::put('unrelated.key', 'kept', 3600);
});

describe('invalidation', function () {
    it('clears only the featured products cache when a product is created', function () {
        $this->post(route('admin.products.store'), simpleProductInput())->assertSessionHasNoErrors();

        expect(Cache::has(ProductCache::FEATURED_KEY))->toBeFalse()
            ->and(Cache::get('unrelated.key'))->toBe('kept');
    });

    it('clears the cache on update and delete', function () {
        $product = Product::factory()->simple()->create();

        updateProduct($product, ['status' => 'inactive']);
        expect(Cache::has(ProductCache::FEATURED_KEY))->toBeFalse();

        Cache::put(ProductCache::FEATURED_KEY, ['stale'], 3600);
        $this->delete(route('admin.products.destroy', $product));
        expect(Cache::has(ProductCache::FEATURED_KEY))->toBeFalse();
    });

    it('clears the cache when stock changes', function () {
        $variant = Product::factory()->simple()->create()->variants()->sole();

        app(StockService::class)->adjust($variant, 3);

        expect(Cache::has(ProductCache::FEATURED_KEY))->toBeFalse();
    });
});

describe('home', function () {
    it('returns featured active in-stock products as plain arrays with the cheapest in-stock price', function () {
        $featured = Product::factory()->withVariants(2)->featured()->create(['name' => 'Polo']);
        $featured->variants()->orderBy('sort_order')->first()->update(['price' => 300000, 'sale_price' => 240000]);
        $featured->variants()->orderBy('sort_order')->get()->last()->update(['price' => 250000]);
        Product::factory()->simple()->create(['name' => 'Not featured']);
        Product::factory()->simple()->featured()->inactive()->create(['name' => 'Inactive']);
        Product::factory()->simple(stock: 0)->featured()->create(['name' => 'Sold out']);
        Cache::forget(ProductCache::FEATURED_KEY);

        $cards = app(HomeService::class)->data()['featuredProducts'];

        expect($cards)->toHaveCount(1)
            ->and($cards[0])->toMatchArray(['name' => 'Polo', 'slug' => $featured->slug, 'price' => 300000, 'sale_price' => 240000, 'discount_percent' => 20])
            ->and(Cache::has(ProductCache::FEATURED_KEY))->toBeTrue();
    });
});
