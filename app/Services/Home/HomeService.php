<?php

namespace App\Services\Home;

use App\Services\Category\CategoryService;
use App\Services\Product\ProductService;
use App\Services\Slider\SliderService;

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
    ) {}

    /**
     * @return array{sliders: array<int, array<string, mixed>>, promo: ?array<string, mixed>, categories: array<int, array<string, ?string>>, featuredProducts: array<int, array<string, mixed>>}
     */
    public function data(): array
    {
        $sliders = $this->sliders->activeForStorefront();

        return [
            'sliders' => $sliders['hero'],
            'promo' => $sliders['promo'],
            'categories' => $this->categories->activeForStorefront(),
            'featuredProducts' => $this->products->featuredForStorefront(),
        ];
    }
}
