<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->variant = Product::factory()->simple(165000, 10)->create()->variants()->sole();
});

/**
 * @return array<string, mixed>
 */
function notificationOrderPayload(int $variantId): array
{
    return [
        'name' => 'Nusrat Jahan',
        'phone' => '01712345678',
        'address' => 'House 1, Road 2, Dhanmondi, Dhaka',
        'delivery_zone' => 'inside',
        'items' => [['variant_id' => $variantId, 'quantity' => 1]],
        'idempotency_key' => (string) Str::uuid(),
        'website' => '',
    ];
}

it('notifies every active admin exactly once and no inactive admin', function () {
    $admins = User::factory()->count(2)->create();
    $inactive = User::factory()->inactive()->create();

    $this->post(route('orders.store'), notificationOrderPayload($this->variant->id))->assertRedirect();

    $order = Order::sole();

    expect(DatabaseNotification::count())->toBe(2)
        ->and($inactive->notifications()->count())->toBe(0);
    $admins->each(fn (User $admin) => expect($admin->notifications()->count())->toBe(1));
    expect($admins->first()->notifications->first()->data['reference_id'])->toBe($order->id);
});

it('stores exactly the five documented keys and neither the phone nor the address', function () {
    $admin = User::factory()->create();

    $this->post(route('orders.store'), notificationOrderPayload($this->variant->id));

    $order = Order::sole();
    $data = $admin->notifications()->sole()->data;

    expect(array_keys($data))->toEqualCanonicalizing(['type', 'title', 'body', 'url', 'reference_id'])
        ->and($data['type'])->toBe('order')
        ->and($data['title'])->toBe("New order {$order->number}")
        ->and($data['body'])->toBe('Nusrat Jahan · ৳1,720 · Inside City')
        ->and($data['url'])->toBe("/admin/orders/{$order->id}")
        ->and(json_encode($data))->not->toContain('01712345678')->not->toContain('Dhanmondi');
});

it('does not notify when the order request is a duplicate of an earlier one', function () {
    $admin = User::factory()->create();
    $payload = notificationOrderPayload($this->variant->id);

    $this->post(route('orders.store'), $payload);
    $this->post(route('orders.store'), $payload);

    expect($admin->notifications()->count())->toBe(1);
});

it('still places the order when the notification cannot be stored', function () {
    Exceptions::fake();
    User::factory()->create();
    Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('database is down'));

    $this->post(route('orders.store'), notificationOrderPayload($this->variant->id))->assertRedirect();

    expect(Order::count())->toBe(1);
    Exceptions::assertReported(RuntimeException::class);
});
