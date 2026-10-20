<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\VariantHead;
use App\Models\VariantValue;
use Illuminate\Database\QueryException;

function variantWithValues(Product $product, array $values): ProductVariant
{
    $variant = ProductVariant::factory()->for($product)->create();
    $variant->syncVariantValues(array_map(fn (VariantValue $value) => $value->id, $values));

    return $variant;
}

function productWithSizeAndColor(): array
{
    $product = Product::factory()->create();
    $size = VariantHead::factory()->for($product)->create(['name' => 'Size', 'position' => 0]);
    $color = VariantHead::factory()->for($product)->create(['name' => 'Color', 'position' => 1]);

    return [
        $product,
        VariantValue::factory()->for($size, 'head')->create(['value' => 'M']),
        VariantValue::factory()->for($color, 'head')->create(['value' => 'Pink']),
    ];
}

describe('prices', function () {
    it('uses the sale price as the effective price', function () {
        $variant = ProductVariant::factory()->onSale(165000)->make(['price' => 200000]);

        expect($variant->effective_price)->toBe(165000);
    });

    it('uses the regular price when there is no sale', function () {
        $variant = ProductVariant::factory()->make(['price' => 200000, 'sale_price' => null]);

        expect($variant->effective_price)->toBe(200000);
    });

    it('rounds the discount to a whole percent (2000 to 1650 is 18%)', function (int $price, ?int $sale, ?int $expected) {
        $variant = ProductVariant::factory()->make(['price' => $price, 'sale_price' => $sale]);

        expect($variant->discount_percent)->toBe($expected);
    })->with([
        'eighteen percent' => [2000, 1650, 18],
        'exactly half' => [2000, 1000, 50],
        'one third' => [3000, 2000, 33],
        'no sale' => [2000, null, null],
        'free sale price' => [2000, 0, 100],
    ]);
});

describe('stock', function () {
    it('is out of stock at zero', function (int $stock, bool $expected) {
        $variant = ProductVariant::factory()->make(['stock' => $stock]);

        expect($variant->is_out_of_stock)->toBe($expected);
    })->with([
        'zero' => [0, true],
        'one left' => [1, false],
    ]);
});

describe('label', function () {
    it('is "Default" without selected values', function () {
        $variant = ProductVariant::factory()->for(Product::factory()->create())->create();

        expect($variant->label)->toBe('Default');
    });

    it('shows one selected value', function () {
        [$product, $size] = productWithSizeAndColor();

        $variant = variantWithValues($product, [$size]);

        expect($variant->label)->toBe('M');
    });

    it('joins the values in head order, whatever order they were attached in', function () {
        [$product, $size, $color] = productWithSizeAndColor();

        $variant = variantWithValues($product, [$color, $size]);

        expect($variant->label)->toBe('M / Pink');
    });
});

describe('combination key', function () {
    it('is the sorted value ids, or "default" without values', function (array $ids, string $expected) {
        expect(ProductVariant::combinationKeyFor($ids))->toBe($expected);
    })->with([
        'no values' => [[], 'default'],
        'one value' => [[12], '12'],
        'two values in any order' => [[47, 12], '12-47'],
        'string ids' => [['9', '10'], '9-10'],
    ]);

    it('is stored together with the selected values', function () {
        [$product, $size, $color] = productWithSizeAndColor();

        $variant = variantWithValues($product, [$color, $size])->fresh();

        expect($variant->combination_key)->toBe($size->id.'-'.$color->id)
            ->and($variant->variantValues->pluck('id')->sort()->values()->all())->toBe([$size->id, $color->id]);
    });

    it('replaces the previous selection', function () {
        [$product, $size, $color] = productWithSizeAndColor();
        $variant = variantWithValues($product, [$size, $color]);

        $variant->syncVariantValues([$size->id]);

        expect($variant->fresh()->combination_key)->toBe((string) $size->id)
            ->and($variant->fresh()->variantValues)->toHaveCount(1);
    });
});

describe('database rules', function () {
    it('rejects a second live variant with the same combination', function () {
        [$product, $size, $color] = productWithSizeAndColor();
        variantWithValues($product, [$size, $color]);
        $duplicate = ProductVariant::factory()->for($product)->create();

        expect(fn () => $duplicate->syncVariantValues([$color->id, $size->id]))->toThrow(QueryException::class);
        expect($duplicate->fresh()->combination_key)->toBe('default')
            ->and($duplicate->fresh()->variantValues)->toHaveCount(0);
    });

    it('rejects a second live default variant of the same product', function () {
        $product = Product::factory()->simple()->create();

        expect(fn () => ProductVariant::factory()->for($product)->create())->toThrow(QueryException::class);
    });

    it('rejects a second variant with the same single value', function () {
        $product = Product::factory()->withVariants(2)->create();
        $medium = $product->variantHeads->sole()->values->firstWhere('value', 'M');
        $other = ProductVariant::factory()->for($product)->create();

        expect(fn () => $other->syncVariantValues([$medium->id]))->toThrow(QueryException::class);
    });

    it('allows the same combination on another product', function () {
        $first = Product::factory()->simple()->create();
        $second = Product::factory()->simple()->create();

        expect($first->variants->sole()->combination_key)->toBe($second->variants->sole()->combination_key);
    });

    it('allows a combination again after its variant was soft-deleted', function () {
        $product = Product::factory()->simple()->create();
        $product->variants->sole()->delete();

        ProductVariant::factory()->for($product)->create();

        expect($product->variants()->count())->toBe(1)
            ->and($product->variants()->withTrashed()->count())->toBe(2);
    });

    it('keeps a SKU unique, even against soft-deleted variants', function () {
        $first = ProductVariant::factory()->create(['sku' => 'FAS-0001']);
        $first->delete();

        expect(fn () => ProductVariant::factory()->create(['sku' => 'FAS-0001']))->toThrow(QueryException::class);
    });

    it('refuses to delete a value that a variant uses', function () {
        [$product, $size] = productWithSizeAndColor();
        variantWithValues($product, [$size]);

        expect(fn () => $size->delete())->toThrow(QueryException::class);
    });

    it('selects a value only once per variant', function () {
        [$product, $size] = productWithSizeAndColor();
        $variant = variantWithValues($product, [$size]);

        expect(fn () => $variant->variantValues()->attach($size->id))->toThrow(QueryException::class);
    });

    it('detaches the selections when a variant without stock history is force deleted', function () {
        [$product, $size] = productWithSizeAndColor();
        $variant = ProductVariant::factory()->for($product)->outOfStock()->create();
        $variant->syncVariantValues([$size->id]);

        $variant->forceDelete();

        expect($size->variants()->count())->toBe(0);
    });

    it('refuses to force delete a variant that has stock history', function () {
        $variant = ProductVariant::factory()->create(['stock' => 5]);

        expect(fn () => $variant->forceDelete())->toThrow(QueryException::class);
    });
});

describe('product images', function () {
    it('allows only one main image per product', function () {
        $product = Product::factory()->create();
        ProductImage::factory()->for($product)->main()->create();
        ProductImage::factory()->for($product)->create();

        expect(fn () => ProductImage::factory()->for($product)->main()->create())->toThrow(QueryException::class);
    });

    it('allows a main image on every product', function () {
        ProductImage::factory()->main()->create();
        ProductImage::factory()->main()->create();

        expect(ProductImage::where('is_main', true)->count())->toBe(2);
    });

    it('links a variant to one of the product images and clears the link when the image is removed', function () {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->for($product)->main()->create();
        $variant = ProductVariant::factory()->for($product)->create(['product_image_id' => $image->id]);

        expect($variant->image->is($image))->toBeTrue()
            ->and($image->variants->pluck('id')->all())->toBe([$variant->id]);

        $image->delete();

        expect($variant->fresh()->product_image_id)->toBeNull();
    });
});
