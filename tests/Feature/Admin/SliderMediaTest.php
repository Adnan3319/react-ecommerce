<?php

use App\Enums\ActiveStatus;
use App\Models\Slider;
use App\Models\User;
use App\Services\Slider\SliderService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('uploads');
    $this->actingAs(User::factory()->create());
});

function mediaSliderData(array $overrides = []): array
{
    return [
        'title' => 'Smart Kitchen',
        'button_link' => '/search',
        'sort_order' => 1,
        'status' => 'active',
        'position' => 'hero',
        'image' => UploadedFile::fake()->image('banner.jpg', 2400, 1000),
        ...$overrides,
    ];
}

function mediaUpdate(Slider $slider, array $data): TestResponse
{
    return test()->post(route('admin.sliders.update', $slider), ['_method' => 'put', ...$data]);
}

function mediaSize(string $path): array
{
    return array_slice(getimagesizefromstring(Storage::disk('uploads')->get($path)), 0, 2);
}

function mediaSmall(string $path): string
{
    return str_replace('.webp', '-sm.webp', $path);
}

describe('responsive images', function () {
    it('creates the main (1920x800) and the small (960x400) file from one upload', function () {
        $this->post(route('admin.sliders.store'), mediaSliderData())->assertSessionHasNoErrors();

        $slider = Slider::sole();

        expect(Storage::disk('uploads')->allFiles())->toHaveCount(2)
            ->and($slider->image)->toMatch('#^sliders/[0-9a-f-]{36}\.webp$#')
            ->and(mediaSize($slider->image))->toBe([1920, 800])
            ->and(mediaSize(mediaSmall($slider->image)))->toBe([960, 400]);
    });

    it('replaces both files when the image is replaced', function () {
        $slider = Slider::factory()->create(['image' => 'sliders/old.webp']);
        Storage::disk('uploads')->put('sliders/old.webp', 'big');
        Storage::disk('uploads')->put('sliders/old-sm.webp', 'small');

        mediaUpdate($slider, ['image' => UploadedFile::fake()->image('new.jpg', 2000, 900)])
            ->assertSessionHasNoErrors();

        $newPath = $slider->fresh()->image;

        expect(Storage::disk('uploads')->exists('sliders/old.webp'))->toBeFalse()
            ->and(Storage::disk('uploads')->exists('sliders/old-sm.webp'))->toBeFalse()
            ->and(Storage::disk('uploads')->exists($newPath))->toBeTrue()
            ->and(Storage::disk('uploads')->exists(mediaSmall($newPath)))->toBeTrue()
            ->and(Storage::disk('uploads')->allFiles())->toHaveCount(2);
    });

    it('deletes both files when the slider is deleted', function () {
        $this->post(route('admin.sliders.store'), mediaSliderData())->assertSessionHasNoErrors();

        $this->delete(route('admin.sliders.destroy', Slider::sole()))->assertRedirect();

        expect(Storage::disk('uploads')->allFiles())->toBe([]);
    });

    it('returns a 960w/1920w srcset in the admin resource and in the storefront cache', function () {
        $this->post(route('admin.sliders.store'), mediaSliderData())->assertSessionHasNoErrors();
        $path = Slider::sole()->image;
        $disk = Storage::disk('uploads');
        $expected = $disk->url(mediaSmall($path)).' 960w, '.$disk->url($path).' 1920w';

        $this->get(route('admin.sliders.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sliders.data.0.image_srcset', $expected)
                ->where('sliders.data.0.image_url', $disk->url($path)));

        expect(app(SliderService::class)->activeForStorefront()['hero'][0]['image_srcset'])->toBe($expected);
    });

    it('has no srcset for static seed images or when only one size exists', function () {
        Slider::factory()->create(['sort_order' => 1, 'image' => '/images/sliders/1.jpg']);
        Slider::factory()->create(['sort_order' => 2, 'image' => 'sliders/single.webp']);
        Storage::disk('uploads')->put('sliders/single.webp', 'only-one-size');

        $this->get(route('admin.sliders.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sliders.data.0.image_srcset', null)
                ->where('sliders.data.1.image_srcset', null));
    });

    it('rejects images outside the slider limits', function (mixed $image) {
        $this->post(route('admin.sliders.store'), mediaSliderData(['image' => $image]))
            ->assertSessionHasErrors('image');

        expect(Slider::count())->toBe(0)->and(Storage::disk('uploads')->allFiles())->toBe([]);
    })->with([
        'over 2MB' => fn () => UploadedFile::fake()->image('big.jpg', 2000, 900)->size(2049),
        'too narrow (1919px)' => fn () => UploadedFile::fake()->image('narrow.png', 1919, 800),
        'too short (799px)' => fn () => UploadedFile::fake()->image('short.png', 1920, 799),
        'too wide (5001px)' => fn () => UploadedFile::fake()->image('wide.png', 5001, 900),
        'too tall (5001px)' => fn () => UploadedFile::fake()->image('tall.png', 2000, 5001),
    ]);

    it('accepts images exactly on the limits', function (int $width, int $height) {
        $this->post(route('admin.sliders.store'), mediaSliderData([
            'image' => UploadedFile::fake()->image('edge.png', $width, $height),
        ]))->assertSessionHasNoErrors();
    })->with([
        'minimum' => [1920, 800],
        'maximum' => [5000, 5000],
    ]);
});

describe('active slider limit', function () {
    it('allows five active sliders and rejects a sixth active one on create', function () {
        Slider::factory()->count(4)->create();

        $this->post(route('admin.sliders.store'), mediaSliderData())->assertSessionHasNoErrors();
        expect(Slider::active()->count())->toBe(5);

        $this->post(route('admin.sliders.store'), mediaSliderData(['title' => 'Sixth']))
            ->assertSessionHasErrors(['status' => 'You can have at most 5 active Hero sliders. Deactivate one first.']);

        expect(Slider::count())->toBe(5)
            ->and(Storage::disk('uploads')->allFiles())->toHaveCount(2);
    });

    it('still allows creating an inactive slider when five are active', function () {
        Slider::factory()->count(5)->create();

        $this->post(route('admin.sliders.store'), mediaSliderData(['status' => 'inactive']))
            ->assertSessionHasNoErrors();

        expect(Slider::count())->toBe(6)->and(Slider::active()->count())->toBe(5);
    });

    it('rejects activating a sixth slider', function () {
        Slider::factory()->count(5)->create();
        $inactive = Slider::factory()->inactive()->create();

        mediaUpdate($inactive, ['status' => 'active'])->assertSessionHasErrors('status');

        expect($inactive->fresh()->status)->toBe(ActiveStatus::Inactive);
    });

    it('lets an already active slider be edited while five are active', function () {
        $sliders = Slider::factory()->count(5)->create();

        mediaUpdate($sliders->first(), ['title' => 'Renamed', 'status' => 'active'])
            ->assertSessionHasNoErrors();

        expect($sliders->first()->fresh()->title)->toBe('Renamed');
    });

    it('lets one slider be deactivated and another activated afterwards', function () {
        $sliders = Slider::factory()->count(5)->create();
        $inactive = Slider::factory()->inactive()->create();

        mediaUpdate($sliders->first(), ['status' => 'inactive'])->assertSessionHasNoErrors();
        mediaUpdate($inactive, ['status' => 'active'])->assertSessionHasNoErrors();

        expect(Slider::active()->count())->toBe(5);
    });

    it('does not count soft-deleted sliders', function () {
        Slider::factory()->count(5)->create();
        Slider::first()->delete();

        $this->post(route('admin.sliders.store'), mediaSliderData())->assertSessionHasNoErrors();
    });
});
