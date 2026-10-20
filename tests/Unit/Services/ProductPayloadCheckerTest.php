<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Product\ProductPayloadChecker;

beforeEach(function () {
    $this->checker = new ProductPayloadChecker;
});

function checkerMatrix(array $overrides = []): array
{
    return [
        'has_variants' => true,
        'heads' => [
            ['name' => 'Size', 'values' => [['value' => 'M'], ['value' => 'L']]],
        ],
        'variants' => [
            ['values' => ['M'], 'price' => '10', 'stock' => 1],
            ['values' => ['L'], 'price' => '10', 'stock' => 1],
        ],
        ...$overrides,
    ];
}

describe('partial update', function () {
    it('only checks images when has_variants is not sent', function () {
        expect($this->checker->check(['status' => 'inactive'], Product::factory()->simple()->create(), false, 0))->toBe([]);
    });
});

describe('matrix', function () {
    it('accepts a valid subset of combinations', function () {
        expect($this->checker->check(checkerMatrix(), null, true, 0))->toBe([]);
    });

    it('reports a variant that selects a value its head does not have', function () {
        $errors = $this->checker->check(checkerMatrix(['variants' => [['values' => ['XL'], 'price' => '10', 'stock' => 1]]]), null, true, 0);

        expect($errors)->toHaveKey('variants.0.values.0');
    });

    it('reports a duplicate combination on the second variant', function () {
        $errors = $this->checker->check(checkerMatrix(['variants' => [
            ['values' => ['M'], 'price' => '10', 'stock' => 1],
            ['values' => ['M'], 'price' => '10', 'stock' => 1],
        ]]), null, true, 0);

        expect($errors)->toHaveKey('variants.1.values')->not->toHaveKey('variants.0.values');
    });

    it('reports a SKU that another variant owns, including a soft-deleted one', function () {
        $deleted = ProductVariant::factory()->create(['sku' => 'OLD-1']);
        $deleted->delete();

        $errors = $this->checker->check(checkerMatrix(['variants' => [
            ['values' => ['M'], 'sku' => 'old-1', 'price' => '10', 'stock' => 1],
        ]]), null, true, 0);

        expect($errors)->toHaveKey('variants.0.sku');
    });

    it('lets a variant keep its own SKU', function () {
        $product = Product::factory()->withVariants(2)->create();
        $variant = $product->variants()->first();

        $errors = $this->checker->check([
            'has_variants' => true,
            'heads' => [['id' => $product->variantHeads()->first()->id, 'name' => 'Size', 'values' => [['value' => 'S']]]],
            'variants' => [['id' => $variant->id, 'values' => ['S'], 'sku' => $variant->sku, 'price' => '10', 'stock' => 1]],
            'gallery_keep' => [],
        ], $product, false, 0);

        expect($errors)->not->toHaveKey('variants.0.sku');
    });

    it('queries the SKUs once however many variants are checked', function () {
        $variants = array_map(fn (int $i) => ['values' => ["V{$i}"], 'sku' => "SKU-{$i}", 'price' => '10', 'stock' => 1], range(1, 30));
        $heads = [['name' => 'Size', 'values' => array_map(fn (array $v) => ['value' => $v['values'][0]], $variants)]];

        DB::enableQueryLog();
        $this->checker->check(['has_variants' => true, 'heads' => $heads, 'variants' => $variants], null, true, 0);
        $queries = collect(DB::getQueryLog())->filter(fn (array $q) => str_contains($q['query'], 'product_variants'))->count();
        DB::disableQueryLog();

        expect($queries)->toBe(1);
    });
});

describe('images', function () {
    it('requires a main image on a full save when the product has none', function () {
        expect($this->checker->check(['has_variants' => false, 'price' => '10'], null, false, 0))->toHaveKey('main_image');
    });

    it('accepts the existing main image and rejects a foreign gallery id', function () {
        $product = Product::factory()->simple()->create();
        ProductImage::factory()->for($product)->create(['is_main' => true]);
        $foreign = ProductImage::factory()->create(['is_main' => false]);

        $errors = $this->checker->check(['has_variants' => false, 'price' => '10', 'gallery_keep' => [$foreign->id]], $product, false, 0);

        expect($errors)->toHaveKey('gallery_keep')->not->toHaveKey('main_image');
    });

    it('limits kept plus new gallery images', function () {
        $errors = $this->checker->check(['has_variants' => false, 'price' => '10'], null, true, 4);

        expect($errors)->toHaveKey('gallery');
    });
});
