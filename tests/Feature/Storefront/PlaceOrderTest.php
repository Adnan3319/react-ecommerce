<?php

use App\Enums\OrderStatus;
use App\Jobs\NotifyAdminOfNewOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Queue::fake();
    $this->variant = Product::factory()->simple(165000, 10)->create(['name' => "Women's Summer Dress"])->variants()->sole();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function placeOrderPayload(int $variantId, array $overrides = []): array
{
    return [
        'name' => 'Nusrat Jahan',
        'phone' => '01712 345678',
        'address' => 'House 1, Road 2, Dhanmondi, Dhaka',
        'delivery_zone' => 'inside_dhaka',
        'note' => 'Call before delivery',
        'items' => [['variant_id' => $variantId, 'quantity' => 1]],
        'idempotency_key' => (string) Str::uuid(),
        'website' => '',
        ...$overrides,
    ];
}

describe('placing an order', function () {
    it('creates the order, reduces stock and redirects to the thank-you page', function () {
        $response = $this->post(route('orders.store'), placeOrderPayload($this->variant->id, [
            'items' => [['variant_id' => $this->variant->id, 'quantity' => 2]],
        ]));

        $order = Order::sole();

        $response->assertRedirect(route('orders.success', $order->public_token));
        expect($order->status)->toBe(OrderStatus::Pending)
            ->and($order->subtotal)->toBe(330000)
            ->and($order->delivery_charge)->toBe(7000)
            ->and($order->total)->toBe(337000)
            ->and($order->shipping_phone)->toBe('01712345678')
            ->and($order->customer_note)->toBe('Call before delivery')
            ->and($order->source->value)->toBe('buy_now')
            ->and($order->ip_address)->not->toBeNull()
            ->and(Customer::sole()->phone)->toBe('01712345678')
            ->and($this->variant->fresh()->stock)->toBe(8)
            ->and(StockMovement::where('reference_id', $order->id)->sole()->quantity)->toBe(-2);
        Queue::assertPushed(NotifyAdminOfNewOrder::class);
    });

    it('works without a note', function () {
        $this->post(route('orders.store'), placeOrderPayload($this->variant->id, ['note' => null]))->assertRedirect();

        expect(Order::sole()->customer_note)->toBeNull();
    });

    it('ignores a price sent by the client', function () {
        $this->post(route('orders.store'), placeOrderPayload($this->variant->id, ['price' => 1, 'total' => 1, 'unit_price' => 1]));

        expect(Order::sole()->total)->toBe(172000);
    });

    it('returns the same order when the form is sent twice with the same key', function () {
        $payload = placeOrderPayload($this->variant->id);

        $this->post(route('orders.store'), $payload)->assertRedirect();
        $this->post(route('orders.store'), $payload)->assertRedirect(route('orders.success', Order::sole()->public_token));

        expect(Order::count())->toBe(1)->and($this->variant->fresh()->stock)->toBe(9);
    });
});

describe('validation', function () {
    it('rejects invalid input and writes nothing', function (array $overrides, string $field) {
        $this->post(route('orders.store'), placeOrderPayload($this->variant->id, $overrides))->assertSessionHasErrors($field);

        expect(Order::count())->toBe(0)->and($this->variant->fresh()->stock)->toBe(10);
    })->with([
        'short name' => [['name' => 'A'], 'name'],
        'missing name' => [['name' => ''], 'name'],
        'not a phone' => [['phone' => '12345'], 'phone'],
        'wrong operator prefix' => [['phone' => '01212345678'], 'phone'],
        'short address' => [['address' => 'Dhaka'], 'address'],
        'unknown delivery area' => [['delivery_zone' => 'mars'], 'delivery_zone'],
        'missing delivery area' => [['delivery_zone' => ''], 'delivery_zone'],
        'long note' => [['note' => str_repeat('a', 501)], 'note'],
        'filled honeypot' => [['website' => 'https://spam.example'], 'website'],
        'bad idempotency key' => [['idempotency_key' => 'not-a-uuid'], 'idempotency_key'],
        'no items' => [['items' => []], 'items'],
        'two items' => [['items' => [['variant_id' => 1, 'quantity' => 1], ['variant_id' => 2, 'quantity' => 1]]], 'items'],
        'zero quantity' => [['items' => [['variant_id' => 1, 'quantity' => 0]]], 'items.0.quantity'],
    ]);

    it('names the quantity field when the stock is too low', function () {
        $this->variant->update(['stock' => 3]);

        $this->post(route('orders.store'), placeOrderPayload($this->variant->id, [
            'items' => [['variant_id' => $this->variant->id, 'quantity' => 5]],
        ]))->assertSessionHasErrors(['items.0.quantity' => 'Only 3 left.']);

        expect(Order::count())->toBe(0);
    });

    it('names the variant when it is not available', function () {
        $this->variant->product->update(['status' => 'inactive']);

        $this->post(route('orders.store'), placeOrderPayload($this->variant->id))->assertSessionHasErrors('items.0.variant');
    });

    it('refuses a blocked phone', function () {
        Customer::factory()->blocked()->create(['phone' => '01712345678']);

        $this->post(route('orders.store'), placeOrderPayload($this->variant->id))->assertSessionHasErrors('phone');

        expect(Order::count())->toBe(0);
    });
});

describe('throttle', function () {
    it('allows 5 order requests a minute per IP and then answers 429', function () {
        $invalid = placeOrderPayload($this->variant->id, ['phone' => 'x']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('orders.store'), $invalid)->assertSessionHasErrors('phone');
        }

        $this->post(route('orders.store'), $invalid)->assertStatus(429);
    });

    it('starts again after a minute', function () {
        $invalid = placeOrderPayload($this->variant->id, ['phone' => 'x']);
        foreach (range(1, 5) as $attempt) {
            $this->post(route('orders.store'), $invalid);
        }

        $this->travel(61)->seconds();

        $this->post(route('orders.store'), $invalid)->assertSessionHasErrors('phone');
    });
});

describe('thank-you page', function () {
    it('shows the order for its token and is noindex', function () {
        $this->post(route('orders.store'), placeOrderPayload($this->variant->id));
        $order = Order::sole();

        $this->get(route('orders.success', $order->public_token))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Orders/Success', false)
                ->where('order.number', $order->number)
                ->where('order.first_name', 'Nusrat')
                ->where('order.phone', '01712345678')
                ->where('order.subtotal', 165000)
                ->where('order.delivery_charge', 7000)
                ->where('order.delivery_zone_label', 'Inside Dhaka')
                ->where('order.total', 172000)
                ->where('order.items.0.name', "Women's Summer Dress")
                ->where('order.items.0.quantity', 1)
                ->where('seo.noindex', true));
    });

    it('asks search engines not to index the page in the first HTML response', function () {
        $order = Order::factory()->withItems()->create();

        $this->get(route('orders.success', $order->public_token))->assertSee('content="noindex,follow"', false);
    });

    it('answers 404 for an unknown token', function () {
        $this->get(route('orders.success', 'does-not-exist'))->assertNotFound();
    });

    it('works for 7 days and then answers 404', function () {
        $order = Order::factory()->withItems()->create();

        $this->travel(6)->days();
        $this->get(route('orders.success', $order->public_token))->assertOk();

        $this->travel(2)->days();
        $this->get(route('orders.success', $order->public_token))->assertNotFound();
    });

    it('does not expose the order by its id', function () {
        $order = Order::factory()->create();

        $this->get('/orders/'.$order->id.'/thank-you')->assertNotFound();
    });
});
