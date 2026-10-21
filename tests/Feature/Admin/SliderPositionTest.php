<?php

use App\Enums\SliderPosition;
use App\Models\Slider;
use App\Services\Slider\SliderService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('uploads');
    actingAsAdmin();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function positionPayload(string $position, array $overrides = []): array
{
    return [
        'title' => 'Banner',
        'status' => 'active',
        'position' => $position,
        'image' => UploadedFile::fake()->image('banner.jpg', 2400, 1000),
        ...$overrides,
    ];
}

function positionUpdate(Slider $slider, array $data): TestResponse
{
    return test()->post(route('admin.sliders.update', $slider), ['_method' => 'put', ...$data]);
}

describe('position', function () {
    it('is stored on create', function (string $position) {
        $this->post(route('admin.sliders.store'), positionPayload($position))->assertSessionHasNoErrors();

        expect(Slider::sole()->position)->toBe(SliderPosition::from($position));
    })->with(['hero', 'promo']);

    it('is required and must be known', function (mixed $position) {
        $payload = positionPayload('hero');
        $payload['position'] = $position;

        $this->post(route('admin.sliders.store'), $payload)->assertSessionHasErrors('position');

        expect(Slider::count())->toBe(0);
    })->with(['missing' => [null], 'unknown' => ['footer']]);

    it('defaults to hero for sliders created without it', function () {
        expect(Slider::factory()->create()->fresh()->position)->toBe(SliderPosition::Hero);
    });

    it('is kept by a partial update such as a status toggle', function () {
        $slider = Slider::factory()->promo()->create();

        positionUpdate($slider, ['status' => 'inactive'])->assertSessionHasNoErrors();

        expect($slider->fresh()->position)->toBe(SliderPosition::Promo);
    });

    it('is shown in the admin list', function () {
        Slider::factory()->promo()->create();

        $this->get(route('admin.sliders.index'))->assertInertia(fn (Assert $page) => $page
            ->where('sliders.data.0.position', 'promo')
            ->has('positions', 2)
            ->where('positions.1', ['value' => 'promo', 'label' => 'Promo', 'image_hint' => 'Wide image, at least 1200x800, max 2MB']));
    });
});

describe('list filter', function () {
    it('lists only the sliders of the chosen position', function (string $position, string $expected) {
        Slider::factory()->create(['title' => 'Hero one']);
        Slider::factory()->promo()->create(['title' => 'Promo one']);

        $page = $this->get(route('admin.sliders.index', ['position' => $position]))->assertOk()->viewData('page');

        expect(collect($page['props']['sliders']['data'])->pluck('title')->all())->toBe([$expected])
            ->and($page['props']['filters']['position'])->toBe($position);
    })->with([
        'hero' => ['hero', 'Hero one'],
        'promo' => ['promo', 'Promo one'],
    ]);

    it('lists everything without a position', function () {
        Slider::factory()->create();
        Slider::factory()->promo()->create();

        $this->get(route('admin.sliders.index'))->assertInertia(fn (Assert $page) => $page->has('sliders.data', 2)->where('filters.position', null));
    });

    it('rejects an unknown position', function () {
        $this->get(route('admin.sliders.index', ['position' => 'footer']))->assertSessionHasErrors('position');
    });
});

describe('active limit per position', function () {
    it('allows 5 hero and 5 promo sliders at the same time', function () {
        Slider::factory()->count(4)->create();
        Slider::factory()->promo()->count(4)->create();

        $this->post(route('admin.sliders.store'), positionPayload('hero'))->assertSessionHasNoErrors();
        $this->post(route('admin.sliders.store'), positionPayload('promo'))->assertSessionHasNoErrors();

        expect(Slider::active()->inPosition(SliderPosition::Hero)->count())->toBe(5)
            ->and(Slider::active()->inPosition(SliderPosition::Promo)->count())->toBe(5);
    });

    it('rejects a sixth active slider of a position', function (string $position) {
        Slider::factory()->count(5)->state(['position' => $position])->create();

        $this->post(route('admin.sliders.store'), positionPayload($position))->assertSessionHasErrors('status');

        expect(Slider::count())->toBe(5);
    })->with(['hero', 'promo']);

    it('accepts an inactive sixth slider', function () {
        Slider::factory()->promo()->count(5)->create();

        $this->post(route('admin.sliders.store'), positionPayload('promo', ['status' => 'inactive']))->assertSessionHasNoErrors();

        expect(Slider::count())->toBe(6);
    });

    it('rejects activating a sixth slider of a position', function () {
        Slider::factory()->promo()->count(5)->create();
        $inactive = Slider::factory()->promo()->inactive()->create();

        positionUpdate($inactive, ['status' => 'active'])->assertSessionHasErrors('status');
    });

    it('lets an active slider of a full position be edited', function () {
        $sliders = Slider::factory()->promo()->count(5)->create();

        positionUpdate($sliders->first(), ['title' => 'Renamed'])->assertSessionHasNoErrors();

        expect($sliders->first()->fresh()->title)->toBe('Renamed');
    });

    it('rejects moving an active slider into a full position', function () {
        Slider::factory()->promo()->count(5)->create();
        $hero = Slider::factory()->create();

        positionUpdate($hero, ['position' => 'promo', 'image' => UploadedFile::fake()->image('p.jpg', 1400, 900)])
            ->assertSessionHasErrors('status');

        expect($hero->fresh()->position)->toBe(SliderPosition::Hero);
    });

    it('allows moving an inactive slider into a full position', function () {
        Slider::factory()->promo()->count(5)->create();
        $hero = Slider::factory()->inactive()->create();

        positionUpdate($hero, ['position' => 'promo', 'image' => UploadedFile::fake()->image('p.jpg', 1400, 900)])
            ->assertSessionHasNoErrors();

        expect($hero->fresh()->position)->toBe(SliderPosition::Promo);
    });
});

describe('image rules and presets', function () {
    it('accepts 1200x800 for promo but not for hero', function (string $position, bool $accepted) {
        $payload = positionPayload($position, ['image' => UploadedFile::fake()->image('b.jpg', 1200, 800)]);

        $response = $this->post(route('admin.sliders.store'), $payload);

        $accepted ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('image');
    })->with([
        'promo' => ['promo', true],
        'hero' => ['hero', false],
    ]);

    it('rejects a promo image under 1200x800 or over 2MB', function (UploadedFile $image) {
        $this->post(route('admin.sliders.store'), positionPayload('promo', ['image' => $image]))->assertSessionHasErrors('image');

        expect(Slider::count())->toBe(0);
    })->with([
        'too narrow' => [fn () => UploadedFile::fake()->image('b.jpg', 1100, 900)],
        'too short' => [fn () => UploadedFile::fake()->image('b.jpg', 1400, 700)],
        'too heavy' => [fn () => UploadedFile::fake()->image('b.jpg', 1400, 900)->size(2049)],
    ]);

    it('stores a promo image as 1200x800 and 600x400 with a two-size srcset', function () {
        $this->post(route('admin.sliders.store'), positionPayload('promo'))->assertSessionHasNoErrors();

        $slider = Slider::sole();
        $small = str_replace('.webp', '-sm.webp', $slider->image);
        $size = fn (string $path) => array_slice(getimagesizefromstring(Storage::disk('uploads')->get($path)), 0, 2);
        $disk = Storage::disk('uploads');

        expect($size($slider->image))->toBe([1200, 800])
            ->and($size($small))->toBe([600, 400])
            ->and(app(SliderService::class)->activeForStorefront()['promo']['image_srcset'])
            ->toBe($disk->url($small).' 600w, '.$disk->url($slider->image).' 1200w');
    });

    it('keeps hero images at 1920x800 and 960x400', function () {
        $this->post(route('admin.sliders.store'), positionPayload('hero'))->assertSessionHasNoErrors();

        $slider = Slider::sole();
        $size = fn (string $path) => array_slice(getimagesizefromstring(Storage::disk('uploads')->get($path)), 0, 2);

        expect($size($slider->image))->toBe([1920, 800])
            ->and($size(str_replace('.webp', '-sm.webp', $slider->image)))->toBe([960, 400]);
    });

    it('requires a new image when the position changes and replaces the old files', function () {
        $this->post(route('admin.sliders.store'), positionPayload('hero'))->assertSessionHasNoErrors();
        $slider = Slider::sole();
        $old = $slider->image;

        positionUpdate($slider, ['position' => 'promo'])->assertSessionHasErrors('image');
        expect($slider->fresh()->image)->toBe($old);

        positionUpdate($slider, ['position' => 'promo', 'image' => UploadedFile::fake()->image('p.jpg', 1400, 900)])->assertSessionHasNoErrors();

        $moved = $slider->fresh();
        expect($moved->position)->toBe(SliderPosition::Promo)
            ->and($moved->image)->not->toBe($old)
            ->and(Storage::disk('uploads')->exists($old))->toBeFalse()
            ->and(Storage::disk('uploads')->allFiles())->toHaveCount(2);
    });
});

describe('storefront', function () {
    it('returns the hero list and the first active promo slider', function () {
        $secondPromo = Slider::factory()->promo()->create(['title' => 'Second promo', 'sort_order' => 2]);
        $firstPromo = Slider::factory()->promo()->create(['title' => 'First promo', 'sort_order' => 1]);
        Slider::factory()->promo()->inactive()->create(['title' => 'Hidden promo', 'sort_order' => 0]);
        Slider::factory()->create(['title' => 'Hero']);

        $sliders = app(SliderService::class)->activeForStorefront();

        expect($sliders)->toHaveKeys(['hero', 'promo'])
            ->and(array_column($sliders['hero'], 'title'))->toBe(['Hero'])
            ->and($sliders['promo']['id'])->toBe($firstPromo->id)
            ->and($sliders['promo']['id'])->not->toBe($secondPromo->id)
            ->and(array_keys($sliders['promo']))->toBe(['id', 'title', 'subtitle', 'image', 'image_srcset', 'button_text', 'button_link']);
    });

    it('has no promo without an active promo slider', function () {
        Slider::factory()->promo()->inactive()->create();
        Slider::factory()->create();

        expect(app(SliderService::class)->activeForStorefront()['promo'])->toBeNull();
    });

    it('is shared with the Home page: hero list as heroSlides, promo as promo', function () {
        Slider::factory()->create(['title' => 'Hero', 'image' => '/images/sliders/1.jpg']);
        Slider::factory()->promo()->create(['title' => 'Promo', 'image' => '/images/sliders/2.jpg']);

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->has('heroSlides', 1)
            ->where('heroSlides.0.title', 'Hero')
            ->where('promo.title', 'Promo'));
    });

    it('caches each position under its own key', function () {
        Slider::factory()->create();
        Slider::factory()->promo()->create();

        app(SliderService::class)->activeForStorefront();

        expect(Cache::has(SliderService::cacheKey(SliderPosition::Hero)))->toBeTrue()
            ->and(Cache::has(SliderService::cacheKey(SliderPosition::Promo)))->toBeTrue()
            ->and(SliderService::cacheKey(SliderPosition::Promo))->toBe('sliders.active.promo');
    });

    it('clears both position keys on every write', function (Closure $write) {
        $slider = Slider::factory()->promo()->create();
        app(SliderService::class)->activeForStorefront();

        $write($slider);

        expect(Cache::has(SliderService::cacheKey(SliderPosition::Hero)))->toBeFalse()
            ->and(Cache::has(SliderService::cacheKey(SliderPosition::Promo)))->toBeFalse();
    })->with([
        'create' => [fn () => test()->post(route('admin.sliders.store'), positionPayload('promo'))],
        'update' => [fn (Slider $slider) => positionUpdate($slider, ['status' => 'inactive'])],
        'delete' => [fn (Slider $slider) => test()->delete(route('admin.sliders.destroy', $slider))],
    ]);
});
