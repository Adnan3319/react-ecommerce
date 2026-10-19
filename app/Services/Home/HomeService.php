<?php

namespace App\Services\Home;

use App\Services\Category\CategoryService;
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
    ) {}

    /**
     * @return array{sliders: array<int, array<string, mixed>>, categories: array<int, array<string, ?string>>}
     */
    public function data(): array
    {
        return [
            'sliders' => $this->sliders->activeForStorefront(),
            'categories' => $this->categories->activeForStorefront(),
        ];
    }
}
