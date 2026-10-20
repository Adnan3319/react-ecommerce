<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Enums\StockMovementReason;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Creates a default variant (no selected values). A variant created with stock also gets its
 * "initial" stock movement, so the movements always add up to the stock.
 *
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-????-####')),
            'price' => 150000,
            'sale_price' => null,
            'stock' => 10,
            'product_image_id' => null,
            'status' => ActiveStatus::Active,
            'sort_order' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ProductVariant $variant): void {
            if ($variant->stock > 0) {
                StockMovement::create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $variant->stock,
                    'reason' => StockMovementReason::Initial,
                ]);
            }
        });
    }

    /**
     * A second variant of the same product needs its own combination (the default one is taken).
     */
    public function combination(string $key): static
    {
        return $this->state(fn () => ['combination_key' => $key]);
    }

    public function onSale(int $salePrice): static
    {
        return $this->state(fn () => ['sale_price' => $salePrice]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive]);
    }
}
