<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
    actingAsAdmin();
});

describe('create', function () {
    it('rejects invalid input with an error on the offending field', function (Closure $input, string $field) {
        $this->post(route('admin.products.store'), $input())
            ->assertSessionHasErrors($field);

        expect(Product::whereIn('name', ['Cotton Shirt', 'Polo Shirt'])->count())->toBe(0);
    })->with([
        'category with sub-categories' => [
            fn () => simpleProductInput(['category_id' => Category::factory()->has(Category::factory()->count(1), 'children')->create()->id]),
            'category_id',
        ],
        'inactive category' => [fn () => simpleProductInput(['category_id' => Category::factory()->inactive()->create()->id]), 'category_id'],
        'sale price not lower than price' => [fn () => simpleProductInput(['sale_price' => '1650.50']), 'sale_price'],
        'price that is not an amount' => [fn () => simpleProductInput(['price' => 'abc']), 'price'],
        'three decimals in price' => [fn () => simpleProductInput(['price' => '10.999']), 'price'],
        'missing price' => [fn () => simpleProductInput(['price' => null]), 'price'],
        'negative stock' => [fn () => simpleProductInput(['stock' => -1]), 'stock'],
        'duplicate SKU' => [
            function () {
                $existing = ProductVariant::factory()->create(['sku' => 'TAKEN-1']);

                return simpleProductInput(['sku' => $existing->sku]);
            },
            'sku',
        ],
        'missing main image' => [fn () => simpleProductInput(['main_image' => null]), 'main_image'],
        'image too small' => [fn () => simpleProductInput(['main_image' => fakeImage(300, 300)]), 'main_image'],
        'image too large in pixels' => [fn () => simpleProductInput(['main_image' => fakeImage(5100, 700)]), 'main_image'],
        'image too heavy' => [fn () => simpleProductInput(['main_image' => fakeImage(800, 800, 3000)]), 'main_image'],
        'gallery over the limit' => [
            fn () => simpleProductInput(['gallery' => array_map(fn () => fakeImage(800, 800), range(1, 4))]),
            'gallery',
        ],
        'three variant heads' => [
            fn () => matrixProductInput(['heads' => [
                ['name' => 'Size', 'values' => [['value' => 'M']]],
                ['name' => 'Color', 'values' => [['value' => 'Pink']]],
                ['name' => 'Fit', 'values' => [['value' => 'Slim']]],
            ], 'variants' => [['values' => ['M', 'Pink', 'Slim'], 'price' => '10', 'stock' => 1]]]),
            'heads',
        ],
        'duplicate head name' => [
            fn () => matrixProductInput(['heads' => [
                ['name' => 'Size', 'values' => [['value' => 'M']]],
                ['name' => 'size', 'values' => [['value' => 'L']]],
            ], 'variants' => [['values' => ['M', 'L'], 'price' => '10', 'stock' => 1]]]),
            'heads.1.name',
        ],
        'duplicate value in a head' => [
            fn () => matrixProductInput(['heads' => [
                ['name' => 'Size', 'values' => [['value' => 'M'], ['value' => ' m ']]],
            ], 'variants' => [['values' => ['M'], 'price' => '10', 'stock' => 1]]]),
            'heads.0.values.1.value',
        ],
        'head without values' => [
            fn () => matrixProductInput(['heads' => [['name' => 'Size', 'values' => []]]]),
            'heads.0.values',
        ],
        'duplicate combination' => [
            fn () => matrixProductInput(['variants' => [
                ['values' => ['M', 'Pink'], 'price' => '10', 'stock' => 1],
                ['values' => ['m', 'pink'], 'price' => '10', 'stock' => 1],
            ]]),
            'variants.1.values',
        ],
        'value text that is not in the head' => [
            fn () => matrixProductInput(['variants' => [['values' => ['XXL', 'Pink'], 'price' => '10', 'stock' => 1]]]),
            'variants.0.values.0',
        ],
        'one value for two heads' => [
            fn () => matrixProductInput(['variants' => [['values' => ['M'], 'price' => '10', 'stock' => 1]]]),
            'variants.0.values',
        ],
        'variant sale price not lower than price' => [
            fn () => matrixProductInput(['variants' => [['values' => ['M', 'Pink'], 'price' => '10', 'sale_price' => '10', 'stock' => 1]]]),
            'variants.0.sale_price',
        ],
        'duplicate variant SKU' => [
            fn () => matrixProductInput(['variants' => [
                ['values' => ['M', 'Pink'], 'sku' => 'DUP-1', 'price' => '10', 'stock' => 1],
                ['values' => ['L', 'Black'], 'sku' => 'dup-1', 'price' => '10', 'stock' => 1],
            ]]),
            'variants.1.sku',
        ],
        'ids on create' => [
            fn () => matrixProductInput(['heads' => [
                ['id' => 1, 'name' => 'Size', 'values' => [['value' => 'M']]],
            ], 'variants' => [['values' => ['M'], 'price' => '10', 'stock' => 1]]]),
            'heads.0.id',
        ],
        'image reference that does not exist' => [
            fn () => matrixProductInput(['variants' => [['values' => ['M', 'Pink'], 'price' => '10', 'stock' => 1, 'image_ref' => '999']]]),
            'variants.0.image_ref',
        ],
    ]);

    it('rejects more variants than the configured maximum', function () {
        config(['shop.max_variants_per_product' => 1]);

        $this->post(route('admin.products.store'), matrixProductInput())->assertSessionHasErrors('variants');
    });
});

describe('update', function () {
    it('rejects ids that belong to another product', function () {
        $product = Product::factory()->withVariants(2)->create();
        $other = Product::factory()->withVariants(2)->create();
        $foreign = $other->variants()->first();

        updateProduct($product, [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'status' => 'active',
            'has_variants' => '1',
            'heads' => [['name' => 'Size', 'values' => [['value' => 'S']]]],
            'variants' => [['id' => $foreign->id, 'values' => ['S'], 'price' => '10', 'stock' => 1]],
        ])->assertSessionHasErrors('variants.0.id');
    });

    it('rejects the main image in gallery_keep', function () {
        test()->post(route('admin.products.store'), simpleProductInput())->assertSessionHasNoErrors();
        $product = Product::firstOrFail();

        updateProduct($product, [
            ...simpleProductInput(['category_id' => $product->category_id, 'main_image' => null]),
            'gallery_keep' => [$product->mainImage->id],
        ])->assertSessionHasErrors('gallery_keep');
    });

    it('keeps the current slug when none is sent and rejects a slug used by another product', function () {
        $product = Product::factory()->simple()->create(['slug' => 'first']);
        Product::factory()->simple()->create(['slug' => 'second']);

        updateProduct($product, ['name' => 'Renamed'])->assertSessionHasNoErrors();
        expect($product->refresh()->slug)->toBe('first');

        updateProduct($product, ['slug' => 'second'])->assertSessionHasErrors('slug');
    });
});
