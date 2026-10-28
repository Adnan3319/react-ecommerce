<?php

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\InvalidOrderTransition;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Order\CheckoutData;
use App\Services\Order\CheckoutService;
use App\Services\Order\OrderStatusService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->statuses = app(OrderStatusService::class);
    $this->variant = Product::factory()->simple(100000, 10)->create()->variants()->sole();
});

/**
 * A real order of 3 units placed through the checkout (stock 10 → 7).
 */
function statusOrder(ProductVariant $variant): Order
{
    return app(CheckoutService::class)->place(new CheckoutData(
        items: [['variant_id' => $variant->id, 'quantity' => 3]],
        name: 'Rahim',
        phone: '01712345678',
        address: 'Dhaka',
        deliveryZone: 'inside',
        idempotencyKey: (string) Str::uuid(),
        source: OrderSource::BuyNow,
    ));
}

/**
 * @return array<int, int> stock movement quantities of the variant for one reason
 */
function statusMovements(ProductVariant $variant, StockMovementReason $reason): array
{
    return StockMovement::where('product_variant_id', $variant->id)->where('reason', $reason)->pluck('quantity')->all();
}

describe('transition', function () {
    it('confirms and ships an order and stamps the time', function () {
        $order = statusOrder($this->variant);

        $confirmed = $this->statuses->transition($order, OrderStatus::Confirmed);
        $this->statuses->transition($order, OrderStatus::Processing);
        $shipped = $this->statuses->transition($order, OrderStatus::Shipped);

        expect($confirmed->status)->toBe(OrderStatus::Confirmed)
            ->and($confirmed->confirmed_at)->not->toBeNull()
            ->and($shipped->status)->toBe(OrderStatus::Shipped)
            ->and($shipped->shipped_at)->not->toBeNull()
            ->and($shipped->payment_status)->toBe(PaymentStatus::Unpaid);
    });

    it('writes a history row with the note and the admin', function () {
        $order = statusOrder($this->variant);
        $admin = User::factory()->create();

        $this->statuses->transition($order, OrderStatus::Confirmed, 'Called the customer', $admin);

        $history = $order->statusHistories()->get()->last();

        expect($history->from_status)->toBe(OrderStatus::Pending)
            ->and($history->to_status)->toBe(OrderStatus::Confirmed)
            ->and($history->note)->toBe('Called the customer')
            ->and($history->changed_by)->toBe($admin->id)
            ->and($order->statusHistories()->count())->toBe(2);
    });

    it('marks a delivered order paid and counts it for the customer', function () {
        $order = statusOrder($this->variant);
        foreach ([OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped] as $status) {
            $this->statuses->transition($order, $status);
        }

        $delivered = $this->statuses->transition($order, OrderStatus::Delivered);

        expect($delivered->payment_status)->toBe(PaymentStatus::Paid)
            ->and($delivered->delivered_at)->not->toBeNull()
            ->and(Customer::sole()->delivered_count)->toBe(1)
            ->and($this->variant->fresh()->stock)->toBe(7);
    });
});

describe('cancelling', function () {
    it('restores stock with order_cancelled and counts the cancellation', function () {
        $order = statusOrder($this->variant);

        $cancelled = $this->statuses->transition($order, OrderStatus::Cancelled, 'Customer asked');

        expect($cancelled->cancelled_at)->not->toBeNull()
            ->and($this->variant->fresh()->stock)->toBe(10)
            ->and(statusMovements($this->variant, StockMovementReason::OrderCancelled))->toBe([3])
            ->and(StockMovement::where('reason', StockMovementReason::OrderCancelled)->first()->reference_id)->toBe($order->id)
            ->and(Customer::sole()->cancelled_count)->toBe(1)
            ->and(StockMovement::where('product_variant_id', $this->variant->id)->sum('quantity'))->toBe(10);
    });

    it('can cancel from pending, confirmed and processing', function (array $path) {
        $order = statusOrder($this->variant);
        foreach ($path as $status) {
            $this->statuses->transition($order, $status);
        }

        $this->statuses->transition($order, OrderStatus::Cancelled);

        expect($this->variant->fresh()->stock)->toBe(10);
    })->with([
        'pending' => [[]],
        'confirmed' => [[OrderStatus::Confirmed]],
        'processing' => [[OrderStatus::Confirmed, OrderStatus::Processing]],
    ]);

    it('restores stock of a soft-deleted variant', function () {
        $order = statusOrder($this->variant);
        $this->variant->delete();

        $this->statuses->transition($order, OrderStatus::Cancelled);

        expect(ProductVariant::withTrashed()->find($this->variant->id)->stock)->toBe(10);
    });
});

describe('returning', function () {
    it('restores stock with order_returned and keeps the payment paid after delivery', function () {
        $order = statusOrder($this->variant);
        foreach ([OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered] as $status) {
            $this->statuses->transition($order, $status);
        }

        $returned = $this->statuses->transition($order, OrderStatus::Returned);

        expect($returned->payment_status)->toBe(PaymentStatus::Paid)
            ->and($this->variant->fresh()->stock)->toBe(10)
            ->and(statusMovements($this->variant, StockMovementReason::OrderReturned))->toBe([3]);
    });

    it('restores stock and keeps the payment unpaid when the customer refused the shipment', function () {
        $order = statusOrder($this->variant);
        foreach ([OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped] as $status) {
            $this->statuses->transition($order, $status);
        }

        $returned = $this->statuses->transition($order, OrderStatus::Returned);

        expect($returned->payment_status)->toBe(PaymentStatus::Unpaid)
            ->and($this->variant->fresh()->stock)->toBe(10)
            ->and(statusMovements($this->variant, StockMovementReason::OrderReturned))->toBe([3])
            ->and(Customer::sole()->delivered_count)->toBe(0);
    });
});

describe('forbidden transitions', function () {
    it('throws and changes nothing', function (array $path, OrderStatus $forbidden) {
        $order = statusOrder($this->variant);
        foreach ($path as $status) {
            $this->statuses->transition($order, $status);
        }
        $before = Order::find($order->id);
        $historyCount = $before->statusHistories()->count();
        $stock = $this->variant->fresh()->stock;
        $movements = StockMovement::count();

        try {
            $this->statuses->transition($order, $forbidden);
            $thrown = false;
        } catch (InvalidOrderTransition) {
            $thrown = true;
        }

        $after = Order::find($order->id);

        expect($thrown)->toBeTrue()
            ->and($after->status)->toBe($before->status)
            ->and($after->updated_at->equalTo($before->updated_at))->toBeTrue()
            ->and($after->statusHistories()->count())->toBe($historyCount)
            ->and($this->variant->fresh()->stock)->toBe($stock)
            ->and(StockMovement::count())->toBe($movements);
    })->with([
        'pending → delivered' => [[], OrderStatus::Delivered],
        'pending → returned' => [[], OrderStatus::Returned],
        'shipped → cancelled' => [[OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped], OrderStatus::Cancelled],
        'cancelled → confirmed' => [[OrderStatus::Cancelled], OrderStatus::Confirmed],
        'returned → delivered' => [[OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Returned], OrderStatus::Delivered],
    ]);

    it('cannot cancel twice, so stock is restored only once', function () {
        $order = statusOrder($this->variant);
        $this->statuses->transition($order, OrderStatus::Cancelled);

        expect(fn () => $this->statuses->transition($order, OrderStatus::Cancelled))->toThrow(InvalidOrderTransition::class)
            ->and($this->variant->fresh()->stock)->toBe(10)
            ->and(Customer::sole()->cancelled_count)->toBe(1);
    });

    it('works from the stored status, not from a stale model', function () {
        $order = statusOrder($this->variant);
        $stale = Order::find($order->id);
        $this->statuses->transition($order, OrderStatus::Cancelled);

        $this->statuses->transition($stale, OrderStatus::Confirmed);
    })->throws(InvalidOrderTransition::class);
});

describe('addNote', function () {
    it('adds a history row without changing the status', function () {
        $order = statusOrder($this->variant);
        $admin = User::factory()->create();

        $this->statuses->addNote($order, 'Customer will pay in cash at the door', $admin);

        $note = $order->statusHistories()->get()->last();

        expect($note->note)->toBe('Customer will pay in cash at the door')
            ->and($note->from_status)->toBe(OrderStatus::Pending)
            ->and($note->to_status)->toBe(OrderStatus::Pending)
            ->and($note->changed_by)->toBe($admin->id)
            ->and($order->fresh()->status)->toBe(OrderStatus::Pending);
    });
});
