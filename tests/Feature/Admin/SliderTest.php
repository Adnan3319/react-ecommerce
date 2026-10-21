<?php

use App\Enums\SliderPosition;
use App\Enums\ActiveStatus;
use App\Models\Slider;
use App\Models\User;
use App\Services\Slider\SliderService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('uploads');
});

function sliderAdmin(): User
{
    return User::factory()->create();
}

function sliderImageSize(string $path): array
{
    return array_slice(getimagesizefromstring(Storage::disk('uploads')->get($path)), 0, 2);
}

function validSlider(array $overrides = []): array
{
    return [
        'title' => 'Smart Kitchen',
        'subtitle' => 'Tools that make cooking easier.',
        'button_text' => 'Shop Now',
        'button_link' => '/search',
        'sort_order' => 1,
        'status' => 'active',
        'position' => 'hero',
        'image' => UploadedFile::fake()->image('banner.jpg', 2400, 1000),
        ...$overrides,
    ];
}

function updateSlider(Slider $slider, array $data): TestResponse
{
    return test()->post(route('admin.sliders.update', $slider), ['_method' => 'put', ...$data]);
}

describe('access', function () {
    it('redirects guests to the login page', function () {
        $slider = Slider::factory()->create();

        $this->get(route('admin.sliders.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.sliders.store'), validSlider())->assertRedirect(route('admin.login'));
        updateSlider($slider, ['status' => 'inactive'])->assertRedirect(route('admin.login'));
        $this->delete(route('admin.sliders.destroy', $slider))->assertRedirect(route('admin.login'));
    });
});

describe('index', function () {
    beforeEach(fn () => $this->actingAs(sliderAdmin()));

    it('lists sliders ordered by sort order then id', function () {
        $second = Slider::factory()->create(['title' => 'Second', 'sort_order' => 2]);
        Slider::factory()->create(['title' => 'First', 'sort_order' => 1]);
        $third = Slider::factory()->create(['title' => 'Third', 'sort_order' => 2]);

        $this->get(route('admin.sliders.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Sliders/Index', false)
                ->has('sliders.data', 3)
                ->where('sliders.data.0.title', 'First')
                ->where('sliders.data.1.id', $second->id)
                ->where('sliders.data.2.id', $third->id)
                ->has('statuses', 2));
    });

    it('exposes a public image url, not the stored path', function () {
        Slider::factory()->create(['image' => 'sliders/a.webp']);

        $this->get(route('admin.sliders.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sliders.data.0.image_url', Storage::disk('uploads')->url('sliders/a.webp'))
                ->missing('sliders.data.0.image'));
    });
});

describe('create', function () {
    beforeEach(fn () => $this->actingAs($this->admin = sliderAdmin()));

    it('creates a slider, resizes the image to 1920x800 webp and records the creator', function () {
        $this->post(route('admin.sliders.store'), validSlider())->assertSessionHasNoErrors()->assertRedirect();

        $slider = Slider::sole();

        expect($slider->status)->toBe(ActiveStatus::Active)
            ->and($slider->created_by)->toBe($this->admin->id)
            ->and($slider->image)->toStartWith('sliders/')->toEndWith('.webp')
            ->and(Storage::disk('uploads')->exists($slider->image))->toBeTrue()
            ->and(sliderImageSize($slider->image))->toBe([1920, 800]);
    });

    it('stores the original image when resizing is disabled', function () {
        config(['media.resize_enabled' => false]);

        $this->post(route('admin.sliders.store'), validSlider([
            'image' => UploadedFile::fake()->image('banner.png', 2000, 900),
        ]))->assertSessionHasNoErrors();

        expect(sliderImageSize(Slider::sole()->image))->toBe([2000, 900]);
    });

    it('requires an image', function () {
        $data = validSlider();
        unset($data['image']);

        $this->post(route('admin.sliders.store'), $data)->assertSessionHasErrors('image');

        expect(Slider::count())->toBe(0);
    });

    it('rejects files that are not valid images and raw image paths', function (mixed $image) {
        $this->post(route('admin.sliders.store'), validSlider(['image' => $image]))
            ->assertSessionHasErrors('image');

        expect(Slider::count())->toBe(0);
    })->with([
        'text file' => fn () => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        'gif image' => fn () => UploadedFile::fake()->image('anim.gif'),
        'over 2MB' => fn () => UploadedFile::fake()->image('big.jpg', 2000, 900)->size(2049),
        'too narrow (1919px)' => fn () => UploadedFile::fake()->image('narrow.png', 1919, 800),
        'too short (799px)' => fn () => UploadedFile::fake()->image('short.png', 1920, 799),
        'too many pixels' => fn () => UploadedFile::fake()->image('huge.png', 5001, 900),
        'too tall' => fn () => UploadedFile::fake()->image('tall.png', 2000, 5001),
        'raw path string' => '/images/sliders/1.jpg',
    ]);

    it('rejects invalid data', function (array $overrides, string $field) {
        $this->post(route('admin.sliders.store'), validSlider($overrides))->assertSessionHasErrors($field);
    })->with([
        'missing title' => [['title' => ''], 'title'],
        'long button text' => [['button_text' => str_repeat('a', 51)], 'button_text'],
        'bad status' => [['status' => 'Active'], 'status'],
        'negative sort order' => [['sort_order' => -1], 'sort_order'],
        'sort order too large' => [['sort_order' => 70000], 'sort_order'],
    ]);

    it('accepts relative and http(s) button links', function (string $link) {
        $this->post(route('admin.sliders.store'), validSlider(['button_link' => $link]))
            ->assertSessionHasNoErrors();

        expect(Slider::sole()->button_link)->toBe($link);
    })->with(['/search', '/products/kettle?x=1', 'https://example.com/sale', 'http://example.com']);

    it('rejects unsafe or malformed button links', function (string $link) {
        $this->post(route('admin.sliders.store'), validSlider(['button_link' => $link]))
            ->assertSessionHasErrors('button_link');

        expect(Slider::count())->toBe(0);
    })->with([
        'javascript' => 'javascript:alert(1)',
        'data uri' => 'data:text/html;base64,PHNjcmlwdD4=',
        'protocol relative' => '//evil.example.com',
        'bare domain' => 'example.com/sale',
        'with spaces' => '/search now',
    ]);
});

describe('update', function () {
    beforeEach(fn () => $this->actingAs($this->admin = sliderAdmin()));

    it('updates fields and records the editor', function () {
        $slider = Slider::factory()->create(['title' => 'Old']);

        updateSlider($slider, ['title' => 'New', 'status' => 'inactive', 'sort_order' => 5])
            ->assertSessionHasNoErrors();

        $slider->refresh();

        expect($slider->title)->toBe('New')
            ->and($slider->status)->toBe(ActiveStatus::Inactive)
            ->and($slider->sort_order)->toBe(5)
            ->and($slider->updated_by)->toBe($this->admin->id);
    });

    it('allows a partial update such as a status toggle', function () {
        $slider = Slider::factory()->create(['title' => 'Keep', 'image' => 'sliders/keep.webp']);

        updateSlider($slider, ['status' => 'inactive'])->assertSessionHasNoErrors();

        expect($slider->fresh())
            ->status->toBe(ActiveStatus::Inactive)
            ->title->toBe('Keep')
            ->image->toBe('sliders/keep.webp');
    });

    it('keeps the current image when none is uploaded', function () {
        $slider = Slider::factory()->create(['image' => 'sliders/keep.webp']);
        Storage::disk('uploads')->put('sliders/keep.webp', 'bytes');

        updateSlider($slider, ['title' => 'Renamed'])->assertSessionHasNoErrors();

        expect($slider->fresh()->image)->toBe('sliders/keep.webp')
            ->and(Storage::disk('uploads')->exists('sliders/keep.webp'))->toBeTrue();
    });

    it('replaces the image and deletes the old file', function () {
        $slider = Slider::factory()->create(['image' => 'sliders/old.webp']);
        Storage::disk('uploads')->put('sliders/old.webp', 'old-bytes');

        updateSlider($slider, ['image' => UploadedFile::fake()->image('new.jpg', 2000, 900)])
            ->assertSessionHasNoErrors();

        $newPath = $slider->fresh()->image;

        expect($newPath)->not->toBe('sliders/old.webp')
            ->and(Storage::disk('uploads')->exists($newPath))->toBeTrue()
            ->and(sliderImageSize($newPath))->toBe([1920, 800])
            ->and(Storage::disk('uploads')->exists('sliders/old.webp'))->toBeFalse();
    });

    it('never deletes static seed images when replacing them', function () {
        $slider = Slider::factory()->create(['image' => '/images/sliders/1.jpg']);

        updateSlider($slider, ['image' => UploadedFile::fake()->image('new.jpg', 1920, 800)])->assertSessionHasNoErrors();

        expect($slider->fresh()->image)->toStartWith('sliders/');
    });

    it('keeps the old image and removes the new upload when the update fails', function () {
        $slider = Slider::factory()->create(['image' => 'sliders/old.webp']);
        Storage::disk('uploads')->put('sliders/old.webp', 'old-bytes');
        Slider::updating(fn () => throw new RuntimeException('boom'));

        $this->withoutExceptionHandling();

        expect(fn () => updateSlider($slider, ['image' => UploadedFile::fake()->image('new.jpg', 1920, 800)]))
            ->toThrow(RuntimeException::class, 'boom');

        expect(Storage::disk('uploads')->allFiles())->toBe(['sliders/old.webp'])
            ->and($slider->fresh()->image)->toBe('sliders/old.webp');
    });

    it('rejects an unsafe button link on update', function () {
        $slider = Slider::factory()->create();

        updateSlider($slider, ['button_link' => 'javascript:alert(1)'])->assertSessionHasErrors('button_link');
    });

    it('can clear the button link', function () {
        $slider = Slider::factory()->create(['button_link' => '/search']);

        updateSlider($slider, ['button_link' => ''])->assertSessionHasNoErrors();

        expect($slider->fresh()->button_link)->toBeNull();
    });
});

describe('delete', function () {
    beforeEach(fn () => $this->actingAs($this->admin = sliderAdmin()));

    it('soft deletes, records who deleted it and removes the image file', function () {
        $slider = Slider::factory()->create(['image' => 'sliders/pic.webp']);
        Storage::disk('uploads')->put('sliders/pic.webp', 'bytes');

        $this->delete(route('admin.sliders.destroy', $slider))->assertRedirect();

        $trashed = Slider::withTrashed()->findOrFail($slider->id);

        expect($trashed->trashed())->toBeTrue()
            ->and($trashed->deleted_by)->toBe($this->admin->id)
            ->and(Storage::disk('uploads')->exists('sliders/pic.webp'))->toBeFalse();
    });

    it('does not touch static seed images', function () {
        $slider = Slider::factory()->create(['image' => '/images/sliders/1.jpg']);

        $this->delete(route('admin.sliders.destroy', $slider))->assertRedirect();

        expect(Slider::count())->toBe(0);
    });

    it('keeps the image file when the delete fails', function () {
        $slider = Slider::factory()->create(['image' => 'sliders/pic.webp']);
        Storage::disk('uploads')->put('sliders/pic.webp', 'bytes');
        Slider::deleting(fn () => throw new RuntimeException('boom'));

        $this->withoutExceptionHandling();

        expect(fn () => $this->delete(route('admin.sliders.destroy', $slider)))
            ->toThrow(RuntimeException::class);

        expect(Storage::disk('uploads')->exists('sliders/pic.webp'))->toBeTrue();
    });
});

describe('storefront cache', function () {
    beforeEach(fn () => $this->actingAs(sliderAdmin()));

    function primeSliderCache(): void
    {
        app(SliderService::class)->activeForStorefront();
        expect(Cache::has(SliderService::cacheKey(SliderPosition::Hero)))->toBeTrue();
    }

    it('caches only active sliders, in order, as plain arrays with a public image url', function () {
        $later = Slider::factory()->create(['title' => 'Later', 'sort_order' => 2, 'image' => 'sliders/b.webp']);
        $first = Slider::factory()->create(['title' => 'First', 'sort_order' => 1, 'image' => 'sliders/a.webp']);
        Slider::factory()->inactive()->create(['title' => 'Hidden']);

        $sliders = app(SliderService::class)->activeForStorefront()['hero'];

        expect($sliders)->toHaveCount(2)
            ->and(Cache::get(SliderService::cacheKey(SliderPosition::Hero)))->toBe($sliders)
            ->and($sliders[0])->toBe([
                'id' => $first->id,
                'title' => 'First',
                'subtitle' => $first->subtitle,
                'image' => Storage::disk('uploads')->url('sliders/a.webp'),
                'image_srcset' => null,
                'button_text' => 'Shop Now',
                'button_link' => '/search',
            ])
            ->and($sliders[1]['id'])->toBe($later->id);
    });

    it('is shared with the Home page as the heroSlides prop', function () {
        Slider::factory()->create(['title' => 'Shown', 'image' => '/images/sliders/1.jpg']);
        Slider::factory()->inactive()->create(['title' => 'Hidden']);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('heroSlides', 1)
                ->where('heroSlides.0.title', 'Shown')
                ->where('heroSlides.0.image', '/images/sliders/1.jpg'));
    });

    it('is cleared when a slider is created', function () {
        primeSliderCache();

        $this->post(route('admin.sliders.store'), validSlider());

        expect(Cache::has(SliderService::cacheKey(SliderPosition::Hero)))->toBeFalse();
    });

    it('is cleared when a slider is updated', function () {
        $slider = Slider::factory()->create();
        primeSliderCache();

        updateSlider($slider, ['status' => 'inactive']);

        expect(Cache::has(SliderService::cacheKey(SliderPosition::Hero)))->toBeFalse();
    });

    it('is cleared when a slider is deleted', function () {
        $slider = Slider::factory()->create();
        primeSliderCache();

        $this->delete(route('admin.sliders.destroy', $slider));

        expect(Cache::has(SliderService::cacheKey(SliderPosition::Hero)))->toBeFalse();
    });

    it('is left alone when validation fails', function () {
        primeSliderCache();

        $this->post(route('admin.sliders.store'), validSlider(['title' => '']));

        expect(Cache::has(SliderService::cacheKey(SliderPosition::Hero)))->toBeTrue();
    });
});
