<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('uploads');
});

function simpleProductInput(array $overrides = []): array
{
    return [
        'category_id' => Category::factory()->create()->id,
        'name' => 'Cotton Shirt',
        'status' => 'active',
        'main_image' => fakeImage(800, 800),
        'has_variants' => '0',
        'price' => '1650.50',
        'sale_price' => '1500',
        'stock' => 20,
        ...$overrides,
    ];
}

function matrixProductInput(array $overrides = []): array
{
    return [
        'category_id' => Category::factory()->create()->id,
        'name' => 'Polo Shirt',
        'status' => 'active',
        'main_image' => fakeImage(800, 800),
        'has_variants' => '1',
        'heads' => [
            ['name' => 'Size', 'values' => [['value' => 'M'], ['value' => 'L']]],
            ['name' => 'Color', 'values' => [['value' => 'Pink'], ['value' => 'Black']]],
        ],
        'variants' => [
            ['values' => ['M', 'Pink'], 'price' => '1000', 'stock' => 5],
            ['values' => ['L', 'Black'], 'price' => '1200', 'sale_price' => '1100', 'stock' => 7],
        ],
        ...$overrides,
    ];
}

function updateProduct(Product $product, array $data): TestResponse
{
    return test()->post(route('admin.products.update', $product), ['_method' => 'put', ...$data]);
}

describe('access', function () {
    it('redirects guests to the login page', function () {
        $product = Product::factory()->simple()->create();

        $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.products.create'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.products.store'), simpleProductInput())->assertRedirect(route('admin.login'));
        $this->get(route('admin.products.edit', $product))->assertRedirect(route('admin.login'));
        $this->delete(route('admin.products.destroy', $product))->assertRedirect(route('admin.login'));
    });

    it('returns 404 for a missing or soft-deleted product', function () {
        actingAsAdmin();
        $product = Product::factory()->simple()->create();
        $product->delete();

        $this->get(route('admin.products.edit', $product->id))->assertNotFound();
        $this->delete(route('admin.products.destroy', 9999))->assertNotFound();
    });
});

describe('form pages', function () {
    it('passes the form props to create and the product to edit', function () {
        actingAsAdmin();
        $product = Product::factory()->withVariants(2)->create();

        $this->get(route('admin.products.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Products/Create', false)
                ->has('categoryTree')
                ->where('galleryMax', 3)
                ->where('maxVariantHeads', 2)
                ->where('maxVariants', 100)
                ->has('statuses', 2));

        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Products/Edit', false)
                ->where('product.id', $product->id)
                ->where('product.has_variants', true)
                ->has('product.heads', 1)
                ->has('product.variants', 2));
    });
});

describe('create', function () {
    beforeEach(fn () => actingAsAdmin());

    it('creates a simple product with one default variant, a main image and an initial stock movement', function () {
        $this->post(route('admin.products.store'), simpleProductInput())
            ->assertRedirect(route('admin.products.index'));

        $product = Product::firstOrFail();
        $variant = $product->variants()->sole();

        expect($product->slug)->toBe('cotton-shirt')
            ->and($product->has_variants)->toBeFalse()
            ->and($variant->combination_key)->toBe(ProductVariant::DEFAULT_COMBINATION)
            ->and($variant->price)->toBe(165050)
            ->and($variant->sale_price)->toBe(150000)
            ->and($variant->stock)->toBe(20)
            ->and($variant->sku)->not->toBeEmpty()
            ->and($product->images()->where('is_main', true)->count())->toBe(1);

        $movement = StockMovement::sole();
        expect($movement->quantity)->toBe(20)->and($movement->product_variant_id)->toBe($variant->id);
    });

    it('creates a product with variants for a subset of the combinations', function () {
        $this->post(route('admin.products.store'), matrixProductInput())
            ->assertRedirect(route('admin.products.index'));

        $product = Product::firstOrFail();

        expect($product->variantHeads()->count())->toBe(2)
            ->and($product->variants()->count())->toBe(2)
            ->and($product->variantHeads()->first()->values()->pluck('value')->all())->toBe(['M', 'L'])
            ->and($product->variants()->where('price', 120000)->first()->sale_price)->toBe(110000)
            ->and(StockMovement::count())->toBe(2);
    });

    it('stores the audit fields of the acting admin', function () {
        $admin = User::factory()->create();
        actingAsAdmin($admin);

        $this->post(route('admin.products.store'), simpleProductInput());
        $product = Product::firstOrFail();

        expect($product->created_by)->toBe($admin->id);

        $this->delete(route('admin.products.destroy', $product));

        expect(Product::withTrashed()->find($product->id)->deleted_by)->toBe($admin->id);
    });
});

describe('update', function () {
    beforeEach(fn () => actingAsAdmin());

    it('records a single movement of the difference when stock changes', function () {
        $this->post(route('admin.products.store'), simpleProductInput());
        $product = Product::firstOrFail();

        updateProduct($product, simpleProductInput(['main_image' => null, 'category_id' => $product->category_id, 'stock' => 25]))
            ->assertRedirect();

        $variant = $product->variants()->sole();
        $movements = StockMovement::orderBy('id')->pluck('quantity')->all();

        expect($variant->stock)->toBe(25)->and($movements)->toBe([20, 5]);
    });

    it('writes no movement when the stock did not change', function () {
        $this->post(route('admin.products.store'), simpleProductInput());
        $product = Product::firstOrFail();

        updateProduct($product, simpleProductInput(['main_image' => null, 'category_id' => $product->category_id, 'price' => '2000']));

        expect(StockMovement::count())->toBe(1)
            ->and($product->variants()->sole()->price)->toBe(200000);
    });

    it('applies a partial update (status and featured toggles) without touching variants', function () {
        $product = Product::factory()->withVariants(2)->create();
        $before = $product->variants()->pluck('id')->all();

        updateProduct($product, ['status' => 'inactive', 'is_featured' => '1'])->assertRedirect();

        $product->refresh();

        expect($product->status->value)->toBe('inactive')
            ->and($product->is_featured)->toBeTrue()
            ->and($product->variants()->pluck('id')->all())->toBe($before);
    });
});

describe('destroy', function () {
    it('soft deletes the product and its variants', function () {
        actingAsAdmin();
        $product = Product::factory()->withVariants(2)->create();

        $this->delete(route('admin.products.destroy', $product))->assertRedirect();

        expect(Product::find($product->id))->toBeNull()
            ->and(Product::withTrashed()->find($product->id))->not->toBeNull()
            ->and(ProductVariant::where('product_id', $product->id)->count())->toBe(0)
            ->and(ProductVariant::withTrashed()->where('product_id', $product->id)->count())->toBe(2);
    });
});
