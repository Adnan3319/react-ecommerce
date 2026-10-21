<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Category\CategoryService;
use App\Services\Product\ProductCache;
use App\Services\Product\StockService;
use App\Services\Storefront\CatalogFilterCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
    $this->category = Category::factory()->create();
    $this->other = Category::factory()->create();
});

function warmListingFilters(): void
{
    foreach ([null, ...Category::pluck('id')->all()] as $categoryId) {
        Cache::put(CatalogFilterCache::key($categoryId), ['stale'], 600);
    }

    Cache::put('categories.unrelated', ['keep']);
}

function listingFiltersCached(): array
{
    return collect([null, ...Category::withTrashed()->pluck('id')->all()])
        ->filter(fn (?int $categoryId) => Cache::has(CatalogFilterCache::key($categoryId)))
        ->values()
        ->all();
}

it('clears the filters of every category and of all products', function () {
    warmListingFilters();

    app(CatalogFilterCache::class)->flush();

    expect(listingFiltersCached())->toBe([])
        ->and(Cache::has('categories.unrelated'))->toBeTrue();
});

describe('product and stock writes', function () {
    it('clears the filters when ProductCache flushes', function () {
        warmListingFilters();

        app(ProductCache::class)->flush();

        expect(listingFiltersCached())->toBe([]);
    });

    it('clears the filters when stock changes', function () {
        $variant = ProductVariant::factory()->create(['stock' => 5]);
        warmListingFilters();

        app(StockService::class)->adjust($variant, 9);

        expect(listingFiltersCached())->toBe([]);
    });

    it('clears the filters when an admin updates a product', function () {
        $product = Product::factory()->simple()->create(['category_id' => $this->category->id]);
        warmListingFilters();

        actingAsAdmin()->post(route('admin.products.update', $product), ['_method' => 'put', 'status' => 'inactive'])->assertRedirect();

        expect(listingFiltersCached())->toBe([]);
    });

    it('clears the filters when an admin deletes a product', function () {
        $product = Product::factory()->simple()->create(['category_id' => $this->category->id]);
        warmListingFilters();

        actingAsAdmin()->delete(route('admin.products.destroy', $product))->assertRedirect();

        expect(listingFiltersCached())->toBe([]);
    });
});

describe('category writes', function () {
    it('clears the filters when a category is created', function () {
        warmListingFilters();

        app(CategoryService::class)->create(['name' => 'Bags', 'slug' => 'bags', 'status' => 'active', 'sort_order' => 1], null);

        expect(listingFiltersCached())->toBe([]);
    });

    it('clears the filters when a category is updated', function () {
        warmListingFilters();

        app(CategoryService::class)->update($this->category, ['status' => 'inactive'], null, false);

        expect(listingFiltersCached())->toBe([]);
    });

    it('clears the filters when a category is deleted', function () {
        warmListingFilters();

        app(CategoryService::class)->delete($this->category);

        expect(listingFiltersCached())->toBe([]);
    });
});
