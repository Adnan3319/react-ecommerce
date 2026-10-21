<?php

namespace App\Services\Storefront;

use App\Enums\ActiveStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * The one product listing behind the category pages and the search page.
 */
class CatalogService
{
    public const PER_PAGE = 12;

    public function __construct(
        private readonly SearchMatcher $matcher,
        private readonly CatalogFilterCache $filterCache,
    ) {}

    /**
     * The active category with this slug whose parent (if any) is active too.
     */
    public function activeCategory(string $slug): ?Category
    {
        $category = Category::query()->active()->where('slug', $slug)->with('parent')->first();

        if ($category?->parent !== null && $category->parent->status !== ActiveStatus::Active) {
            return null;
        }

        return $category;
    }

    /**
     * One page of products plus the filters to show. Without a category it lists every active category.
     * All variant-level conditions (price, stock, every selected option value) must hold for the
     * SAME variant, so "M + Pink + in stock" never matches an M/Pink that is sold out while an
     * L/Blue is in stock.
     *
     * @return array{products: LengthAwarePaginator<int, Product>, filters: array{available: array<string, mixed>, active: array<string, mixed>}}
     */
    public function listing(?Category $category, ProductFilter $filter): array
    {
        $categoryIds = $category === null ? null : $this->categoryIds($category);
        $available = $this->availableFilters($category, $categoryIds);
        $filter = $filter->restrictedTo($this->knownHeads($available));
        $term = $filter->searchTerm();

        $products = Product::query()
            ->active()
            ->inCategories($categoryIds)
            ->when($term !== null, fn (Builder $query) => $this->matcher->apply($query, $term))
            ->when($filter->hasVariantConditions(), fn (Builder $query) => $query->whereHas(
                'variants',
                fn (Builder $variants) => $this->matchSingleVariant($variants, $filter),
            ))
            ->tap(fn (Builder $query) => $this->applySort($query, $filter->sort, $term))
            ->with(['mainImage', 'activeVariants', 'variantHeads.values'])
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return [
            'products' => $products,
            'filters' => ['available' => $available, 'active' => $filter->toArray()],
        ];
    }

    /**
     * @param  Builder<ProductVariant>  $variants
     */
    private function matchSingleVariant(Builder $variants, ProductFilter $filter): void
    {
        $variants->active();

        if ($filter->inStock) {
            $variants->where('stock', '>', 0);
        }

        if ($filter->minPrice !== null) {
            $variants->whereRaw('COALESCE(sale_price, price) >= ?', [$filter->minPrice]);
        }

        if ($filter->maxPrice !== null) {
            $variants->whereRaw('COALESCE(sale_price, price) <= ?', [$filter->maxPrice]);
        }

        foreach ($filter->heads as $head => $values) {
            $variants->whereExists(fn (QueryBuilder $selection) => $selection
                ->selectRaw('1')
                ->from('variant_selections as vs')
                ->join('variant_values as vv', 'vv.id', '=', 'vs.variant_value_id')
                ->join('variant_heads as vh', 'vh.id', '=', 'vv.variant_head_id')
                ->whereColumn('vs.product_variant_id', 'product_variants.id')
                ->whereRaw('LOWER(vh.name) = ?', [$head])
                ->whereIn(DB::raw('LOWER(vv.value)'), $values));
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, string $sort, ?string $term): void
    {
        if ($sort === ProductFilter::SORT_BEST_MATCH && $term !== null) {
            $this->matcher->orderByRelevance($query, $term);
        } elseif (in_array($sort, [ProductFilter::SORT_PRICE_ASC, ProductFilter::SORT_PRICE_DESC], true)) {
            $direction = $sort === ProductFilter::SORT_PRICE_ASC ? 'ASC' : 'DESC';

            $query->orderByRaw(
                "(SELECT MIN(COALESCE(v.sale_price, v.price)) FROM product_variants v WHERE v.product_id = products.id AND v.status = ? AND v.deleted_at IS NULL) {$direction}",
                [ActiveStatus::Active->value],
            );
        }

        $query->orderByDesc('products.id');
    }

    /**
     * The category and its active sub-categories.
     *
     * @return array<int, int>
     */
    public function categoryIds(Category $category): array
    {
        return Category::query()
            ->active()
            ->where('parent_id', $category->id)
            ->pluck('id')
            ->prepend($category->id)
            ->all();
    }

    /**
     * Price bounds and the option heads present in the category, cached for ten minutes.
     *
     * @param  array<int, int>|null  $categoryIds
     * @return array{price: array{min: ?string, max: ?string}, heads: list<array{key: string, name: string, values: list<array{key: string, value: string, count: int}>}>}
     */
    private function availableFilters(?Category $category, ?array $categoryIds): array
    {
        return $this->filterCache->remember(
            $category?->id,
            fn () => ['price' => $this->priceBounds($categoryIds), 'heads' => $this->heads($categoryIds)],
        );
    }

    /**
     * @param  array<int, int>|null  $categoryIds
     * @return array{min: ?string, max: ?string}
     */
    private function priceBounds(?array $categoryIds): array
    {
        $bounds = ProductVariant::query()
            ->active()
            ->whereHas('product', fn (Builder $product) => $product->active()->inCategories($categoryIds))
            ->selectRaw('MIN(COALESCE(sale_price, price)) as low, MAX(COALESCE(sale_price, price)) as high')
            ->toBase()
            ->first();

        return [
            'min' => $bounds?->low === null ? null : Money::toTaka((int) $bounds->low),
            'max' => $bounds?->high === null ? null : Money::toTaka((int) $bounds->high),
        ];
    }

    /**
     * Heads merged case-insensitively, each value with the number of products that have it.
     *
     * @param  array<int, int>|null  $categoryIds
     * @return list<array{key: string, name: string, values: list<array{key: string, value: string, count: int}>}>
     */
    private function heads(?array $categoryIds): array
    {
        $active = ActiveStatus::Active->value;

        $rows = DB::table('variant_selections as vs')
            ->join('variant_values as vv', 'vv.id', '=', 'vs.variant_value_id')
            ->join('variant_heads as vh', 'vh.id', '=', 'vv.variant_head_id')
            ->join('product_variants as pv', 'pv.id', '=', 'vs.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->whereNull('pv.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('pv.status', $active)
            ->where('p.status', $active)
            ->whereIn('p.category_id', $categoryIds ?? Category::query()->active()->select('id'))
            ->groupByRaw('LOWER(vh.name), LOWER(vv.value)')
            ->selectRaw('LOWER(vh.name) as head_key, MIN(vh.name) as head_name, MIN(vh.position) as head_position, LOWER(vv.value) as value_key, MIN(vv.value) as value_name, MIN(vv.position) as value_position, COUNT(DISTINCT p.id) as products')
            ->orderBy('head_position')
            ->orderBy('head_key')
            ->orderBy('value_position')
            ->orderBy('value_key')
            ->get();

        return $rows->groupBy('head_key')->map(fn ($values, $key) => [
            'key' => (string) $key,
            'name' => $values->first()->head_name,
            'values' => $values->map(fn ($row) => [
                'key' => $row->value_key,
                'value' => $row->value_name,
                'count' => (int) $row->products,
            ])->values()->all(),
        ])->values()->all();
    }

    /**
     * @param  array{heads: list<array{key: string, values: list<array{key: string}>}>}  $available
     * @return array<string, list<string>>
     */
    private function knownHeads(array $available): array
    {
        $known = [];

        foreach ($available['heads'] as $head) {
            $known[$head['key']] = array_column($head['values'], 'key');
        }

        return $known;
    }
}
