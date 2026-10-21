<?php

namespace App\Http\Resources\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Services\Storefront\ProductFilter;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The Inertia props of the shared listing page, identical for `/categories/{slug}` and `/search`.
 */
final class ListingProps
{
    private const SORT_LABELS = [
        ProductFilter::SORT_NEWEST => 'Newest',
        ProductFilter::SORT_PRICE_ASC => 'Price: low to high',
        ProductFilter::SORT_PRICE_DESC => 'Price: high to low',
    ];

    /**
     * @param  array{products: LengthAwarePaginator<int, Product>, filters: array<string, mixed>}  $page
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    public static function from(?Category $category, array $page, array $seo): array
    {
        return [
            'category' => $category === null ? null : [
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent' => $category->parent === null ? null : [
                    'name' => $category->parent->name,
                    'slug' => $category->parent->slug,
                ],
            ],
            'products' => ProductCardResource::collection($page['products']),
            'filters' => $page['filters'],
            'sortOptions' => array_map(
                fn (string $value, string $label) => ['value' => $value, 'label' => $label],
                array_keys(self::SORT_LABELS),
                self::SORT_LABELS,
            ),
            'seo' => $seo,
        ];
    }
}
