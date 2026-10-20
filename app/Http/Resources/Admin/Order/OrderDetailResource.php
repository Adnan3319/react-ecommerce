<?php

namespace App\Http\Resources\Admin\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Services\Media\FileUploadService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects `customer`, `items` and `statusHistories.changedBy` to be loaded (OrderQueryService::detail).
 *
 * @mixin Order
 */
class OrderDetailResource extends JsonResource
{
    private const KIND_PLACED = 'placed';

    private const KIND_NOTE = 'note';

    private const KIND_CHANGE = 'change';

    /**
     * @param  array{delivered: int, cancelled: int, pending: int}  $customerHistory
     */
    public function __construct(Order $order, private readonly array $customerHistory)
    {
        parent::__construct($order);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $uploads = app(FileUploadService::class);

        return [
            'id' => $this->id,
            'number' => $this->number,
            'placed_at' => $this->created_at->toIso8601String(),
            'status' => self::statusBadge($this->status),
            'payment_status' => $this->payment_status->value,
            'source' => ['value' => $this->source->value, 'label' => $this->source->label()],
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'fbclid' => $this->fbclid,
            'customer_note' => $this->customer_note,
            'subtotal' => Money::format($this->subtotal),
            'delivery_charge' => Money::format($this->delivery_charge),
            'delivery_zone_label' => $this->delivery_zone_label,
            'total' => Money::format($this->total),
            'items' => $this->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'name' => $item->product_name,
                'variant_label' => $item->variant_label,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => Money::format($item->unit_price),
                'line_total' => Money::format($item->line_total),
                'image_url' => $uploads->variantUrl($item->image_path, 'thumb'),
            ])->values()->all(),
            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->shipping_name,
                'phone' => $this->shipping_phone,
                'address' => $this->shipping_address,
                'is_blocked' => $this->customer->is_blocked,
            ],
            'customer_history' => $this->customerHistory,
            'history' => $this->statusHistories->map(fn (OrderStatusHistory $row) => [
                'id' => $row->id,
                'kind' => $this->kind($row),
                'from' => $row->from_status?->label(),
                'to' => $row->to_status->label(),
                'note' => $row->note,
                'by' => $row->changedBy?->name,
                'at' => $row->created_at->toIso8601String(),
            ])->values()->all(),
            'allowed_next' => array_map(self::statusBadge(...), $this->status->allowedNext()),
        ];
    }

    /**
     * @return array{value: string, label: string, color: string}
     */
    private static function statusBadge(OrderStatus $status): array
    {
        return ['value' => $status->value, 'label' => $status->label(), 'color' => $status->color()];
    }

    private function kind(OrderStatusHistory $row): string
    {
        return match (true) {
            $row->from_status === null => self::KIND_PLACED,
            $row->from_status === $row->to_status => self::KIND_NOTE,
            default => self::KIND_CHANGE,
        };
    }
}
