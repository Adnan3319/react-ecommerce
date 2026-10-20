<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSlugRedirect;
use App\Models\ProductVariant;
use App\Models\VariantHead;
use App\Services\Media\FileUploadService;
use App\Support\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Everything the storefront product page shows, as one plain array cached per product
 * (cleared by ProductCache on every product or stock write). Prices are minor units.
 */
class ProductPageService
{
    private const IMAGE_PRESET = 'product';

    private const SEO_DESCRIPTION_LENGTH = 160;

    public function __construct(private readonly FileUploadService $uploads) {}

    /**
     * Id of the live, active product with this slug (soft-deleted and inactive products are not found).
     */
    public function activeIdBySlug(string $slug): ?int
    {
        return Product::query()->active()->where('slug', $slug)->value('id');
    }

    /**
     * Current slug of the active product that once had `$oldSlug`, for the 301.
     */
    public function redirectTarget(string $oldSlug): ?string
    {
        $redirect = ProductSlugRedirect::query()
            ->where('old_slug', $oldSlug)
            ->with(['product' => fn ($query) => $query->active()->select('id', 'slug', 'status')])
            ->first();

        return $redirect?->product?->slug;
    }

    /**
     * @return array{product: array<string, mixed>, category: array<string, mixed>, seo: array<string, mixed>}
     */
    public function page(int $productId): array
    {
        return Cache::rememberForever(ProductCache::pageKey($productId), fn () => $this->build($productId));
    }

    /**
     * @return array{product: array<string, mixed>, category: array<string, mixed>, seo: array<string, mixed>}
     */
    private function build(int $productId): array
    {
        $product = Product::query()
            ->active()
            ->with(['category.parent', 'images', 'variantHeads.values', 'activeVariants.variantValues'])
            ->findOrFail($productId);

        $images = $product->images->map(fn (ProductImage $image) => $this->image($image))->values()->all();
        $variants = $product->activeVariants->map(fn (ProductVariant $variant) => $this->variant($variant, $product->variantHeads))->values()->all();

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'has_variants' => $product->variantHeads->isNotEmpty(),
                'images' => $images,
                'heads' => $product->variantHeads->map(fn (VariantHead $head) => [
                    'id' => $head->id,
                    'name' => $head->name,
                    'values' => $head->values->map(fn ($value) => ['id' => $value->id, 'value' => $value->value])->values()->all(),
                ])->values()->all(),
                'variants' => $variants,
            ],
            'category' => $this->category($product),
            'seo' => $this->seo($product, $images, $variants),
        ];
    }

    /**
     * @return array{id: int, url: ?string, thumb_url: ?string, srcset: ?string}
     */
    private function image(ProductImage $image): array
    {
        return [
            'id' => $image->id,
            'url' => $this->uploads->url($image->path),
            'thumb_url' => $this->uploads->variantUrl($image->path, 'thumb'),
            'srcset' => $this->uploads->srcset($image->path, self::IMAGE_PRESET),
        ];
    }

    /**
     * @param  iterable<int, VariantHead>  $heads
     * @return array<string, mixed>
     */
    private function variant(ProductVariant $variant, iterable $heads): array
    {
        $selected = $variant->variantValues->pluck('id')->all();
        $valueIds = [];

        foreach ($heads as $head) {
            $valueIds[] = $head->values->pluck('id')->intersect($selected)->first();
        }

        return [
            'id' => $variant->id,
            'value_ids' => array_values(array_filter($valueIds)),
            'label' => $variant->combination_key === ProductVariant::DEFAULT_COMBINATION ? null : $variant->label,
            'sku' => $variant->sku,
            'price' => $variant->price,
            'sale_price' => $variant->sale_price,
            'effective_price' => $variant->effective_price,
            'discount_percent' => $variant->discount_percent,
            'stock' => $variant->stock,
            'stock_state' => $this->stockState($variant->stock),
            'image_id' => $variant->product_image_id,
        ];
    }

    private function stockState(int $stock): string
    {
        return match (true) {
            $stock <= 0 => 'out',
            $stock <= (int) config('shop.low_stock_threshold') => 'low',
            default => 'in',
        };
    }

    /**
     * Category path for the breadcrumb (root first); the storefront builds the links.
     *
     * @return array{trail: list<array{slug: string, label: string}>}
     */
    private function category(Product $product): array
    {
        $category = $product->category;
        $trail = array_filter([
            $category->parent ? ['slug' => $category->parent->slug, 'label' => $category->parent->name] : null,
            ['slug' => $category->slug, 'label' => $category->name],
        ]);

        return ['trail' => array_values($trail)];
    }

    /**
     * @param  list<array<string, mixed>>  $images
     * @param  list<array<string, mixed>>  $variants
     * @return array<string, mixed>
     */
    private function seo(Product $product, array $images, array $variants): array
    {
        $canonical = route('products.show', $product->slug);
        $image = $this->absolute($images[0]['url'] ?? null);
        $description = $product->meta_description
            ?: $product->short_description
            ?: Str::limit(trim(strip_tags((string) $product->description)), self::SEO_DESCRIPTION_LENGTH, '');

        return [
            'title' => $product->meta_title ?: $product->name.' | '.config('app.name'),
            'description' => $description !== '' ? $description : null,
            'canonical' => $canonical,
            'noindex' => false,
            'image' => $image,
            'jsonLd' => [
                $this->productJsonLd($product, $canonical, array_filter(array_map(fn (array $item) => $this->absolute($item['url']), $images)), $variants, $description),
                $this->breadcrumbJsonLd($product, $canonical),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $images
     * @param  list<array<string, mixed>>  $variants
     * @return array<string, mixed>
     */
    private function productJsonLd(Product $product, string $canonical, array $images, array $variants, string $description): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'url' => $canonical,
            'image' => array_values($images),
        ];

        if ($description !== '') {
            $schema['description'] = $description;
        }

        if ($variants === []) {
            return $schema;
        }

        $prices = array_column($variants, 'effective_price');
        $availability = collect($variants)->contains(fn (array $variant) => $variant['stock'] > 0)
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';
        $schema['sku'] = $variants[0]['sku'];
        $offer = ['priceCurrency' => 'BDT', 'availability' => $availability, 'url' => $canonical];

        $schema['offers'] = min($prices) === max($prices)
            ? ['@type' => 'Offer', 'price' => Money::toTaka(min($prices)), ...$offer]
            : [
                '@type' => 'AggregateOffer',
                'lowPrice' => Money::toTaka(min($prices)),
                'highPrice' => Money::toTaka(max($prices)),
                'offerCount' => count($variants),
                ...$offer,
            ];

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function breadcrumbJsonLd(Product $product, string $canonical): array
    {
        $crumbs = [['name' => 'Home', 'item' => route('home')]];

        foreach (array_filter([$product->category->parent, $product->category]) as $category) {
            $crumbs[] = ['name' => $category->name, 'item' => route('search', ['category' => $category->slug])];
        }

        $crumbs[] = ['name' => $product->name, 'item' => $canonical];

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

    private function absolute(?string $url): ?string
    {
        if ($url === null || str_starts_with($url, 'http')) {
            return $url;
        }

        return url($url);
    }
}
