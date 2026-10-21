<?php

namespace App\Services\Home;

use App\Services\Category\CategoryService;
use App\Services\Order\DeliveryZones;
use App\Services\Product\ProductService;
use App\Services\Slider\SliderService;
use App\Services\Testimonial\TestimonialService;

/**
 * Composes the Home page data from the per-domain caches. Each domain service owns
 * and clears its own cache key; the composed result is never cached as a whole.
 */
class HomeService
{
    public function __construct(
        private readonly SliderService $sliders,
        private readonly CategoryService $categories,
        private readonly ProductService $products,
        private readonly TestimonialService $testimonials,
        private readonly DeliveryZones $zones,
    ) {}

    /**
     * @return array{heroSlides: array<int, array<string, mixed>>, promo: ?array<string, mixed>, categories: array<int, array<string, mixed>>, featured: array<int, array<string, mixed>>, categoryRails: list<array{category: array{name: string, slug: string}, products: array<int, array<string, mixed>>}>, newArrivals: array<int, array<string, mixed>>, testimonials: array<int, array<string, mixed>>, deliveryZones: list<array{key: string, label: string, charge: int}>, seo: array<string, mixed>}
     */
    public function data(): array
    {
        $sliders = $this->sliders->activeForStorefront();

        return [
            'heroSlides' => $sliders['hero'],
            'promo' => $sliders['promo'],
            'categories' => $this->categories->activeForStorefront(),
            'featured' => $this->products->featuredForStorefront(),
            'categoryRails' => $this->products->categoryRailsForStorefront(),
            'newArrivals' => $this->products->newArrivalsForStorefront(),
            'testimonials' => $this->testimonials->activeForStorefront(),
            'deliveryZones' => $this->zones->forStorefront(),
            'seo' => $this->seo($sliders['hero'][0]['image'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seo(?string $heroImage): array
    {
        $name = config('app.name');
        $home = route('home');

        return [
            'title' => $name,
            'description' => "Kitchenware and fashion delivered to your door across Bangladesh. Pay with Cash on Delivery at {$name}.",
            'canonical' => $home,
            'noindex' => false,
            'image' => $heroImage === null ? null : url($heroImage),
            'jsonLd' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => $name,
                    'url' => $home,
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $name,
                    'url' => $home,
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => route('search').'?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    }
}
