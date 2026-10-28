<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => null,
            'mobile' => '017'.fake()->unique()->numerify('########'),
            'message' => fake()->sentence(12),
            'is_read' => false,
        ];
    }

    public function read(): static
    {
        return $this->state(['is_read' => true]);
    }
}
