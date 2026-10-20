<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
    actingAsAdmin();
});

function thumbPath(string $path): string
{
    return preg_replace('/\.webp$/', '-thumb.webp', $path);
}

function createdProductWithGallery(int $gallery = 2): Product
{
    test()->post(route('admin.products.store'), simpleProductInput([
        'gallery' => array_map(fn () => fakeImage(800, 800), range(1, $gallery)),
    ]))->assertSessionHasNoErrors();

    return Product::firstOrFail();
}

describe('upload', function () {
    it('stores the main image and the gallery with their thumbnails', function () {
        $product = createdProductWithGallery();

        expect($product->images()->count())->toBe(3)
            ->and($product->images()->where('is_main', true)->count())->toBe(1);

        foreach ($product->images as $image) {
            Storage::disk('uploads')->assertExists($image->path);
            Storage::disk('uploads')->assertExists(thumbPath($image->path));
        }
    });

    it('lets a variant use the main image or a new upload through image_ref', function () {
        $this->post(route('admin.products.store'), matrixProductInput([
            'gallery' => [fakeImage(800, 800)],
            'variants' => [
                ['values' => ['M', 'Pink'], 'price' => '10', 'stock' => 1, 'image_ref' => 'new:main'],
                ['values' => ['L', 'Black'], 'price' => '10', 'stock' => 1, 'image_ref' => 'new:0'],
            ],
        ]))->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        [$first, $second] = $product->variants()->orderBy('sort_order')->get()->all();

        expect($first->product_image_id)->toBe($product->mainImage->id)
            ->and($second->product_image_id)->toBe($product->galleryImages()->first()->id);
    });
});

describe('replace', function () {
    it('replaces the main image in place and removes the old files', function () {
        $product = createdProductWithGallery(0);
        $old = $product->mainImage;

        updateProduct($product, simpleProductInput(['category_id' => $product->category_id]))->assertSessionHasNoErrors();

        $new = $product->refresh()->mainImage;

        expect($new->id)->toBe($old->id)->and($new->path)->not->toBe($old->path);
        Storage::disk('uploads')->assertMissing($old->path);
        Storage::disk('uploads')->assertMissing(thumbPath($old->path));
        Storage::disk('uploads')->assertExists($new->path);
    });

    it('drops gallery images that are not kept and removes their files', function () {
        $product = createdProductWithGallery();
        [$keep, $drop] = $product->galleryImages->all();

        updateProduct($product, simpleProductInput([
            'category_id' => $product->category_id,
            'main_image' => null,
            'gallery_keep' => [$keep->id],
        ]))->assertSessionHasNoErrors();

        expect(ProductImage::find($drop->id))->toBeNull()
            ->and(ProductImage::find($keep->id))->not->toBeNull();
        Storage::disk('uploads')->assertMissing($drop->path);
        Storage::disk('uploads')->assertExists($keep->path);
    });

    it('keeps every file when the save fails', function () {
        $product = createdProductWithGallery(1);
        $paths = $product->images->pluck('path')->all();

        updateProduct($product, simpleProductInput([
            'category_id' => $product->category_id,
            'sale_price' => '99999',
        ]))->assertSessionHasErrors('sale_price');

        foreach ($paths as $path) {
            Storage::disk('uploads')->assertExists($path);
        }

        expect(count(Storage::disk('uploads')->allFiles()))->toBe(count($paths) * 2);
    });

    it('keeps the product images when the product is deleted', function () {
        $product = createdProductWithGallery(1);

        $this->delete(route('admin.products.destroy', $product))->assertRedirect();

        expect(ProductImage::where('product_id', $product->id)->count())->toBe(2);
    });
});
