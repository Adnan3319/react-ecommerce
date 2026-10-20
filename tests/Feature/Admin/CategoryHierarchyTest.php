<?php

use App\Enums\ActiveStatus;
use App\Enums\SliderPosition;
use App\Models\Category;
use App\Services\Category\CategoryService;
use App\Services\Slider\SliderService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('uploads');
});

function hierarchyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Cookware',
        'status' => ActiveStatus::Active->value,
        'sort_order' => 1,
    ], $overrides);
}

function hierarchyUpdate(Category $category, array $data): TestResponse
{
    return actingAsAdmin()->post(route('admin.categories.update', $category), ['_method' => 'put', ...$data]);
}

describe('create', function () {
    it('files a new category under an active top-level parent', function () {
        $parent = Category::factory()->create(['name' => 'Kitchenware', 'slug' => 'kitchenware']);

        $response = actingAsAdmin()->post(route('admin.categories.store'), hierarchyPayload(['parent_id' => $parent->id]));

        $response->assertRedirect()->assertSessionHasNoErrors();
        expect(Category::where('slug', 'cookware')->sole()->parent_id)->toBe($parent->id);
    });

    it('creates a top-level category when the parent is empty', function () {
        $response = actingAsAdmin()->post(route('admin.categories.store'), hierarchyPayload(['parent_id' => '']));

        $response->assertRedirect()->assertSessionHasNoErrors();
        expect(Category::where('slug', 'cookware')->sole()->parent_id)->toBeNull();
    });

    it('rejects a parent that is not an active top-level category', function (string $case) {
        $root = Category::factory()->create();
        $parentId = match ($case) {
            'a sub-category' => Category::factory()->childOf($root)->create()->id,
            'an inactive category' => Category::factory()->inactive()->create()->id,
            'a soft-deleted category' => tap(Category::factory()->create())->delete()->id,
            'a missing category' => 999999,
        };

        $response = actingAsAdmin()->post(route('admin.categories.store'), hierarchyPayload(['parent_id' => $parentId]));

        $response->assertSessionHasErrors(['parent_id' => 'The parent must be an active top-level category.']);
        expect(Category::where('slug', 'cookware')->exists())->toBeFalse();
    })->with(['a sub-category', 'an inactive category', 'a soft-deleted category', 'a missing category']);

    it('rejects a non-numeric parent', function () {
        $response = actingAsAdmin()->post(route('admin.categories.store'), hierarchyPayload(['parent_id' => 'abc']));

        $response->assertSessionHasErrors('parent_id');
    });
});

describe('update', function () {
    it('moves a sub-category under another top-level category', function () {
        $first = Category::factory()->create();
        $second = Category::factory()->create();
        $child = Category::factory()->childOf($first)->create();

        hierarchyUpdate($child, ['parent_id' => $second->id])->assertSessionHasNoErrors();

        expect($child->fresh()->parent_id)->toBe($second->id);
    });

    it('moves a sub-category to the top level with an empty parent', function () {
        $child = Category::factory()->childOf(Category::factory()->create())->create();

        hierarchyUpdate($child, ['parent_id' => ''])->assertSessionHasNoErrors();

        expect($child->fresh()->parent_id)->toBeNull();
    });

    it('rejects a category as its own parent', function () {
        $category = Category::factory()->create();

        $response = hierarchyUpdate($category, ['parent_id' => $category->id]);

        $response->assertSessionHasErrors(['parent_id' => 'A category cannot be its own parent.']);
        expect($category->fresh()->parent_id)->toBeNull();
    });

    it('rejects turning a category that has sub-categories into a sub-category', function () {
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent)->create();
        $other = Category::factory()->create();

        $response = hierarchyUpdate($parent, ['parent_id' => $other->id]);

        $response->assertSessionHasErrors(['parent_id' => 'A category with sub-categories cannot become a sub-category.']);
        expect($parent->fresh()->parent_id)->toBeNull();
    });

    it('rejects a sub-category as the parent of another category (max two levels)', function () {
        $child = Category::factory()->childOf(Category::factory()->create())->create();
        $category = Category::factory()->create();

        $response = hierarchyUpdate($category, ['parent_id' => $child->id]);

        $response->assertSessionHasErrors('parent_id');
        expect($category->fresh()->parent_id)->toBeNull();
    });

    it('keeps the parent when only the status changes', function () {
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create();

        hierarchyUpdate($child, ['status' => 'inactive'])->assertSessionHasNoErrors();

        expect($child->fresh())
            ->parent_id->toBe($parent->id)
            ->status->toBe(ActiveStatus::Inactive);
    });

    it('lets a sub-category keep its parent in a full edit', function () {
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create(['name' => 'Old']);

        hierarchyUpdate($child, ['name' => 'New', 'parent_id' => $parent->id])->assertSessionHasNoErrors();

        expect($child->fresh())->name->toBe('New')->parent_id->toBe($parent->id);
    });
});

describe('delete', function () {
    it('refuses to delete a category that has sub-categories', function () {
        $parent = Category::factory()->create(['image' => 'categories/parent.webp']);
        Storage::disk('uploads')->put('categories/parent.webp', 'bytes');
        Category::factory()->childOf($parent)->create();

        $response = actingAsAdmin()->delete(route('admin.categories.destroy', $parent));

        $response->assertSessionHasErrors(['category' => 'Delete or move its sub-categories first.']);
        expect($parent->fresh()->trashed())->toBeFalse();
        Storage::disk('uploads')->assertExists('categories/parent.webp');
    });

    it('deletes the parent once its sub-categories are gone', function () {
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create();
        actingAsAdmin()->delete(route('admin.categories.destroy', $child));

        $response = actingAsAdmin()->delete(route('admin.categories.destroy', $parent));

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSoftDeleted($parent);
    });

    it('deletes a sub-category and keeps its parent', function () {
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create();

        actingAsAdmin()->delete(route('admin.categories.destroy', $child))->assertSessionHasNoErrors();

        $this->assertSoftDeleted($child);
        expect($parent->fresh()->trashed())->toBeFalse();
    });
});

describe('list', function () {
    it('lists each top-level category followed by its sub-categories', function () {
        $a = Category::factory()->create(['name' => 'A', 'slug' => 'a', 'sort_order' => 1]);
        $b = Category::factory()->create(['name' => 'B', 'slug' => 'b', 'sort_order' => 2]);
        Category::factory()->create(['name' => 'C', 'slug' => 'c', 'sort_order' => 3]);
        Category::factory()->childOf($a)->create(['name' => 'A1', 'slug' => 'a1', 'sort_order' => 2]);
        Category::factory()->childOf($a)->create(['name' => 'A2', 'slug' => 'a2', 'sort_order' => 1]);
        Category::factory()->childOf($b)->create(['name' => 'B1', 'slug' => 'b1', 'sort_order' => 1]);

        $response = actingAsAdmin()->get(route('admin.categories.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('categories.data', 6)
            ->where('categories.data.0.name', 'A')
            ->where('categories.data.0.children_count', 2)
            ->where('categories.data.1.name', 'A2')
            ->where('categories.data.1.parent_name', 'A')
            ->where('categories.data.2.name', 'A1')
            ->where('categories.data.3.name', 'B')
            ->where('categories.data.4.name', 'B1')
            ->where('categories.data.5.name', 'C')
            ->where('categories.data.5.children_count', 0));
    });

    it('finds a sub-category by name and names its parent', function () {
        $parent = Category::factory()->create(['name' => 'Kitchenware', 'slug' => 'kitchenware']);
        Category::factory()->childOf($parent)->create(['name' => 'Glassware', 'slug' => 'glassware']);

        $response = actingAsAdmin()->get(route('admin.categories.index', ['search' => 'glass']));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Glassware')
            ->where('categories.data.0.parent_name', 'Kitchenware')
            ->where('categories.data.0.parent_id', $parent->id));
    });

    it('offers only active top-level categories as parents', function () {
        $root = Category::factory()->create(['name' => 'Root', 'slug' => 'root', 'sort_order' => 1]);
        Category::factory()->childOf($root)->create();
        Category::factory()->inactive()->create();
        Category::factory()->create()->delete();

        $response = actingAsAdmin()->get(route('admin.categories.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('parents', 1)
            ->where('parents.0.id', $root->id)
            ->where('parents.0.name', 'Root'));
    });
});

describe('cache', function () {
    it('nests active sub-categories under their top-level category as plain arrays', function () {
        $root = Category::factory()->create([
            'name' => 'Kitchenware', 'slug' => 'kitchenware', 'headline' => 'Cook', 'description' => 'Tools', 'image' => 'categories/k.webp',
        ]);
        Category::factory()->childOf($root)->create(['name' => 'Cookware', 'slug' => 'cookware', 'headline' => null, 'description' => null, 'image' => null, 'sort_order' => 2]);
        Category::factory()->childOf($root)->create(['name' => 'Glassware', 'slug' => 'glassware', 'headline' => null, 'description' => null, 'image' => null, 'sort_order' => 1]);

        $categories = app(CategoryService::class)->activeForStorefront();

        expect($categories)->toBe([[
            'slug' => 'kitchenware',
            'label' => 'Kitchenware',
            'headline' => 'Cook',
            'description' => 'Tools',
            'image' => Storage::disk('uploads')->url('categories/k.webp'),
            'children' => [
                ['slug' => 'glassware', 'label' => 'Glassware', 'headline' => null, 'description' => null, 'image' => null],
                ['slug' => 'cookware', 'label' => 'Cookware', 'headline' => null, 'description' => null, 'image' => null],
            ],
        ]])->and(Cache::get(CategoryService::ACTIVE_CACHE_KEY))->toBe($categories);
    });

    it('leaves out inactive sub-categories and hides the children of an inactive top-level category', function () {
        $shown = Category::factory()->create(['slug' => 'shown']);
        Category::factory()->childOf($shown)->create(['slug' => 'visible-child']);
        Category::factory()->childOf($shown)->inactive()->create(['slug' => 'hidden-child']);
        $hidden = Category::factory()->inactive()->create(['slug' => 'hidden']);
        Category::factory()->childOf($hidden)->create(['slug' => 'orphaned-child']);

        $categories = app(CategoryService::class)->activeForStorefront();

        expect(array_column($categories, 'slug'))->toBe(['shown'])
            ->and(array_column($categories[0]['children'], 'slug'))->toBe(['visible-child']);
    });

    it('gives a top-level category without sub-categories an empty children list', function () {
        Category::factory()->create();

        $categories = app(CategoryService::class)->activeForStorefront();

        expect($categories[0]['children'])->toBe([]);
    });

    it('is shared with the Home page with children nested', function () {
        $root = Category::factory()->create(['slug' => 'kitchenware']);
        Category::factory()->childOf($root)->create(['slug' => 'cookware']);

        $response = $this->get(route('home'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('categories', 1)
            ->where('categories.0.slug', 'kitchenware')
            ->where('categories.0.children.0.slug', 'cookware'));
    });

    it('is cleared by a sub-category write and only that key', function (string $action) {
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create();
        Cache::put(CategoryService::ACTIVE_CACHE_KEY, ['stale']);
        Cache::put(SliderService::cacheKey(SliderPosition::Hero), ['keep']);

        match ($action) {
            'create' => actingAsAdmin()->post(route('admin.categories.store'), hierarchyPayload(['parent_id' => $parent->id])),
            'update' => hierarchyUpdate($child, ['name' => 'Renamed']),
            'delete' => actingAsAdmin()->delete(route('admin.categories.destroy', $child)),
        };

        expect(Cache::has(CategoryService::ACTIVE_CACHE_KEY))->toBeFalse()
            ->and(Cache::has(SliderService::cacheKey(SliderPosition::Hero)))->toBeTrue();
    })->with(['create', 'update', 'delete']);

    it('keeps the cache when deleting a parent with sub-categories is refused', function () {
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent)->create();
        Cache::put(CategoryService::ACTIVE_CACHE_KEY, ['still valid']);

        actingAsAdmin()->delete(route('admin.categories.destroy', $parent));

        expect(Cache::has(CategoryService::ACTIVE_CACHE_KEY))->toBeTrue();
    });
});
