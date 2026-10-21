<?php

use App\Enums\SliderPosition;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slider;
use App\Models\Testimonial;
use App\Services\Product\ProductCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('uploads');
});

it('sends every Home prop with its documented shape', function () {
    Category::factory()->create(['slug' => 'kitchenware']);
    Product::factory()->withOptions(['Size' => ['S', 'M', 'L'], 'Color' => ['Pink', 'Blue']])->featured()->create(['name' => 'Dress']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home', false)
            ->hasAll(['heroSlides', 'promo', 'categories', 'featured', 'categoryRails', 'newArrivals', 'testimonials', 'deliveryZones', 'seo'])
            ->has('featured.0', fn (AssertableInertia $card) => $card
                ->where('name', 'Dress')
                ->where('options_summary', '3 sizes · 2 colors')
                ->where('has_variants', true)
                ->where('default_variant_id', null)
                ->where('in_stock', true)
                ->where('is_new', true)
                ->hasAll(['id', 'slug', 'thumb', 'srcset', 'price', 'sale_price', 'discount_percent'])));
});

it('hides the promo and the testimonials when there are none', function () {
    $this->get(route('home'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('promo', null)
        ->has('testimonials', 0)
        ->has('featured', 0)
        ->has('newArrivals', 0));
});

it('sends the first active promo slider and only active testimonials', function () {
    Slider::factory()->create(['title' => 'Sale', 'position' => SliderPosition::Promo]);
    Testimonial::factory()->create(['customer_name' => 'Live']);
    Testimonial::factory()->inactive()->create(['customer_name' => 'Hidden']);

    $this->get(route('home'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('promo.title', 'Sale')
        ->has('testimonials', 1)
        ->where('testimonials.0.customer_name', 'Live'));
});

it('gives a simple product its default variant id', function () {
    $product = Product::factory()->simple()->featured()->create();

    $this->get(route('home'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('featured.0.default_variant_id', $product->variants()->value('id'))
        ->where('featured.0.options_summary', null)
        ->where('featured.0.has_variants', false));
});

it('lists only active in-stock products in both rails', function () {
    Product::factory()->simple()->featured()->create(['name' => 'Shown']);
    Product::factory()->simple(stock: 0)->featured()->create(['name' => 'Sold out']);
    Product::factory()->simple()->featured()->inactive()->create(['name' => 'Inactive']);
    Product::factory()->simple()->create(['name' => 'Plain']);

    $this->get(route('home'))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('featured', 1)
        ->where('featured.0.name', 'Shown')
        ->has('newArrivals', 2)
        ->where('newArrivals.0.name', 'Plain')
        ->where('newArrivals.1.name', 'Shown'));
});

it('limits each rail to ten products', function () {
    Product::factory()->count(11)->simple()->featured()->create();

    $this->get(route('home'))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('featured', 10)
        ->has('newArrivals', 10));
});

it('marks products older than two weeks as not new', function () {
    Product::factory()->simple()->create(['created_at' => now()->subDays(20)]);

    $this->get(route('home'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('newArrivals.0.is_new', false));
});

it('sends the delivery zones from the configuration', function () {
    $this->get(route('home'))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('deliveryZones', count(config('shop.delivery_zones')))
        ->has('deliveryZones.0', fn (AssertableInertia $zone) => $zone->hasAll(['key', 'label', 'charge'])));
});

describe('seo', function () {
    it('describes the page with Organization and WebSite JSON-LD with a search action', function () {
        $seo = $this->get(route('home'))->viewData('page')['props']['seo'];

        expect($seo['canonical'])->toBe(route('home'))
            ->and($seo['noindex'])->toBeFalse()
            ->and(collect($seo['jsonLd'])->pluck('@type')->all())->toBe(['Organization', 'WebSite'])
            ->and($seo['jsonLd'][1]['potentialAction']['target'])->toBe(route('search').'?q={search_term_string}');
    });

    it('uses the first hero slide as the social image', function () {
        Slider::factory()->create(['image' => 'sliders/hero.webp']);

        $seo = $this->get(route('home'))->viewData('page')['props']['seo'];

        expect($seo['image'])->toBe(url(Storage::disk('uploads')->url('sliders/hero.webp')));
    });

    it('renders the WebSite JSON-LD into the server HTML', function () {
        $this->get(route('home'))->assertSee('"@type":"WebSite"', false);
    });
});

describe('cache', function () {
    it('serves both rails from their own cache keys', function () {
        Product::factory()->simple()->featured()->create();

        $this->get(route('home'));

        expect(Cache::has(ProductCache::FEATURED_KEY))->toBeTrue()
            ->and(Cache::has(ProductCache::NEW_ARRIVALS_KEY))->toBeTrue();
    });

    it('clears both rails on a product write', function () {
        actingAsAdmin();
        $product = Product::factory()->simple()->featured()->create();
        $this->get(route('home'));

        $this->post(route('admin.products.update', $product), ['_method' => 'put', 'status' => 'inactive']);

        expect(Cache::has(ProductCache::FEATURED_KEY))->toBeFalse()
            ->and(Cache::has(ProductCache::NEW_ARRIVALS_KEY))->toBeFalse();
    });
});
