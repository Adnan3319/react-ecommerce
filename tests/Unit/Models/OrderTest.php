<?php

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

describe('casts', function () {
    it('casts statuses, payment and source to enums and money to integers', function () {
        $order = Order::factory()->buyNow()->create()->fresh();

        expect($order->status)->toBe(OrderStatus::Pending)
            ->and($order->payment_method)->toBe(PaymentMethod::Cod)
            ->and($order->payment_status)->toBe(PaymentStatus::Unpaid)
            ->and($order->source)->toBe(OrderSource::BuyNow)
            ->and($order->total)->toBeInt()->toBe(157000);
    });
});

describe('relations', function () {
    it('belongs to a customer and has items and status histories in order', function () {
        $order = Order::factory()->withItems(2)->create();
        OrderStatusHistory::create(['order_id' => $order->id, 'from_status' => null, 'to_status' => OrderStatus::Pending]);
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => OrderStatus::Pending,
            'to_status' => OrderStatus::Confirmed,
            'changed_by' => User::factory()->create()->id,
        ]);

        $histories = $order->statusHistories;

        expect($order->customer)->toBeInstanceOf(Customer::class)
            ->and($order->items)->toHaveCount(2)
            ->and($histories->pluck('to_status')->all())->toBe([OrderStatus::Pending, OrderStatus::Confirmed])
            ->and($histories->first()->from_status)->toBeNull()
            ->and($histories->last()->changedBy)->toBeInstanceOf(User::class);
    });

    it('keeps the customer reachable after the customer was soft deleted', function () {
        $order = Order::factory()->create();
        $order->customer->delete();

        expect($order->fresh()->customer)->not->toBeNull();
    });
});

describe('withItems factory', function () {
    it('makes the totals match the lines', function () {
        $order = Order::factory()->withItems(2)->create()->fresh();

        expect($order->subtotal)->toBe((int) $order->items->sum('line_total'))
            ->and($order->total)->toBe($order->subtotal + $order->delivery_charge);
    });
});

describe('status scope', function () {
    it('filters by status and ignores null', function () {
        Order::factory()->pending()->create();
        Order::factory()->shipped()->create();

        expect(Order::status(OrderStatus::Shipped)->count())->toBe(1)
            ->and(Order::status(null)->count())->toBe(2);
    });
});

describe('search scope', function () {
    it('matches the number, the name and the phone', function () {
        $order = Order::factory()->create([
            'number' => 'CK-260101-0042',
            'shipping_name' => 'Rahim Uddin',
            'shipping_phone' => '01712345678',
        ]);
        Order::factory()->create();

        expect(Order::search('0042')->pluck('id')->all())->toBe([$order->id])
            ->and(Order::search('rahim')->pluck('id')->all())->toBe([$order->id])
            ->and(Order::search('1234567')->pluck('id')->all())->toBe([$order->id]);
    });

    it('treats LIKE wildcards in the term literally', function () {
        Order::factory()->create(['shipping_name' => '100% Real']);
        Order::factory()->create(['shipping_name' => 'Plain']);

        expect(Order::search('%')->count())->toBe(1)
            ->and(Order::search('_')->count())->toBe(0);
    });

    it('returns everything for an empty term', function () {
        Order::factory()->count(2)->create();

        expect(Order::search('')->count())->toBe(2)->and(Order::search(null)->count())->toBe(2);
    });
});

describe('placedBetween scope', function () {
    it('includes the whole first and last day', function () {
        $before = Order::factory()->create(['created_at' => '2026-03-04 23:59:59']);
        $first = Order::factory()->create(['created_at' => '2026-03-05 00:00:00']);
        $last = Order::factory()->create(['created_at' => '2026-03-06 23:59:59']);
        $after = Order::factory()->create(['created_at' => '2026-03-07 00:00:00']);

        $ids = Order::placedBetween(Carbon::parse('2026-03-05'), Carbon::parse('2026-03-06'))->pluck('id')->all();

        expect($ids)->toEqualCanonicalizing([$first->id, $last->id])
            ->and($ids)->not->toContain($before->id, $after->id);
    });

    it('accepts an open end on either side', function () {
        Order::factory()->create(['created_at' => '2026-03-01 12:00:00']);
        Order::factory()->create(['created_at' => '2026-03-09 12:00:00']);

        expect(Order::placedBetween(Carbon::parse('2026-03-05'), null)->count())->toBe(1)
            ->and(Order::placedBetween(null, Carbon::parse('2026-03-05'))->count())->toBe(1)
            ->and(Order::placedBetween(null, null)->count())->toBe(2);
    });
});

describe('factory states', function () {
    it('sets timestamps and payment status for each stage', function () {
        $delivered = Order::factory()->delivered()->create();
        $cancelled = Order::factory()->cancelled()->create();
        $returned = Order::factory()->returned()->create();

        expect($delivered->status)->toBe(OrderStatus::Delivered)
            ->and($delivered->payment_status)->toBe(PaymentStatus::Paid)
            ->and($delivered->delivered_at)->not->toBeNull()
            ->and($cancelled->cancelled_at)->not->toBeNull()
            ->and($cancelled->payment_status)->toBe(PaymentStatus::Unpaid)
            ->and($returned->status)->toBe(OrderStatus::Returned);
    });

    it('gives each order its own number, token and idempotency key', function () {
        [$a, $b] = Order::factory()->count(2)->create();

        expect($a->number)->not->toBe($b->number)
            ->and($a->public_token)->not->toBe($b->public_token)
            ->and($a->idempotency_key)->not->toBe($b->idempotency_key);
    });
});

describe('unique columns', function () {
    it('refuses a duplicate number, token or idempotency key', function (string $column) {
        $existing = Order::factory()->create();

        Order::factory()->create([$column => $existing->{$column}]);
    })->with(['number', 'public_token', 'idempotency_key'])->throws(UniqueConstraintViolationException::class);
});

describe('items', function () {
    it('snapshots the variant it was created from', function () {
        $item = OrderItem::factory()->quantity(3)->create()->fresh();

        expect($item->product_name)->toBe($item->product->name)
            ->and($item->sku)->toBe($item->variant->sku)
            ->and($item->unit_price)->toBe(150000)
            ->and($item->line_total)->toBe(450000);
    });
});
