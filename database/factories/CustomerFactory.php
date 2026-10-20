<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '01'.fake()->numberBetween(3, 9).fake()->unique()->numerify('########'),
            'is_blocked' => false,
            'orders_count' => 0,
            'delivered_count' => 0,
            'cancelled_count' => 0,
        ];
    }

    public function blocked(): static
    {
        return $this->state(fn () => ['is_blocked' => true]);
    }
}
