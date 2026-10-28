<?php

use App\Enums\OrderSource;
use App\Jobs\NotifyAdminOfNewOrder;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    Queue::fake();
    $this->token = cartVisitor();
});

/**
 * Form data of the Cart page: details and delivery area only, no items.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function cartOrderPayload(array $overrides = []): array
{
    return [
        'source' => 'cart',
        'name' => 'Nusrat Jahan',
        'phone' => '01712 345678',
        'address' => 'House 1, Road 2, Dhanmondi, Dhaka',
        'delivery_zone' => 'outside',
        'idempotency_key' => (string) Str::uuid(),
        'website' => '',
        ...$overrides,
    ];
}

describe('placing an order from the cart', function () {
    it('orders every payable line with database prices and source cart', function () {
        $dress = cartVariant(price: 165000, stock: 10);
        $basket = cartVariant(price: 65000, stock: 5);
        cartSeed($this->token, $dress, 1);
        cartSeed($this->token, $basket, 2, priceAtAdd: 70000);

        $response = $this->post(route('orders.store'), cartOrderPayload())->assertSessionHasNoErrors();

        $order = Order::query()->with('items')->sole();
        $response->assertRedirect(route('orders.success', $order->public_token));
        expect($order->source)->toBe(OrderSource::Cart)
            ->and($order->items->pluck('quantity', 'product_variant_id')->all())->toBe([$dress->id => 1, $basket->id => 2])
            ->and($order->subtotal)->toBe(165000 + 2 * 65000)
            ->and($order->delivery_charge)->toBe(13000)
            ->and($order->total)->toBe(165000 + 130000 + 13000)
            ->and($dress->fresh()->stock)->toBe(9)
            ->and($basket->fresh()->stock)->toBe(3);
        Queue::assertPushed(NotifyAdminOfNewOrder::class);
    });

    it('empties the cart of the ordered lines', function () {
        cartSeed($this->token, cartVariant(), 2);
        cartSeed($this->token, cartVariant(), 1);

        $this->post(route('orders.store'), cartOrderPayload())->assertSessionHasNoErrors();

        expect(CartItem::query()->where('cart_token', $this->token)->count())->toBe(0)
            ->and(cartPage()['lines'])->toBe([]);
    });

    it('leaves an unavailable line out of the order and in the cart', function () {
        $ok = cartVariant(stock: 5);
        $soldOut = cartVariant(stock: 5);
        cartSeed($this->token, $ok, 1);
        cartSeed($this->token, $soldOut, 1);
        $soldOut->update(['stock' => 0]);

        $this->post(route('orders.store'), cartOrderPayload())->assertSessionHasNoErrors();

        expect(Order::query()->sole()->items->pluck('product_variant_id')->all())->toBe([$ok->id])
            ->and(CartItem::query()->where('cart_token', $this->token)->pluck('product_variant_id')->all())->toBe([$soldOut->id]);
    });

    it('refuses an empty cart', function () {
        $this->post(route('orders.store'), cartOrderPayload())->assertSessionHasErrors('items');

        expect(Order::query()->count())->toBe(0);
    });

    it('refuses a cart with nothing left to order and keeps the cart', function () {
        $variant = cartVariant(stock: 5);
        cartSeed($this->token, $variant, 1);
        $variant->update(['status' => 'inactive']);

        $this->post(route('orders.store'), cartOrderPayload())->assertSessionHasErrors('items');

        expect(Order::query()->count())->toBe(0)
            ->and(CartItem::query()->count())->toBe(1);
    });

    it('keeps the cart when the order is refused', function () {
        cartSeed($this->token, cartVariant(), 1);

        $this->post(route('orders.store'), cartOrderPayload(['phone' => '12345']))->assertSessionHasErrors('phone');

        expect(CartItem::query()->count())->toBe(1);
    });

    it('never takes items, prices or totals from the client', function (array $injected) {
        $own = cartVariant(price: 100000);
        $foreign = cartVariant(price: 100);
        cartSeed($this->token, $own, 1);

        $this->post(route('orders.store'), cartOrderPayload($injected + ['items' => [['variant_id' => $foreign->id, 'quantity' => 1]]]))
            ->assertSessionHasErrors('items');

        expect(Order::query()->count())->toBe(0)
            ->and(CartItem::query()->where('cart_token', $this->token)->count())->toBe(1);
    })->with([
        'foreign variant' => [[]],
        'with a price' => [['unit_price' => 1, 'total' => 1]],
    ]);

    it('ignores a price sent next to a valid cart order', function () {
        cartSeed($this->token, cartVariant(price: 100000), 1);

        $this->post(route('orders.store'), cartOrderPayload(['unit_price' => 1, 'total' => 1, 'subtotal' => 1]))->assertSessionHasNoErrors();

        expect(Order::query()->sole()->subtotal)->toBe(100000);
    });

    it('does not order the lines of another visitor', function () {
        $theirs = cartVariant();
        cartSeed(Str::random(40), $theirs, 3);

        $this->post(route('orders.store'), cartOrderPayload())->assertSessionHasErrors('items');

        expect(Order::query()->count())->toBe(0)
            ->and(CartItem::query()->count())->toBe(1);
    });

    it('creates one order when the form is sent twice with the same key', function () {
        cartSeed($this->token, cartVariant(), 1);
        $payload = cartOrderPayload();

        $this->post(route('orders.store'), $payload);
        $this->post(route('orders.store'), $payload);

        expect(Order::query()->count())->toBe(1);
    });

    it('still refuses a blocked phone and leaves the cart alone', function () {
        cartSeed($this->token, cartVariant(), 1);
        Customer::factory()->create(['phone' => '01712345678', 'is_blocked' => true]);

        $this->post(route('orders.store'), cartOrderPayload())->assertSessionHasErrors('phone');

        expect(CartItem::query()->count())->toBe(1);
    });
});

describe('order now', function () {
    it('still works without a source and does not touch the cart', function () {
        $variant = cartVariant(stock: 5);
        cartSeed($this->token, cartVariant(), 2);

        $payload = cartOrderPayload(['items' => [['variant_id' => $variant->id, 'quantity' => 1]]]);
        unset($payload['source']);
        $this->post(route('orders.store'), $payload)->assertSessionHasNoErrors();

        expect(Order::query()->sole()->source)->toBe(OrderSource::BuyNow)
            ->and(CartItem::query()->where('cart_token', $this->token)->count())->toBe(1);
    });

    it('rejects an unknown source', function () {
        $variant = cartVariant();

        $this->post(route('orders.store'), cartOrderPayload(['source' => 'wholesale', 'items' => [['variant_id' => $variant->id, 'quantity' => 1]]]))
            ->assertSessionHasErrors('source');
    });
});
