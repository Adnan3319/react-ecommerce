<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Sample two-level catalogue. Safe to run repeatedly (matched by slug).
     */
    public function run(): void
    {
        foreach ($this->tree() as $rootOrder => $root) {
            $children = $root['children'];
            unset($root['children']);

            $parent = $this->upsert($root + ['sort_order' => $rootOrder + 1]);

            foreach ($children as $childOrder => $child) {
                $this->upsert($child + ['sort_order' => $childOrder + 1, 'parent_id' => $parent->id]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsert(array $attributes): Category
    {
        return Category::updateOrCreate(
            ['slug' => $attributes['slug']],
            $attributes + ['status' => ActiveStatus::Active],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tree(): array
    {
        return [
            [
                'name' => 'Kitchenware',
                'slug' => 'kitchenware',
                'headline' => 'Cook with Joy',
                'description' => 'Premium kitchen essentials for your home.',
                'image' => '/images/categories/kitchenware.jpg',
                'children' => [
                    ['name' => 'Cookware', 'slug' => 'cookware'],
                    ['name' => 'Kitchen Storage', 'slug' => 'kitchen-storage'],
                    ['name' => 'Glassware', 'slug' => 'glassware'],
                ],
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
                'headline' => 'Dress Your Style',
                'description' => 'Trendy and comfortable for every occasion.',
                'image' => '/images/categories/fashion.jpg',
                'children' => [
                    ['name' => "Women's Clothing", 'slug' => 'womens-clothing'],
                    ['name' => 'Bags & Accessories', 'slug' => 'bags-accessories'],
                ],
            ],
        ];
    }
}
