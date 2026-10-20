<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\InvalidOrderTransition;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Product\StockService;
use Illuminate\Support\Facades\DB;

/**
 * Moves an order through its statuses (doc/order/README.md §4): validates the move, applies its side
 * effects and writes the history row, all in one transaction with the order row locked.
 */
class OrderStatusService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly PendingOrderCount $pendingOrders,
    ) {}

    /**
     * @throws InvalidOrderTransition when the order cannot move to `$to`; nothing is changed then
     */
    public function transition(Order $order, OrderStatus $to, ?string $note = null, ?User $by = null): Order
    {
        $moved = DB::transaction(function () use ($order, $to, $note, $by): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw InvalidOrderTransition::between($from, $to);
            }

            $locked->forceFill(['status' => $to, ...$this->timestamps($to)])->save();

            if ($to->restoresStock()) {
                $this->restoreStock($locked, $to);
            }

            $this->updateCustomerCounters($locked, $to);

            $locked->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
                'changed_by' => $by?->id,
            ]);

            return $locked;
        });

        $this->pendingOrders->forget();

        return $moved;
    }

    /**
     * A history entry without a status change (the status is repeated in from and to).
     */
    public function addNote(Order $order, string $note, User $by): void
    {
        $order->statusHistories()->create([
            'from_status' => $order->status,
            'to_status' => $order->status,
            'note' => $note,
            'changed_by' => $by->id,
        ]);
    }

    /**
     * Columns the new status sets on the order.
     *
     * @return array<string, mixed>
     */
    private function timestamps(OrderStatus $to): array
    {
        return match ($to) {
            OrderStatus::Confirmed => ['confirmed_at' => now()],
            OrderStatus::Shipped => ['shipped_at' => now()],
            OrderStatus::Delivered => ['delivered_at' => now(), 'payment_status' => PaymentStatus::Paid],
            OrderStatus::Cancelled => ['cancelled_at' => now()],
            default => [],
        };
    }

    private function restoreStock(Order $order, OrderStatus $to): void
    {
        $reason = $to === OrderStatus::Cancelled ? StockMovementReason::OrderCancelled : StockMovementReason::OrderReturned;
        $quantities = $order->items()->orderBy('product_variant_id')->pluck('quantity', 'product_variant_id');

        // Same id order as placing an order, so concurrent orders cannot deadlock.
        foreach ($quantities as $variantId => $quantity) {
            $this->stock->restore(ProductVariant::withTrashed()->findOrFail($variantId), $quantity, $reason, $order);
        }
    }

    private function updateCustomerCounters(Order $order, OrderStatus $to): void
    {
        $column = match ($to) {
            OrderStatus::Delivered => 'delivered_count',
            OrderStatus::Cancelled => 'cancelled_count',
            default => null,
        };

        if ($column !== null) {
            Customer::withTrashed()->whereKey($order->customer_id)->increment($column);
        }
    }
}
