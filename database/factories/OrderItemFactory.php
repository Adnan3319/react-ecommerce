<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An order line that snapshots a real variant (a new default variant unless given with forVariant()).
 *
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'product_id' => fn (array $attributes) => ProductVariant::findOrFail($attributes['product_variant_id'])->product_id,
            'product_name' => fn (array $attributes) => Product::findOrFail($attributes['product_id'])->name,
            'variant_label' => null,
            'sku' => fn (array $attributes) => ProductVariant::findOrFail($attributes['product_variant_id'])->sku,
            'image_path' => null,
            'unit_price' => fn (array $attributes) => ProductVariant::findOrFail($attributes['product_variant_id'])->effective_price,
            'quantity' => 1,
            'line_total' => fn (array $attributes) => $attributes['unit_price'] * $attributes['quantity'],
        ];
    }

    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn () => ['product_variant_id' => $variant->id]);
    }

    public function quantity(int $quantity): static
    {
        return $this->state(fn () => ['quantity' => $quantity]);
    }
}
