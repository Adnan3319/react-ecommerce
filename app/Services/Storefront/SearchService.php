<?php

namespace App\Services\Storefront;

use App\Http\Resources\Storefront\ProductCardResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * The search-box dropdown: a few matching products plus the total, cached for five minutes per term.
 */
class SearchService
{
    public const SUGGESTION_LIMIT = 6;

    private const CACHE_PREFIX = 'search.suggest.';

    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly SearchMatcher $matcher,
        private readonly CatalogService $catalog,
    ) {}

    /**
     * @return array{items: list<array{name: string, category: ?string, thumb: ?string, price: ?int, url: string}>, total: int}
     */
    public function suggest(ProductFilter $filter): array
    {
        $term = $filter->searchTerm();

        if ($term === null) {
            return ['items' => [], 'total' => 0];
        }

        return Cache::remember(
            self::CACHE_PREFIX.sha1(mb_strtolower($term).'|'.$filter->category),
            self::CACHE_TTL_SECONDS,
            fn () => $this->search($term, $filter->category),
        );
    }

    /**
     * @return array{items: list<array{name: string, category: ?string, thumb: ?string, price: ?int, url: string}>, total: int}
     */
    private function search(string $term, ?string $categorySlug): array
    {
        $category = $categorySlug === null ? null : $this->catalog->activeCategory($categorySlug);

        $matches = fn (): Builder => Product::query()
            ->active()
            ->inCategories($category === null ? null : $this->catalog->categoryIds($category))
            ->tap(fn (Builder $query) => $this->matcher->apply($query, $term));

        $products = $matches()
            ->tap(fn (Builder $query) => $this->matcher->orderByRelevance($query, $term))
            ->orderByDesc('products.id')
            ->with(['category:id,name', 'mainImage', 'activeVariants', 'variantHeads.values'])
            ->limit(self::SUGGESTION_LIMIT)
            ->get();

        $cards = ProductCardResource::collection($products)->resolve();

        return [
            'items' => $products->values()->map(fn (Product $product, int $index) => [
                'name' => $product->name,
                'category' => $product->category?->name,
                'thumb' => $cards[$index]['thumb'],
                'price' => $cards[$index]['sale_price'] ?? $cards[$index]['price'],
                'url' => route('products.show', $product->slug, false),
            ])->all(),
            'total' => $matches()->count(),
        ];
    }
}
