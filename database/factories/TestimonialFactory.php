<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Enums\TestimonialSource;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_name' => fake()->name(),
            'text' => fake()->sentence(12),
            'rating' => fake()->numberBetween(Testimonial::MIN_RATING, Testimonial::MAX_RATING),
            'source' => TestimonialSource::Facebook,
            'status' => ActiveStatus::Active,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive]);
    }
}
