<?php

namespace App\Http\Resources\Admin\Product;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\VariantHead;
use App\Models\VariantValue;
use App\Services\Media\FileUploadService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects images, variantHeads.values and variants.variantValues to be loaded.
 *
 * @mixin Product
 */
class ProductFormResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $uploads = app(FileUploadService::class);
        $default = $this->variants->firstWhere('combination_key', ProductVariant::DEFAULT_COMBINATION);
        $matrix = $this->variants->reject(fn (ProductVariant $variant) => $variant->combination_key === ProductVariant::DEFAULT_COMBINATION);

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'status' => $this->status->value,
            'is_featured' => $this->is_featured,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'images' => $this->images->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'url' => $uploads->url($image->path),
                'thumb_url' => $uploads->variantUrl($image->path, 'thumb'),
                'is_main' => $image->is_main,
                'sort_order' => $image->sort_order,
            ])->values(),
            'has_variants' => $this->variantHeads->isNotEmpty(),
            'sku' => $default?->sku,
            'price' => $this->taka($default?->price),
            'sale_price' => $this->taka($default?->sale_price),
            'stock' => $default?->stock ?? 0,
            'heads' => $this->variantHeads->map(fn (VariantHead $head) => [
                'id' => $head->id,
                'name' => $head->name,
                'values' => $head->values->map(fn (VariantValue $value) => ['id' => $value->id, 'value' => $value->value])->values(),
            ])->values(),
            'variants' => $matrix->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'values' => $this->valueTexts($variant),
                'sku' => $variant->sku,
                'price' => $this->taka($variant->price),
                'sale_price' => $this->taka($variant->sale_price),
                'stock' => $variant->stock,
                'image_ref' => $variant->product_image_id === null ? null : (string) $variant->product_image_id,
                'status' => $variant->status->value,
            ])->values(),
        ];
    }

    /**
     * Value texts of a variant in head order.
     *
     * @return list<string>
     */
    private function valueTexts(ProductVariant $variant): array
    {
        return $this->variantHeads
            ->map(fn (VariantHead $head) => $variant->variantValues->firstWhere('variant_head_id', $head->id)?->value)
            ->filter()
            ->values()
            ->all();
    }

    private function taka(?int $minor): ?string
    {
        return $minor === null ? null : Money::toTaka($minor);
    }
}
