<?php

namespace App\Http\Resources\Admin\Order;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'placed_at' => $this->created_at->toIso8601String(),
            'customer_name' => $this->shipping_name,
            'customer_phone' => $this->shipping_phone,
            'delivery_zone_label' => $this->delivery_zone_label,
            'items_count' => $this->items_count,
            'total' => Money::format($this->total),
            'source' => ['value' => $this->source->value, 'label' => $this->source->label()],
            'status' => ['value' => $this->status->value, 'label' => $this->status->label(), 'color' => $this->status->color()],
        ];
    }
}
