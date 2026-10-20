<?php

use App\Enums\ActiveStatus;
use App\Models\Category;
use Database\Seeders\CategorySeeder;

describe('tree', function () {
    it('creates two top-level categories with their sub-categories', function () {
        $this->seed(CategorySeeder::class);

        $kitchenware = Category::where('slug', 'kitchenware')->sole();
        $fashion = Category::where('slug', 'fashion')->sole();

        expect(Category::roots()->count())->toBe(2)
            ->and(Category::count())->toBe(7)
            ->and($kitchenware->children->pluck('name')->all())->toBe(['Cookware', 'Kitchen Storage', 'Glassware'])
            ->and($fashion->children->pluck('name')->all())->toBe(["Women's Clothing", 'Bags & Accessories']);
    });

    it('keeps every seeded category active and the hierarchy two levels deep', function () {
        $this->seed(CategorySeeder::class);

        expect(Category::where('status', '!=', ActiveStatus::Active->value)->count())->toBe(0)
            ->and(Category::whereNotNull('parent_id')->whereHas('parent', fn ($q) => $q->whereNotNull('parent_id'))->count())->toBe(0);
    });
});

describe('idempotency', function () {
    it('does not duplicate anything when run twice', function () {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        expect(Category::count())->toBe(7)
            ->and(Category::roots()->count())->toBe(2);
    });

    it('restores a renamed seeded category instead of creating another', function () {
        $this->seed(CategorySeeder::class);
        Category::where('slug', 'cookware')->update(['name' => 'Renamed']);

        $this->seed(CategorySeeder::class);

        expect(Category::where('slug', 'cookware')->sole()->name)->toBe('Cookware')
            ->and(Category::count())->toBe(7);
    });
});
