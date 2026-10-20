<?php

namespace Database\Factories;

use App\Models\VariantHead;
use App\Models\VariantValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VariantValue>
 */
class VariantValueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'variant_head_id' => VariantHead::factory(),
            'value' => fake()->unique()->word(),
            'position' => 0,
        ];
    }
}
