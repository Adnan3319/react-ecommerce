<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\VariantHead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VariantHead>
 */
class VariantHeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->unique()->word(),
            'position' => 0,
        ];
    }
}
