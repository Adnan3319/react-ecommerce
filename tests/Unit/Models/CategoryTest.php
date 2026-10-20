<?php

use App\Models\Category;

describe('relations', function () {
    it('links a sub-category to its parent and back', function () {
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create();

        expect($child->parent->is($parent))->toBeTrue()
            ->and($parent->children->pluck('id')->all())->toBe([$child->id])
            ->and($parent->parent)->toBeNull();
    });

    it('orders children by sort order then name', function () {
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent)->create(['name' => 'Zed', 'sort_order' => 1]);
        Category::factory()->childOf($parent)->create(['name' => 'Beta', 'sort_order' => 2]);
        Category::factory()->childOf($parent)->create(['name' => 'Alpha', 'sort_order' => 2]);

        expect($parent->children->pluck('name')->all())->toBe(['Zed', 'Alpha', 'Beta']);
    });

    it('does not count soft-deleted sub-categories as children', function () {
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent)->create()->delete();

        expect($parent->children()->exists())->toBeFalse();
    });
});

describe('scopes', function () {
    it('roots() returns only top-level categories', function () {
        $root = Category::factory()->create();
        Category::factory()->childOf($root)->create();

        expect(Category::roots()->pluck('id')->all())->toBe([$root->id]);
    });

    it('leaves() returns categories without sub-categories', function () {
        $withChildren = Category::factory()->create();
        $child = Category::factory()->childOf($withChildren)->create();
        $childless = Category::factory()->create();

        $leafIds = Category::leaves()->pluck('id')->all();

        expect($leafIds)->toContain($child->id, $childless->id)
            ->not->toContain($withChildren->id);
    });

    it('treeOrdered() puts every sub-category right after its parent', function () {
        $first = Category::factory()->create(['name' => 'First', 'sort_order' => 1]);
        $second = Category::factory()->create(['name' => 'Second', 'sort_order' => 2]);
        Category::factory()->childOf($second)->create(['name' => 'Second child', 'sort_order' => 1]);
        Category::factory()->childOf($first)->create(['name' => 'First child b', 'sort_order' => 2]);
        Category::factory()->childOf($first)->create(['name' => 'First child a', 'sort_order' => 1]);

        $names = Category::treeOrdered()->pluck('name')->all();

        expect($names)->toBe(['First', 'First child a', 'First child b', 'Second', 'Second child']);
    });
});
