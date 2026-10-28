<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Jobs\NotifyAdminOfNewOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Order\PendingOrderCount;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->create(['name' => 'Rafi Admin']);
    actingAsAdmin($this->admin);
});

/**
 * A pending order with one line of `$quantity` units of a variant that has `$stock` left.
 */
function orderForStock(int $stock = 10, int $quantity = 3): Order
{
    $variant = Product::factory()->simple(100000, $stock)->create()->variants()->sole();
    $order = Order::factory()->create();
    OrderItem::factory()->forVariant($variant)->quantity($quantity)->create(['order_id' => $order->id]);

    return $order;
}

describe('show', function () {
    it('shows the order with items, totals, customer, history and the moves it allows', function () {
        $customer = Customer::factory()->create(['delivered_count' => 3, 'cancelled_count' => 1]);
        $order = Order::factory()->withItems(2)->create([
            'customer_id' => $customer->id,
            'utm_campaign' => 'summer_dress',
            'shipping_name' => 'Nusrat Jahan',
        ]);
        Order::factory()->create(['customer_id' => $customer->id]);
        $order->statusHistories()->create(['from_status' => null, 'to_status' => OrderStatus::Pending]);

        $this->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Show', false)
                ->where('order.number', $order->number)
                ->where('order.status.value', 'pending')
                ->where('order.utm_campaign', 'summer_dress')
                ->where('order.customer.name', 'Nusrat Jahan')
                ->where('order.customer_history', ['delivered' => 3, 'cancelled' => 1, 'pending' => 2])
                ->has('order.items', 2)
                ->where('order.history.0.kind', 'placed')
                ->has('order.allowed_next', 2)
                ->where('order.allowed_next.0.value', 'confirmed')
                ->where('order.allowed_next.1.value', 'cancelled'));
    });

    it('returns 404 for a missing order', function () {
        $this->get(route('admin.orders.show', 999))->assertNotFound();
    });
});

describe('transition', function () {
    it('maps every allowed move of the enum to a working action', function (OrderStatus $from, OrderStatus $to) {
        $order = orderForStock();
        $order->update(['status' => $from]);

        $this->post(route('admin.orders.transition', $order), ['to' => $to->value, 'note' => 'Because'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect($order->fresh()->status)->toBe($to);
    })->with(function () {
        foreach (OrderStatus::cases() as $from) {
            foreach ($from->allowedNext() as $to) {
                yield "{$from->value} → {$to->value}" => [$from, $to];
            }
        }
    });

    it('rejects a move the order cannot make', function (OrderStatus $from, OrderStatus $to) {
        $order = orderForStock();
        $order->update(['status' => $from]);

        $this->post(route('admin.orders.transition', $order), ['to' => $to->value, 'note' => 'Because'])
            ->assertSessionHasErrors('to');

        expect($order->fresh()->status)->toBe($from)
            ->and($order->statusHistories()->count())->toBe(0);
    })->with([
        'pending → shipped' => [OrderStatus::Pending, OrderStatus::Shipped],
        'shipped → cancelled' => [OrderStatus::Shipped, OrderStatus::Cancelled],
        'cancelled → pending' => [OrderStatus::Cancelled, OrderStatus::Pending],
        'delivered → pending' => [OrderStatus::Delivered, OrderStatus::Pending],
    ]);

    it('requires a note to cancel or return', function (OrderStatus $from, OrderStatus $to) {
        $order = orderForStock();
        $order->update(['status' => $from]);

        $this->post(route('admin.orders.transition', $order), ['to' => $to->value])->assertSessionHasErrors('note');

        expect($order->fresh()->status)->toBe($from);
    })->with([
        'cancel' => [OrderStatus::Pending, OrderStatus::Cancelled],
        'return' => [OrderStatus::Delivered, OrderStatus::Returned],
    ]);

    it('confirms without a note and records who did it', function () {
        $order = orderForStock();

        $this->post(route('admin.orders.transition', $order), ['to' => 'confirmed'])->assertRedirect();

        $history = $order->statusHistories()->sole();
        expect($history->from_status)->toBe(OrderStatus::Pending)
            ->and($history->to_status)->toBe(OrderStatus::Confirmed)
            ->and($history->changed_by)->toBe($this->admin->id)
            ->and($order->fresh()->confirmed_at)->not->toBeNull();
    });

    it('marks the order paid when it is delivered', function () {
        $order = orderForStock();
        $order->update(['status' => OrderStatus::Shipped]);

        $this->post(route('admin.orders.transition', $order), ['to' => 'delivered'])->assertRedirect();

        expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
    });

    it('puts the stock back and logs the note when cancelling', function () {
        $order = orderForStock(stock: 10, quantity: 3);
        $variantId = $order->items()->sole()->product_variant_id;

        $this->post(route('admin.orders.transition', $order), ['to' => 'cancelled', 'note' => 'Customer asked to cancel'])
            ->assertRedirect();

        expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
            ->and($order->statusHistories()->sole()->note)->toBe('Customer asked to cancel')
            ->and(StockMovement::query()->where('product_variant_id', $variantId)->where('reason', StockMovementReason::OrderCancelled)->sole()->quantity)->toBe(3)
            ->and(ProductVariant::query()->find($variantId)->stock)->toBe(13);
    });

    it('rejects an unknown status', function () {
        $order = orderForStock();

        $this->post(route('admin.orders.transition', $order), ['to' => 'lost'])->assertSessionHasErrors('to');
    });
});

describe('notes', function () {
    it('adds a note to the history without changing the status', function () {
        $order = orderForStock();

        $this->post(route('admin.orders.notes', $order), ['note' => 'Called, will receive after 5pm'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $history = $order->statusHistories()->sole();
        expect($order->fresh()->status)->toBe(OrderStatus::Pending)
            ->and($history->note)->toBe('Called, will receive after 5pm')
            ->and($history->from_status)->toBe($history->to_status)
            ->and($history->changed_by)->toBe($this->admin->id);
    });

    it('rejects an empty or too long note', function (string $note) {
        $order = orderForStock();

        $this->post(route('admin.orders.notes', $order), ['note' => $note])->assertSessionHasErrors('note');

        expect($order->statusHistories()->count())->toBe(0);
    })->with([
        'empty' => '',
        'too long' => str_repeat('a', 501),
    ]);
});

describe('blocking a phone', function () {
    it('blocks and unblocks the customer', function () {
        $customer = Customer::factory()->create();

        $this->post(route('admin.customers.block', $customer))->assertRedirect();
        expect($customer->fresh()->is_blocked)->toBeTrue();

        $this->delete(route('admin.customers.unblock', $customer))->assertRedirect();
        expect($customer->fresh()->is_blocked)->toBeFalse();
    });

    it('stops that phone from placing a new order', function () {
        Queue::fake();
        $variant = Product::factory()->simple(100000, 10)->create()->variants()->sole();
        $customer = Customer::factory()->create(['phone' => '01712345678']);
        $this->post(route('admin.customers.block', $customer));

        $this->post(route('orders.store'), [
            'name' => 'Nusrat Jahan',
            'phone' => '01712345678',
            'address' => 'House 1, Road 2, Dhanmondi, Dhaka',
            'delivery_zone' => 'inside',
            'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
            'idempotency_key' => (string) Str::uuid(),
            'website' => '',
        ])->assertSessionHasErrors('phone');

        expect(Order::query()->count())->toBe(0);
    });

    it('returns 404 for a missing customer', function () {
        $this->post(route('admin.customers.block', 999))->assertNotFound();
    });
});

describe('pending badge cache', function () {
    it('is refreshed after an order is placed', function () {
        Queue::fake();
        $variant = Product::factory()->simple(100000, 10)->create()->variants()->sole();
        expect(app(PendingOrderCount::class)->get())->toBe(0);

        $this->post(route('orders.store'), [
            'name' => 'Nusrat Jahan',
            'phone' => '01712345678',
            'address' => 'House 1, Road 2, Dhanmondi, Dhaka',
            'delivery_zone' => 'inside',
            'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
            'idempotency_key' => (string) Str::uuid(),
            'website' => '',
        ])->assertRedirect();

        expect(app(PendingOrderCount::class)->get())->toBe(1);
        Queue::assertPushed(NotifyAdminOfNewOrder::class);
    });

    it('is refreshed after a status change', function () {
        $order = orderForStock();
        expect(app(PendingOrderCount::class)->get())->toBe(1);

        $this->post(route('admin.orders.transition', $order), ['to' => 'confirmed']);

        expect(app(PendingOrderCount::class)->get())->toBe(0);
    });
});
