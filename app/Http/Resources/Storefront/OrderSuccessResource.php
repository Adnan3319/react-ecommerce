<?php

namespace App\Http\Resources\Storefront;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Media\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * What the thank-you page shows. Amounts are minor units.
 *
 * @mixin Order
 */
class OrderSuccessResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $uploads = app(FileUploadService::class);

        return [
            'number' => $this->number,
            'first_name' => Str::before(trim($this->shipping_name), ' '),
            'phone' => $this->shipping_phone,
            'subtotal' => $this->subtotal,
            'delivery_charge' => $this->delivery_charge,
            'delivery_zone_label' => $this->delivery_zone_label,
            'total' => $this->total,
            'items' => $this->items->map(fn (OrderItem $item) => [
                'name' => $item->product_name,
                'variant_label' => $item->variant_label,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
                'image_url' => $uploads->variantUrl($item->image_path, 'thumb'),
            ])->values()->all(),
        ];
    }
}
