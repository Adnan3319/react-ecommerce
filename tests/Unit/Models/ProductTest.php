<?php

use App\Enums\ActiveStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

describe('relations', function () {
    it('belongs to a category', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        expect($product->category->is($category))->toBeTrue();
    });

    it('lists the main image first, then the gallery in order', function () {
        $product = Product::factory()->create();
        $second = ProductImage::factory()->for($product)->create(['sort_order' => 2]);
        $first = ProductImage::factory()->for($product)->create(['sort_order' => 1]);
        $main = ProductImage::factory()->for($product)->main()->create(['sort_order' => 9]);

        expect($product->images->pluck('id')->all())->toBe([$main->id, $first->id, $second->id])
            ->and($product->mainImage->is($main))->toBeTrue()
            ->and($product->galleryImages->pluck('id')->all())->toBe([$first->id, $second->id]);
    });

    it('lists variants by sort order and leaves out inactive and deleted ones from the active list', function () {
        $product = Product::factory()->create();
        $second = ProductVariant::factory()->for($product)->combination('a')->create(['sort_order' => 2]);
        $first = ProductVariant::factory()->for($product)->combination('b')->create(['sort_order' => 1]);
        ProductVariant::factory()->for($product)->combination('c')->inactive()->create(['sort_order' => 3]);
        ProductVariant::factory()->for($product)->combination('d')->create(['sort_order' => 4])->delete();

        expect($product->variants->pluck('id')->all())->toContain($first->id, $second->id)
            ->and($product->variants)->toHaveCount(3)
            ->and($product->activeVariants->pluck('id')->all())->toBe([$first->id, $second->id]);
    });

    it('lists the variant heads by position', function () {
        $product = Product::factory()->withOptions(['Size' => ['M'], 'Color' => ['Pink']])->create();

        expect($product->variantHeads->pluck('name')->all())->toBe(['Size', 'Color']);
    });
});

describe('scopes', function () {
    it('active() keeps only active products', function () {
        $active = Product::factory()->create();
        Product::factory()->inactive()->create();

        expect(Product::active()->pluck('id')->all())->toBe([$active->id]);
    });

    it('featured() keeps only featured products', function () {
        $featured = Product::factory()->featured()->create();
        Product::factory()->create();

        expect(Product::featured()->pluck('id')->all())->toBe([$featured->id]);
    });

    it('inStock() needs an active, live variant with stock', function () {
        $inStock = Product::factory()->simple(stock: 3)->create();
        Product::factory()->outOfStock()->create();
        Product::factory()->create();
        $onlyInactiveStock = Product::factory()->create();
        ProductVariant::factory()->for($onlyInactiveStock)->inactive()->create(['stock' => 5]);
        $onlyDeletedStock = Product::factory()->create();
        ProductVariant::factory()->for($onlyDeletedStock)->create(['stock' => 5])->delete();

        expect(Product::inStock()->pluck('id')->all())->toBe([$inStock->id]);
    });

    it('inStock() counts a product when only one of its variants has stock', function () {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->combination('a')->outOfStock()->create(['sort_order' => 1]);
        ProductVariant::factory()->for($product)->combination('b')->create(['stock' => 2, 'sort_order' => 2]);

        expect(Product::inStock()->pluck('id')->all())->toBe([$product->id]);
    });
});

describe('accessors', function () {
    it('price_range is the cheapest and dearest effective price of the active variants', function () {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->combination('a')->onSale(165000)->create(['price' => 200000]);
        ProductVariant::factory()->for($product)->combination('b')->create(['price' => 180000]);
        ProductVariant::factory()->for($product)->combination('c')->inactive()->create(['price' => 100]);

        expect($product->price_range)->toBe(['min' => 165000, 'max' => 180000]);
    });

    it('price_range is empty without variants', function () {
        $product = Product::factory()->create();

        expect($product->price_range)->toBe(['min' => null, 'max' => null]);
    });

    it('total_stock adds up the stock of the active variants only', function () {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->combination('a')->create(['stock' => 4]);
        ProductVariant::factory()->for($product)->combination('b')->create(['stock' => 6]);
        ProductVariant::factory()->for($product)->combination('c')->inactive()->create(['stock' => 50]);

        expect($product->total_stock)->toBe(10);
    });

    it('has_variants is false for a simple product and true when it has variant heads', function () {
        $simple = Product::factory()->simple()->create();
        $withSizes = Product::factory()->withVariants(3)->create();

        expect($simple->has_variants)->toBeFalse()
            ->and($withSizes->has_variants)->toBeTrue();
    });

    it('computes price range and stock from loaded variants without more queries', function () {
        $product = Product::factory()->withVariants(3, stock: 2)->create()->load('variants');
        DB::enableQueryLog();

        $range = $product->price_range;
        $stock = $product->total_stock;

        expect(DB::getQueryLog())->toBe([])
            ->and($range)->toBe(['min' => 150000, 'max' => 150000])
            ->and($stock)->toBe(6);
    });
});

describe('factory states', function () {
    it('simple() creates one default variant with a generated SKU and its initial stock movement', function () {
        $product = Product::factory()->simple(price: 200000, stock: 7)->create();

        $variant = $product->variants->sole();

        expect($variant->combination_key)->toBe('default')
            ->and($variant->label)->toBe('Default')
            ->and($variant->price)->toBe(200000)
            ->and($variant->sku)->toMatch('/^[A-Z0-9]{3}-\d{4}$/')
            ->and($variant->stockMovements()->sum('quantity'))->toBe(7);
    });

    it('withVariants() creates a Size head with one value and one variant per size', function () {
        $product = Product::factory()->withVariants(3)->create();

        expect($product->variantHeads->sole()->values->pluck('value')->all())->toBe(['S', 'M', 'L'])
            ->and($product->variants->map->label->all())->toBe(['S', 'M', 'L']);
    });

    it('withOptions() creates one variant per combination', function () {
        $product = Product::factory()->withOptions(['Size' => ['M', 'L'], 'Color' => ['Pink', 'Black']])->create();

        expect($product->variants->map->label->sort()->values()->all())
            ->toBe(['L / Black', 'L / Pink', 'M / Black', 'M / Pink'])
            ->and($product->variants->pluck('sku')->unique())->toHaveCount(4);
    });

    it('outOfStock() leaves the default variant without stock or movements', function () {
        $product = Product::factory()->outOfStock()->create();

        expect($product->variants->sole()->is_out_of_stock)->toBeTrue()
            ->and(StockMovement::count())->toBe(0);
    });

    it('inactive() and featured() set the flags', function () {
        expect(Product::factory()->inactive()->create()->status)->toBe(ActiveStatus::Inactive)
            ->and(Product::factory()->featured()->create()->is_featured)->toBeTrue();
    });
});

describe('soft delete', function () {
    it('keeps the row and its slug reserved', function () {
        $product = Product::factory()->create();

        $product->delete();

        expect(Product::count())->toBe(0)
            ->and(Product::withTrashed()->count())->toBe(1);
    });
});
