<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Product\SkuGenerator;

function skuProduct(int $id = 12, string $category = 'Fashion'): Product
{
    return Product::factory()->create([
        'id' => $id,
        'category_id' => Category::factory()->create(['name' => $category, 'slug' => str($category)->slug().'-'.$id])->id,
    ]);
}

beforeEach(function () {
    $this->skus = new SkuGenerator;
});

describe('format', function () {
    it('builds {CAT3}-{id} for the default variant', function () {
        $sku = $this->skus->generate(skuProduct());

        expect($sku)->toBe('FAS-0012');
    });

    it('appends a short code for each option value', function () {
        $sku = $this->skus->generate(skuProduct(), ['M', 'Pink']);

        expect($sku)->toBe('FAS-0012-M-PNK');
    });

    it('pads the product id to four digits and lets longer ids grow', function (int $id, string $expected) {
        $sku = $this->skus->generate(skuProduct($id));

        expect($sku)->toBe($expected);
    })->with([
        'one digit' => [7, 'FAS-0007'],
        'four digits' => [1234, 'FAS-1234'],
        'five digits' => [12345, 'FAS-12345'],
    ]);

    it('uses the top-level category name for a product in a sub-category', function () {
        $fashion = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);
        $leaf = Category::factory()->childOf($fashion)->create(['name' => "Women's Clothing", 'slug' => 'womens-clothing']);
        $product = Product::factory()->create(['id' => 3, 'category_id' => $leaf->id]);

        expect($this->skus->generate($product))->toBe('FAS-0003');
    });

    it('falls back to GEN when the category name has no letters or digits', function () {
        $sku = $this->skus->generate(skuProduct(5, '!!!'));

        expect($sku)->toBe('GEN-0005');
    });
});

describe('option codes', function () {
    it('keeps the first character, drops later vowels and takes three characters', function (string $value, string $code) {
        $sku = $this->skus->generate(skuProduct(), [$value]);

        expect($sku)->toBe("FAS-0012-{$code}");
    })->with([
        'single letter' => ['M', 'M'],
        'pink' => ['Pink', 'PNK'],
        'two letters' => ['XL', 'XL'],
        'short word' => ['Red', 'RD'],
        'long word' => ['Small', 'SML'],
        'lower case' => ['pink', 'PNK'],
        'with spaces and symbols' => ['Navy Blue!', 'NVY'],
        'accents' => ['Café', 'CF'],
    ]);

    it('transliterates non-Latin values', function () {
        $sku = $this->skus->generate(skuProduct(), ['লাল']);

        expect($sku)->toMatch('/^FAS-0012-[A-Z0-9]{1,3}$/');
    });

    it('gives a stable short hash to values with no letters or digits at all', function () {
        $product = skuProduct();
        $first = $this->skus->generate($product, ['###']);
        $again = $this->skus->generate($product, ['###']);

        expect($first)->toMatch('/^FAS-0012-[0-9A-F]{3}$/')
            ->and($again)->toBe($first);
    });
});

describe('collisions', function () {
    it('appends -2 and then -3 when the SKU is taken', function () {
        $product = skuProduct();
        ProductVariant::factory()->for($product)->combination('a')->create(['sku' => 'FAS-0012']);

        $second = $this->skus->generate($product);
        ProductVariant::factory()->for($product)->combination('b')->create(['sku' => $second]);
        $third = $this->skus->generate($product);

        expect($second)->toBe('FAS-0012-2')
            ->and($third)->toBe('FAS-0012-3');
    });

    it('treats the SKU of a soft-deleted variant as taken', function () {
        $product = skuProduct();
        ProductVariant::factory()->for($product)->create(['sku' => 'FAS-0012-M-PNK'])->delete();

        $sku = $this->skus->generate($product, ['M', 'Pink']);

        expect($sku)->toBe('FAS-0012-M-PNK-2');
    });

    it('avoids SKUs that were chosen in the same batch but not saved yet', function () {
        $product = skuProduct();

        $sku = $this->skus->generate($product, ['M'], reserved: ['FAS-0012-M', 'FAS-0012-M-2']);

        expect($sku)->toBe('FAS-0012-M-3');
    });
});
