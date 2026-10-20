<?php

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->product = Product::factory()->simple(100000, 20)->create();
    $this->variant = $this->product->variants()->sole();
});

function attributionPayload(int $variantId): array
{
    return [
        'name' => 'Rahim Uddin',
        'phone' => '01712345678',
        'address' => 'House 1, Road 2, Dhanmondi, Dhaka',
        'delivery_zone' => 'inside_dhaka',
        'items' => [['variant_id' => $variantId, 'quantity' => 1]],
        'idempotency_key' => (string) Str::uuid(),
        'website' => '',
    ];
}

describe('landing parameters', function () {
    it('end up on the order after browsing other pages', function () {
        $this->get(route('products.show', $this->product->slug).'?utm_source=facebook&utm_medium=cpc&utm_campaign=eid-sale&fbclid=abc123')->assertOk();
        $this->get(route('home'))->assertOk();
        $this->get(route('about'))->assertOk();

        $this->post(route('orders.store'), attributionPayload($this->variant->id))->assertRedirect();

        $order = Order::sole();

        expect($order->only(['utm_source', 'utm_medium', 'utm_campaign', 'fbclid']))->toBe([
            'utm_source' => 'facebook',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'eid-sale',
            'fbclid' => 'abc123',
        ]);
    });

    it('are replaced as a whole by a second landing, not merged', function () {
        $this->get(route('home').'?utm_source=facebook&utm_medium=cpc&utm_campaign=eid-sale&fbclid=abc123');
        $this->get(route('products.show', $this->product->slug).'?utm_source=google&utm_campaign=winter');

        $this->post(route('orders.store'), attributionPayload($this->variant->id))->assertRedirect();

        expect(Order::sole()->only(['utm_source', 'utm_medium', 'utm_campaign', 'fbclid']))->toBe([
            'utm_source' => 'google',
            'utm_medium' => null,
            'utm_campaign' => 'winter',
            'fbclid' => null,
        ]);
    });

    it('are kept when a later page has no parameters', function () {
        $this->get(route('home').'?fbclid=abc123');
        $this->get(route('about'));

        $this->post(route('orders.store'), attributionPayload($this->variant->id));

        expect(Order::sole()->fbclid)->toBe('abc123');
    });

    it('stay empty for a visitor who arrived without any', function () {
        $this->get(route('home'));

        $this->post(route('orders.store'), attributionPayload($this->variant->id));

        expect(Order::sole()->only(['utm_source', 'utm_medium', 'utm_campaign', 'fbclid']))->toBe([
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'fbclid' => null,
        ]);
    });

    it('ignore blank values and cut long ones to 255 characters', function () {
        $this->get(route('home').'?utm_source=%20&utm_campaign='.str_repeat('a', 300));

        $this->post(route('orders.store'), attributionPayload($this->variant->id));

        $order = Order::sole();

        expect($order->utm_source)->toBeNull()->and($order->utm_campaign)->toHaveLength(255);
    });

    it('are not captured on admin pages', function () {
        $this->get('/admin/login?utm_source=admin-test');

        $this->post(route('orders.store'), attributionPayload($this->variant->id));

        expect(Order::sole()->utm_source)->toBeNull();
    });
});
