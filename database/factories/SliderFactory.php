<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Models\Slider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slider>
 */
class SliderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(),
            'image' => 'sliders/'.fake()->uuid().'.webp',
            'button_text' => 'Shop Now',
            'button_link' => '/search',
            'sort_order' => fake()->numberBetween(0, 100),
            'status' => ActiveStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive]);
    }
}
