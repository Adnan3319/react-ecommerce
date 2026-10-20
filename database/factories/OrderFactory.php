<?php

namespace Database\Factories;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * A pending cash-on-delivery order with no lines: add them with withItems() (totals follow the lines)
 * and move it with the status states, which also set the matching timestamps and payment status.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    private const DEFAULT_SUBTOTAL = 150000;

    private const DEFAULT_DELIVERY_CHARGE = 7000;

    public function definition(): array
    {
        return [
            'number' => sprintf('CK-%s-%04d', now()->format('ymd'), fake()->unique()->numberBetween(1, 9999)),
            'public_token' => Str::random(32),
            'customer_id' => Customer::factory(),
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Unpaid,
            'source' => OrderSource::Cart,
            'subtotal' => self::DEFAULT_SUBTOTAL,
            'delivery_charge' => self::DEFAULT_DELIVERY_CHARGE,
            'total' => self::DEFAULT_SUBTOTAL + self::DEFAULT_DELIVERY_CHARGE,
            'shipping_name' => fake()->name(),
            'shipping_phone' => '01'.fake()->numberBetween(3, 9).fake()->numerify('########'),
            'shipping_address' => fake()->address(),
            'delivery_zone' => 'inside_dhaka',
            'delivery_zone_label' => 'Inside Dhaka',
            'customer_note' => null,
            'idempotency_key' => (string) Str::uuid(),
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'fbclid' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'Pest',
        ];
    }

    /**
     * Creates `$count` lines after the order and makes the totals match them.
     */
    public function withItems(int $count = 1): static
    {
        return $this->afterCreating(function (Order $order) use ($count): void {
            OrderItem::factory()->count($count)->create(['order_id' => $order->id]);

            $subtotal = (int) $order->items()->sum('line_total');
            $order->update(['subtotal' => $subtotal, 'total' => $subtotal + $order->delivery_charge]);
        });
    }

    public function buyNow(): static
    {
        return $this->state(fn () => ['source' => OrderSource::BuyNow]);
    }

    public function cart(): static
    {
        return $this->state(fn () => ['source' => OrderSource::Cart]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Pending]);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Processing,
            'confirmed_at' => now(),
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Shipped,
            'confirmed_at' => now(),
            'shipped_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Delivered,
            'payment_status' => PaymentStatus::Paid,
            'confirmed_at' => now(),
            'shipped_at' => now(),
            'delivered_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Returned after delivery (paid; the refund is manual).
     */
    public function returned(): static
    {
        return $this->delivered()->state(fn () => ['status' => OrderStatus::Returned]);
    }
}
