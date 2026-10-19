<?php

use App\Services\Media\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
    $this->uploads = app(FileUploadService::class);
});

it('writes the main file and the -sm variant for a preset with variants', function () {
    $path = $this->uploads->upload(UploadedFile::fake()->image('a.jpg', 2400, 1000), 'sliders', 'slider');
    $small = str_replace('.webp', '-sm.webp', $path);
    $size = fn (string $file) => array_slice(getimagesizefromstring(Storage::disk('uploads')->get($file)), 0, 2);

    expect($path)->toMatch('#^sliders/[0-9a-f-]{36}\.webp$#')
        ->and($size($path))->toBe([1920, 800])
        ->and($size($small))->toBe([960, 400])
        ->and(Storage::disk('uploads')->allFiles())->toHaveCount(2);
});

it('removes every size when deleting a file with variants', function () {
    $path = $this->uploads->upload(UploadedFile::fake()->image('a.jpg', 2400, 1000), 'sliders', 'slider');

    $this->uploads->delete($path);

    expect(Storage::disk('uploads')->allFiles())->toBe([]);
});

it('cleans up already written files when a variant cannot be produced', function () {
    config(['media.presets.slider.variants' => ['sm' => 'does_not_exist']]);

    expect(fn () => $this->uploads->upload(UploadedFile::fake()->image('a.jpg', 2400, 1000), 'sliders', 'slider'))
        ->toThrow(InvalidArgumentException::class, 'Unknown image preset [does_not_exist]');

    expect(Storage::disk('uploads')->allFiles())->toBe([]);
});

it('builds a srcset from the sizes that exist, smallest first', function () {
    $path = $this->uploads->upload(UploadedFile::fake()->image('a.jpg', 2400, 1000), 'sliders', 'slider');
    $disk = Storage::disk('uploads');

    expect($this->uploads->srcset($path, 'slider'))
        ->toBe($disk->url(str_replace('.webp', '-sm.webp', $path)).' 960w, '.$disk->url($path).' 1920w');
});

it('returns no srcset for null, static paths or originals stored without resizing', function () {
    config(['media.resize_enabled' => false]);
    $original = $this->uploads->upload(UploadedFile::fake()->image('a.jpg', 2400, 1000), 'sliders', 'slider');

    expect($this->uploads->srcset(null, 'slider'))->toBeNull()
        ->and($this->uploads->srcset('/images/sliders/1.jpg', 'slider'))->toBeNull()
        ->and($this->uploads->srcset($original, 'slider'))->toBeNull()
        ->and(Storage::disk('uploads')->allFiles())->toHaveCount(1);
});

it('fits the whole image into the category box without cropping (contain, padded transparent)', function () {
    $source = tempnam(sys_get_temp_dir(), 'img');
    $canvas = imagecreatetruecolor(600, 300);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 0, 0));
    imagepng($canvas, $source);

    $path = $this->uploads->upload(new UploadedFile($source, 'wide.png', null, null, true), 'categories', 'category');
    $result = imagecreatefromstring(Storage::disk('uploads')->get($path));
    $alphaAt = fn (int $x, int $y) => (imagecolorat($result, $x, $y) >> 24) & 0x7F;

    expect([imagesx($result), imagesy($result)])->toBe([300, 300])
        ->and($alphaAt(150, 10))->toBe(127) // padding above the image
        ->and($alphaAt(150, 290))->toBe(127) // padding below the image
        ->and($alphaAt(150, 150))->toBe(0) // image content
        ->and($alphaAt(5, 150))->toBe(0); // reaches the left edge, so nothing was cropped away
});
