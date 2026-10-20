<?php

use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia;

beforeEach(fn () => actingAsAdmin());

/**
 * Names of the products on the list page for the given query string.
 *
 * @return array<int, string>
 */
function listedProductNames(array $query = []): array
{
    $page = test()->get(route('admin.products.index', $query))->assertOk()->viewData('page');

    return collect($page['props']['products']['data'])->pluck('name')->all();
}

describe('filters', function () {
    it('searches by product name and by variant SKU', function () {
        $shirt = Product::factory()->simple()->create(['name' => 'Cotton Shirt']);
        Product::factory()->simple()->create(['name' => 'Leather Belt']);

        $sku = $shirt->variants()->sole()->sku;

        expect(listedProductNames(['search' => 'shirt']))->toBe(['Cotton Shirt'])
            ->and(listedProductNames(['search' => $sku]))->toBe(['Cotton Shirt']);
    });

    it('treats LIKE wildcards in the search term literally', function () {
        Product::factory()->simple()->create(['name' => '100% Cotton']);
        Product::factory()->simple()->create(['name' => 'Plain Tee']);

        expect(listedProductNames(['search' => '%']))->toBe(['100% Cotton']);
    });

    it('filters by a category including its sub-categories', function () {
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create();
        Product::factory()->simple()->create(['name' => 'In child', 'category_id' => $child->id]);
        Product::factory()->simple()->create(['name' => 'Elsewhere']);

        expect(listedProductNames(['category_id' => $parent->id]))->toBe(['In child'])
            ->and(listedProductNames(['category_id' => $child->id]))->toBe(['In child']);
    });

    it('filters by status and featured', function () {
        Product::factory()->simple()->featured()->create(['name' => 'Featured']);
        Product::factory()->simple()->inactive()->create(['name' => 'Hidden']);

        expect(listedProductNames(['status' => 'inactive']))->toBe(['Hidden'])
            ->and(listedProductNames(['featured' => 1]))->toBe(['Featured']);
    });

    it('filters by stock state using the low stock threshold', function () {
        Product::factory()->simple(stock: 50)->create(['name' => 'Plenty']);
        Product::factory()->simple(stock: 5)->create(['name' => 'Few']);
        Product::factory()->simple(stock: 0)->create(['name' => 'None']);

        expect(listedProductNames(['stock' => 'in']))->toBe(['Plenty'])
            ->and(listedProductNames(['stock' => 'low']))->toBe(['Few'])
            ->and(listedProductNames(['stock' => 'out']))->toBe(['None']);
    });

    it('rejects an unknown stock state', function () {
        $this->get(route('admin.products.index', ['stock' => 'plenty']))->assertSessionHasErrors('stock');
    });
});

describe('page', function () {
    it('returns list rows, the active filters and pagination meta', function () {
        $category = Category::factory()->create(['name' => 'Fashion']);
        Product::factory()->withVariants(3)->create(['name' => 'Polo', 'category_id' => $category->id]);

        $this->get(route('admin.products.index', ['search' => 'Polo', 'per_page' => 20]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Products/Index', false)
                ->where('products.data.0.name', 'Polo')
                ->where('products.data.0.category', 'Fashion')
                ->where('products.data.0.total_stock', 30)
                ->where('products.data.0.stock_state', 'in')
                ->where('products.meta.per_page', 20)
                ->where('filters.search', 'Polo')
                ->where('filters.per_page', 20)
                ->has('categoryTree')
                ->has('statuses', 2));
    });

    it('shows the category path of a sub-category and a price range', function () {
        $parent = Category::factory()->create(['name' => 'Fashion']);
        $child = Category::factory()->childOf($parent)->create(['name' => 'Women']);
        $product = Product::factory()->withVariants(2)->create(['category_id' => $child->id]);
        $product->variants()->orderBy('sort_order')->first()->update(['price' => 200000]);

        $this->get(route('admin.products.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('products.data.0.category', 'Fashion › Women')
                ->where('products.data.0.price', '৳1,500 – ৳2,000'));
    });

    it('falls back to the first rows-per-page option for an unsupported value', function () {
        Product::factory()->simple()->count(12)->create();

        expect(listedProductNames(['per_page' => 7]))->toHaveCount(10);
    });

    it('does not run a query per product', function () {
        Product::factory()->withVariants(2)->count(10)->create();

        DB::enableQueryLog();
        $this->get(route('admin.products.index', ['per_page' => 10]))->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($queries)->toBeLessThan(20);
    });
});
