<?php

namespace App\Services\Storefront;

use App\Models\Category;
use App\Services\Media\FileUploadService;

/**
 * The `seo` prop of the shared listing page. Category pages are indexable until a filter or sort is
 * active; the search page is never indexable. Pagination keeps its own canonical (`?page=N`).
 */
class ListingSeo
{
    public function __construct(private readonly FileUploadService $uploads) {}

    /**
     * @return array<string, mixed>
     */
    public function forCategory(Category $category, ProductFilter $filter, int $page): array
    {
        $name = config('app.name');
        $clean = route('categories.show', $category->slug);
        $canonical = $page > 1 ? $clean.'?page='.$page : $clean;
        $image = $this->uploads->url($category->image);

        return [
            'title' => "{$category->name} | {$name}",
            'description' => $category->description ?: "Shop {$category->name} online at {$name}. Cash on Delivery across Bangladesh.",
            'canonical' => $canonical,
            'noindex' => $filter->isNarrowed(),
            'image' => $image === null ? null : url($image),
            'jsonLd' => [$this->breadcrumbs($category, $clean)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forSearch(ProductFilter $filter): array
    {
        $title = $filter->q === null ? 'All products' : "Results for \"{$filter->q}\"";

        return [
            'title' => $title.' | '.config('app.name'),
            'description' => null,
            'canonical' => route('search'),
            'noindex' => true,
            'image' => null,
            'jsonLd' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function breadcrumbs(Category $category, string $canonical): array
    {
        $crumbs = [['name' => 'Home', 'item' => route('home')]];

        if ($category->parent !== null) {
            $crumbs[] = ['name' => $category->parent->name, 'item' => route('categories.show', $category->parent->slug)];
        }

        $crumbs[] = ['name' => $category->name, 'item' => $canonical];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                ...$crumb,
            ], $crumbs, array_keys($crumbs)),
        ];
    }
}
