<?php

namespace App\Http\Resources\Admin\Product;

use App\Models\Product;
use App\Services\Media\FileUploadService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $range = $this->price_range;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'thumb_url' => app(FileUploadService::class)->variantUrl($this->mainImage?->path, 'thumb'),
            'category' => $this->category?->parent
                ? "{$this->category->parent->name} › {$this->category->name}"
                : $this->category?->name,
            'price' => $this->priceLabel($range['min'], $range['max']),
            'total_stock' => $this->total_stock,
            'stock_state' => $this->stockState(),
            'status' => $this->status->value,
            'is_featured' => $this->is_featured,
        ];
    }

    private function priceLabel(?int $min, ?int $max): ?string
    {
        if ($min === null) {
            return null;
        }

        return $min === $max ? Money::format($min) : Money::format($min).' – '.Money::format($max);
    }

    private function stockState(): string
    {
        return match (true) {
            $this->total_stock === 0 => 'out',
            $this->total_stock <= (int) config('shop.low_stock_threshold') => 'low',
            default => 'in',
        };
    }
}
