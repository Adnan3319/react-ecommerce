<?php

namespace Database\Factories;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'headline' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'image' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'status' => CategoryStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => CategoryStatus::Inactive]);
    }
}
