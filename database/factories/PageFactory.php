<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'title' => ucfirst($title),
            'sub_title' => fake()->sentence(6),
            'slug' => str($title)->slug()->toString(),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'image' => null,
            'status' => ActiveStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => ActiveStatus::Inactive]);
    }
}
