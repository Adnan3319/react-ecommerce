<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Kitchenware',
                'slug' => 'kitchenware',
                'headline' => 'Cook with Joy',
                'description' => 'Premium kitchen essentials for your home.',
                'image' => '/images/categories/kitchenware.jpg',
                'sort_order' => 1,
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
                'headline' => 'Dress Your Style',
                'description' => 'Trendy and comfortable for every occasion.',
                'image' => '/images/categories/fashion.jpg',
                'sort_order' => 2,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['status' => 'Active'],
            );
        }
    }
}