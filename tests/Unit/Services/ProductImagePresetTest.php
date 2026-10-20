<?php

use App\Services\Media\FileUploadService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
    $this->uploads = app(FileUploadService::class);
});

function productImageSize(string $path): array
{
    return array_slice(getimagesizefromstring(Storage::disk('uploads')->get($path)), 0, 2);
}

describe('config', function () {
    it('allows three gallery images and a low stock threshold of five by default', function () {
        expect(config('media.product_gallery_max'))->toBe(3)
            ->and(config('shop.low_stock_threshold'))->toBe(5)
            ->and(config('shop.max_variant_heads'))->toBe(2);
    });
});

describe('product preset', function () {
    it('writes a 1200x1200 file and a 400x400 -thumb file beside it', function () {
        $path = $this->uploads->upload(fakeImage(800, 600), 'products', 'product');
        $thumb = str_replace('.webp', '-thumb.webp', $path);

        expect($path)->toMatch('#^products/[0-9a-f-]{36}\.webp$#')
            ->and(productImageSize($path))->toBe([1200, 1200])
            ->and(productImageSize($thumb))->toBe([400, 400])
            ->and(Storage::disk('uploads')->allFiles())->toHaveCount(2);
    });

    it('removes both files together', function () {
        $path = $this->uploads->upload(fakeImage(800, 600), 'products', 'product');

        $this->uploads->delete($path);

        expect(Storage::disk('uploads')->allFiles())->toBe([]);
    });
});
