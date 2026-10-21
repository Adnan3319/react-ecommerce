<?php

namespace App\Http\Resources\Storefront;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantHead;
use App\Services\Media\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * The one product card shape used by every storefront list. Needs `mainImage`, `activeVariants`
 * and `variantHeads.values` loaded. Prices are minor units, taken from the cheapest in-stock
 * variant (or the cheapest variant when everything is sold out).
 *
 * @mixin Product
 */
class ProductCardResource extends JsonResource
{
    private const IMAGE_PRESET = 'product';

    private const NEW_FOR_DAYS = 14;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $uploads = app(FileUploadService::class);
        $path = $this->mainImage?->path;
        $variants = $this->activeVariants;
        $inStock = $variants->filter(fn (ProductVariant $variant) => $variant->stock > 0);
        $shown = ($inStock->isNotEmpty() ? $inStock : $variants)->sortBy('effective_price')->first();
        $hasVariants = $this->variantHeads->isNotEmpty();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'thumb' => $uploads->variantUrl($path, 'thumb'),
            'srcset' => $uploads->srcset($path, self::IMAGE_PRESET),
            'price' => $shown?->price,
            'sale_price' => $shown?->sale_price,
            'discount_percent' => $shown?->discount_percent,
            'options_summary' => $this->optionsSummary(),
            'in_stock' => $inStock->isNotEmpty(),
            'is_new' => $this->created_at?->greaterThanOrEqualTo(now()->subDays(self::NEW_FOR_DAYS)) ?? false,
            'has_variants' => $hasVariants,
            'default_variant_id' => $hasVariants ? null : $variants->first()?->id,
        ];
    }

    /**
     * "3 sizes · 2 colors", or null for a simple product.
     */
    private function optionsSummary(): ?string
    {
        $parts = $this->variantHeads
            ->map(fn (VariantHead $head) => $head->values->count().' '.Str::plural(Str::lower($head->name), $head->values->count()))
            ->all();

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
