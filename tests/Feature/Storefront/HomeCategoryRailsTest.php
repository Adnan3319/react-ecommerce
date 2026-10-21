<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\Category\CategoryService;
use App\Services\Product\ProductCache;
use App\Services\Product\StockService;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->kitchen = Category::factory()->create(['name' => 'Kitchenware', 'slug' => 'kitchenware', 'sort_order' => 1]);
    $this->fashion = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion', 'sort_order' => 2]);
});

function railNames(?string $slug = null): array
{
    $rails = test()->get(route('home'))->viewData('page')['props']['categoryRails'];

    return collect($rails)
        ->when($slug !== null, fn ($rails) => $rails->where('category.slug', $slug))
        ->flatMap(fn (array $rail) => collect($rail['products'])->pluck('name'))
        ->all();
}

it('sends one rail per root category in category order with the card shape', function () {
    Product::factory()->simple()->create(['name' => 'Pan', 'category_id' => $this->kitchen->id]);
    Product::factory()->simple()->create(['name' => 'Kurti', 'category_id' => $this->fashion->id]);

    $this->get(route('home'))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('categoryRails', 2)
        ->where('categoryRails.0.category', ['name' => 'Kitchenware', 'slug' => 'kitchenware'])
        ->where('categoryRails.1.category.slug', 'fashion')
        ->has('categoryRails.0.products.0', fn (AssertableInertia $card) => $card
            ->where('name', 'Pan')
            ->hasAll(['id', 'slug', 'thumb', 'srcset', 'price', 'sale_price', 'discount_percent', 'in_stock', 'has_variants', 'default_variant_id', 'options_summary', 'is_new'])));
});

it('lists the newest products first and at most ten per rail', function () {
    foreach (range(1, 12) as $number) {
        Product::factory()->simple()->create(['name' => "Pan {$number}", 'category_id' => $this->kitchen->id]);
    }

    $names = railNames('kitchenware');

    expect($names)->toHaveCount(10)
        ->and($names[0])->toBe('Pan 12')
        ->and($names[9])->toBe('Pan 3');
});

it('includes the products of active sub-categories in the root rail', function () {
    $child = Category::factory()->create(['parent_id' => $this->kitchen->id]);
    Product::factory()->simple()->create(['name' => 'Cookware item', 'category_id' => $child->id]);

    expect(railNames('kitchenware'))->toBe(['Cookware item']);
});

it('keeps out sold-out, inactive and other-category products', function () {
    Product::factory()->simple()->create(['name' => 'Shown', 'category_id' => $this->kitchen->id]);
    Product::factory()->simple(stock: 0)->create(['name' => 'Sold out', 'category_id' => $this->kitchen->id]);
    Product::factory()->simple()->inactive()->create(['name' => 'Inactive', 'category_id' => $this->kitchen->id]);
    Product::factory()->simple()->create(['name' => 'Elsewhere', 'category_id' => $this->fashion->id]);

    expect(railNames('kitchenware'))->toBe(['Shown']);
});

it('skips categories without in-stock products and inactive categories', function () {
    Product::factory()->simple(stock: 0)->create(['category_id' => $this->kitchen->id]);
    $off = Category::factory()->inactive()->create();
    Product::factory()->simple()->create(['category_id' => $off->id]);
    Product::factory()->simple()->create(['name' => 'Kurti', 'category_id' => $this->fashion->id]);

    $rails = $this->get(route('home'))->viewData('page')['props']['categoryRails'];

    expect(collect($rails)->pluck('category.slug')->all())->toBe(['fashion']);
});

describe('cache', function () {
    it('serves the rails from their own cache key', function () {
        Product::factory()->simple()->create(['category_id' => $this->kitchen->id]);

        $this->get(route('home'));

        expect(Cache::has(ProductCache::CATEGORY_RAILS_KEY))->toBeTrue();
    });

    it('clears the rails on a product write', function () {
        $product = Product::factory()->simple()->create(['category_id' => $this->kitchen->id]);
        $this->get(route('home'));

        actingAsAdmin()->post(route('admin.products.update', $product), ['_method' => 'put', 'status' => 'inactive']);

        expect(Cache::has(ProductCache::CATEGORY_RAILS_KEY))->toBeFalse();
    });

    it('clears the rails on a stock write', function () {
        $variant = Product::factory()->simple()->create(['category_id' => $this->kitchen->id])->variants()->sole();
        $this->get(route('home'));

        app(StockService::class)->adjust($variant, 0);

        expect(Cache::has(ProductCache::CATEGORY_RAILS_KEY))->toBeFalse()
            ->and(railNames())->toBe([]);
    });

    it('clears the rails on a category write', function () {
        Product::factory()->simple()->create(['category_id' => $this->kitchen->id]);
        $this->get(route('home'));

        app(CategoryService::class)->update($this->kitchen, ['status' => 'inactive'], null, false);

        expect(Cache::has(ProductCache::CATEGORY_RAILS_KEY))->toBeFalse()
            ->and(railNames())->toBe([]);
    });
});
