<?php

use App\Services\Media\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
    $this->uploads = app(FileUploadService::class);
});

it('returns the full relative path, not just the filename', function () {
    $path = $this->uploads->upload(UploadedFile::fake()->image('a.jpg'), '/products/');

    expect($path)->toMatch('#^products/[0-9a-f-]{36}\.jpg$#')
        ->and(Storage::disk('uploads')->exists($path))->toBeTrue();
});

it('derives the extension from the file content, not the client filename', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'img');
    imagejpeg(imagecreatetruecolor(20, 20), $tmp);
    $file = new UploadedFile($tmp, 'picture.php', null, null, true);

    $path = $this->uploads->upload($file, 'products');

    expect($path)->toEndWith('.jpg')->not->toContain('php');
});

it('re-encodes to the preset format when resizing is enabled', function () {
    $path = $this->uploads->upload(UploadedFile::fake()->image('a.png', 900, 500), 'categories', 'category');

    expect($path)->toEndWith('.webp')
        ->and(array_slice(getimagesizefromstring(Storage::disk('uploads')->get($path)), 0, 2))->toBe([300, 300]);
});

it('only shrinks (never enlarges) with a scale_down preset', function () {
    $path = $this->uploads->upload(UploadedFile::fake()->image('small.png', 300, 200), 'misc', 'default');

    expect(array_slice(getimagesizefromstring(Storage::disk('uploads')->get($path)), 0, 2))->toBe([300, 200]);
});

it('stores the original file when resizing is disabled', function () {
    config(['media.resize_enabled' => false]);

    $path = $this->uploads->upload(UploadedFile::fake()->image('a.png', 2000, 1000), 'categories', 'category');

    expect($path)->toEndWith('.png')
        ->and(array_slice(getimagesizefromstring(Storage::disk('uploads')->get($path)), 0, 2))->toBe([2000, 1000]);
});

it('throws for an unknown preset', function () {
    $this->uploads->upload(UploadedFile::fake()->image('a.jpg'), 'categories', 'nope');
})->throws(InvalidArgumentException::class, 'Unknown image preset [nope]');

it('uploads many files and skips non-files', function () {
    $paths = $this->uploads->uploadMany([
        UploadedFile::fake()->image('a.jpg'),
        'not-a-file',
        UploadedFile::fake()->image('b.jpg'),
    ], 'gallery');

    expect($paths)->toHaveCount(2)
        ->and(Storage::disk('uploads')->allFiles())->toHaveCount(2);
});

it('deletes a stored file', function () {
    Storage::disk('uploads')->put('categories/a.webp', 'x');

    $this->uploads->delete('categories/a.webp');

    expect(Storage::disk('uploads')->exists('categories/a.webp'))->toBeFalse();
});

it('does nothing when deleting null, missing, static, external or traversal paths', function (?string $path) {
    Storage::disk('uploads')->put('keep.webp', 'x');

    $this->uploads->delete($path);

    expect(Storage::disk('uploads')->exists('keep.webp'))->toBeTrue();
})->with([
    'null' => [null],
    'empty' => [''],
    'missing file' => ['categories/missing.webp'],
    'static asset' => ['/images/categories/fashion.jpg'],
    'external url' => ['https://example.com/a.jpg'],
    'traversal' => ['../keep.webp'],
]);

it('builds public urls and leaves static and external paths untouched', function () {
    expect($this->uploads->url(null))->toBeNull()
        ->and($this->uploads->url('categories/a.webp'))->toBe(Storage::disk('uploads')->url('categories/a.webp'))
        ->and($this->uploads->url('/images/categories/fashion.jpg'))->toBe('/images/categories/fashion.jpg')
        ->and($this->uploads->url('https://cdn.example.com/a.jpg'))->toBe('https://cdn.example.com/a.jpg');
});

it('serves uploads straight from public/uploads', function () {
    expect(config('filesystems.disks.uploads.root'))->toBe(public_path('uploads'))
        ->and(config('filesystems.disks.uploads.url'))->toBe('/uploads')
        ->and(config('media.disk'))->toBe('uploads');
});

describe('variantUrl', function () {
    it('returns the url of a named size variant and leaves static and external paths alone', function () {
        $service = app(FileUploadService::class);

        expect($service->variantUrl('products/abc.webp', 'thumb'))->toEndWith('products/abc-thumb.webp')
            ->and($service->variantUrl('/images/a.jpg', 'thumb'))->toBe('/images/a.jpg')
            ->and($service->variantUrl('https://cdn.test/a.jpg', 'thumb'))->toBe('https://cdn.test/a.jpg')
            ->and($service->variantUrl(null, 'thumb'))->toBeNull();
    });
});
