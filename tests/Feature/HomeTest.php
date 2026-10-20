<?php

use App\Enums\SliderPosition;
use App\Models\Category;
use App\Models\Slider;
use App\Models\User;
use App\Services\Category\CategoryService;
use App\Services\Home\HomeService;
use App\Services\Slider\SliderService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('uploads');
});

it('composes sliders and categories from their own caches', function () {
    Slider::factory()->create(['title' => 'Hero']);
    Category::factory()->create(['name' => 'Kitchenware', 'slug' => 'kitchenware']);

    $data = app(HomeService::class)->data();

    expect($data)->toHaveKeys(['sliders', 'promo', 'categories'])
        ->and($data['sliders'])->toBe(Cache::get(SliderService::cacheKey(SliderPosition::Hero)))
        ->and($data['categories'])->toBe(Cache::get(CategoryService::ACTIVE_CACHE_KEY));
});

it('renders the Home page with sliders and categories props', function () {
    Slider::factory()->create(['title' => 'Hero', 'image' => '/images/sliders/1.jpg']);
    Category::factory()->create(['slug' => 'kitchenware']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sliders', 1)
            ->where('sliders.0.title', 'Hero')
            ->has('categories', 1)
            ->where('categories.0.slug', 'kitchenware'));
});

it('preloads the first hero image in the page head', function () {
    Slider::factory()->create(['image' => 'sliders/hero.webp']);
    Storage::disk('uploads')->put('sliders/hero.webp', 'big');
    Storage::disk('uploads')->put('sliders/hero-sm.webp', 'small');

    $url = Storage::disk('uploads')->url('sliders/hero.webp');
    $small = Storage::disk('uploads')->url('sliders/hero-sm.webp');

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect(preg_replace('/\s+/', ' ', $html))->toContain(
        '<link rel="preload" as="image" href="'.$url.'" imagesrcset="'.$small.' 960w, '.$url.' 1920w" imagesizes="100vw" fetchpriority="high">',
    );
});

it('does not preload anything without sliders or on other pages', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('rel="preload" as="image"', false);

    Slider::factory()->create();

    $this->get(route('contact'))->assertOk()->assertDontSee('rel="preload" as="image"', false);
});

describe('cache invalidation per key', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create());
        $this->slider = Slider::factory()->create();
        $this->category = Category::factory()->create();
        app(HomeService::class)->data();
    });

    it('a category write clears only the categories key', function () {
        $this->post(route('admin.categories.update', $this->category), ['_method' => 'put', 'status' => 'inactive']);

        expect(Cache::has(CategoryService::ACTIVE_CACHE_KEY))->toBeFalse()
            ->and(Cache::has(SliderService::cacheKey(SliderPosition::Hero)))->toBeTrue();
    });

    it('a slider write clears only the sliders key', function () {
        $this->post(route('admin.sliders.update', $this->slider), ['_method' => 'put', 'status' => 'inactive']);

        expect(Cache::has(SliderService::cacheKey(SliderPosition::Hero)))->toBeFalse()
            ->and(Cache::has(CategoryService::ACTIVE_CACHE_KEY))->toBeTrue();
    });

    it('the Home page reflects a write right after it', function () {
        $this->post(route('admin.sliders.update', $this->slider), ['_method' => 'put', 'status' => 'inactive']);

        $this->get(route('home'))->assertInertia(fn (AssertableInertia $page) => $page->has('sliders', 0));
    });
});
