<?php

use App\Http\Resources\Admin\Product\ProductFormResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\VariantValue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
    actingAsAdmin();
});

/**
 * Creates a matrix product through the endpoint (Size M/L x Color Pink/Black, variants M-Pink and L-Black).
 */
function createdMatrixProduct(): Product
{
    test()->post(route('admin.products.store'), matrixProductInput())->assertSessionHasNoErrors();

    return Product::firstOrFail();
}

/**
 * What the edit form would submit for the product as it is now; callers change it before submitting.
 *
 * @return array<string, mixed>
 */
function currentFormInput(Product $product): array
{
    $product->load(['images', 'variantHeads.values', 'variants.variantValues']);
    $data = json_decode(ProductFormResource::make($product)->toJson(), true);

    return [
        'category_id' => $product->category_id,
        'name' => $product->name,
        'status' => 'active',
        'has_variants' => $data['has_variants'] ? '1' : '0',
        'heads' => $data['heads'],
        'variants' => $data['variants'],
        'gallery_keep' => collect($data['images'])->where('is_main', false)->pluck('id')->all(),
    ];
}

/**
 * Value text a variant selected under the given head name.
 */
function variantValueText(ProductVariant $variant, string $head): ?string
{
    return $variant->variantValues()->whereHas('head', fn ($query) => $query->where('name', $head))->value('value');
}

describe('values', function () {
    it('keeps the variants and their prices when a value is renamed', function () {
        $product = createdMatrixProduct();
        $before = $product->variants()->pluck('price', 'id')->all();
        $input = currentFormInput($product);
        $input['heads'][0]['values'][0]['value'] = 'Medium';
        $input['variants'][0]['values'][0] = 'Medium';

        updateProduct($product, $input)->assertSessionHasNoErrors();

        $after = $product->variants()->pluck('price', 'id')->all();
        $renamed = $product->variants()->orderBy('sort_order')->first();

        expect($after)->toBe($before)
            ->and(variantValueText($renamed, 'Size'))->toBe('Medium');
    });

    it('deletes values that no variant uses any more', function () {
        $product = createdMatrixProduct();
        $input = currentFormInput($product);
        $input['variants'] = [$input['variants'][0]];

        updateProduct($product, $input)->assertSessionHasNoErrors();

        $values = VariantValue::whereIn('variant_head_id', $product->variantHeads()->pluck('id'))->pluck('value')->all();

        expect($values)->toEqualCanonicalizing(['M', 'Pink']);
    });
});

describe('variants', function () {
    it('adds a variant and gives it an initial stock movement', function () {
        $product = createdMatrixProduct();
        $input = currentFormInput($product);
        $input['variants'][] = ['values' => ['M', 'Black'], 'price' => '900', 'stock' => 3];

        updateProduct($product, $input)->assertSessionHasNoErrors();

        $added = $product->variants()->where('price', 90000)->sole();

        expect($product->variants()->count())->toBe(3)
            ->and($added->stock)->toBe(3)
            ->and(StockMovement::where('product_variant_id', $added->id)->sole()->quantity)->toBe(3);
    });

    it('soft deletes a removed variant, drops its selections and keeps its movements', function () {
        $product = createdMatrixProduct();
        $removed = $product->variants()->orderBy('sort_order')->get()->last();
        $input = currentFormInput($product);
        $input['variants'] = [$input['variants'][0]];

        updateProduct($product, $input)->assertSessionHasNoErrors();

        expect($product->variants()->count())->toBe(1)
            ->and(ProductVariant::withTrashed()->find($removed->id)->trashed())->toBeTrue()
            ->and($removed->variantValues()->count())->toBe(0)
            ->and(StockMovement::where('product_variant_id', $removed->id)->exists())->toBeTrue();
    });

    it('lets two variants swap their combinations in one save', function () {
        $product = createdMatrixProduct();
        $input = currentFormInput($product);
        [$first, $second] = $input['variants'];
        $input['variants'][0]['values'] = $second['values'];
        $input['variants'][1]['values'] = $first['values'];

        updateProduct($product, $input)->assertSessionHasNoErrors();

        $firstVariant = ProductVariant::find($first['id']);

        expect(variantValueText($firstVariant, 'Size'))->toBe('L')
            ->and(variantValueText($firstVariant, 'Color'))->toBe('Black')
            ->and($firstVariant->price)->toBe(100000);
    });

    it('changes the price of an existing variant without recording stock movements', function () {
        $product = createdMatrixProduct();
        $input = currentFormInput($product);
        $input['variants'][0]['price'] = '1999.99';

        updateProduct($product, $input)->assertSessionHasNoErrors();

        expect($product->variants()->orderBy('sort_order')->first()->price)->toBe(199999)
            ->and(StockMovement::count())->toBe(2);
    });
});

describe('switching', function () {
    it('turns a product with variants into a simple product', function () {
        $product = createdMatrixProduct();

        updateProduct($product, [
            ...currentFormInput($product),
            'has_variants' => '0',
            'heads' => null,
            'variants' => null,
            'price' => '500',
            'stock' => 4,
        ])->assertSessionHasNoErrors();

        $variant = $product->variants()->sole();

        expect($product->variantHeads()->count())->toBe(0)
            ->and($variant->combination_key)->toBe(ProductVariant::DEFAULT_COMBINATION)
            ->and($variant->price)->toBe(50000)
            ->and(ProductVariant::withTrashed()->where('product_id', $product->id)->count())->toBe(3);
    });

    it('turns a simple product into a product with variants', function () {
        test()->post(route('admin.products.store'), simpleProductInput())->assertSessionHasNoErrors();
        $product = Product::firstOrFail();
        $default = $product->variants()->sole();

        updateProduct($product, [
            ...matrixProductInput(['category_id' => $product->category_id, 'main_image' => null, 'name' => $product->name]),
        ])->assertSessionHasNoErrors();

        expect(ProductVariant::withTrashed()->find($default->id)->trashed())->toBeTrue()
            ->and($product->variants()->count())->toBe(2)
            ->and($product->variantHeads()->count())->toBe(2);
    });
});
