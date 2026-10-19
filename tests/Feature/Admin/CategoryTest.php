<?php

use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\User;
use App\Services\Category\CategoryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('uploads');
});

function adminUser(): User
{
    return User::factory()->create();
}

function storedImageSize(string $path): array
{
    return array_slice(getimagesizefromstring(Storage::disk('uploads')->get($path)), 0, 2);
}

function validCategory(array $overrides = []): array
{
    return [
        'name' => 'Kitchenware',
        'slug' => 'kitchenware',
        'headline' => 'Cook with Joy',
        'description' => 'Premium kitchen essentials.',
        'sort_order' => 1,
        'status' => 'active',
        ...$overrides,
    ];
}

function updateRequest(Category $category, array $data): TestResponse
{
    return test()->post(route('admin.categories.update', $category), ['_method' => 'put', ...$data]);
}

describe('access', function () {
    it('redirects guests to the login page', function () {
        $category = Category::factory()->create();

        $this->get(route('admin.categories.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.categories.store'), validCategory())->assertRedirect(route('admin.login'));
        updateRequest($category, ['status' => 'inactive'])->assertRedirect(route('admin.login'));
        $this->delete(route('admin.categories.destroy', $category))->assertRedirect(route('admin.login'));
    });
});

describe('index', function () {
    beforeEach(fn () => $this->actingAs(adminUser()));

    it('lists categories ordered by sort order then name with pagination meta', function () {
        Category::factory()->create(['name' => 'Zeta', 'slug' => 'zeta', 'sort_order' => 1]);
        Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha', 'sort_order' => 2]);
        Category::factory()->create(['name' => 'Beta', 'slug' => 'beta', 'sort_order' => 2]);

        $this->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Categories/Index', false)
                ->has('categories.data', 3)
                ->where('categories.data.0.name', 'Zeta')
                ->where('categories.data.1.name', 'Alpha')
                ->where('categories.data.2.name', 'Beta')
                ->where('categories.meta.total', 3)
                ->where('filters.per_page', 10)
                ->has('statuses', 2));
    });

    it('searches by name', function () {
        Category::factory()->create(['name' => 'Kitchenware', 'slug' => 'kitchenware']);
        Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);

        $this->get(route('admin.categories.index', ['search' => 'kitchen']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('categories.data', 1)
                ->where('categories.data.0.name', 'Kitchenware')
                ->where('filters.search', 'kitchen'));
    });

    it('treats LIKE wildcards in the search term literally', function () {
        Category::factory()->create(['name' => '100% Cotton', 'slug' => 'cotton']);
        Category::factory()->create(['name' => 'Snake_Case', 'slug' => 'snake-case']);
        Category::factory()->create(['name' => 'Plain', 'slug' => 'plain']);

        $this->get(route('admin.categories.index', ['search' => '%']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('categories.data', 1)
                ->where('categories.data.0.name', '100% Cotton'));

        $this->get(route('admin.categories.index', ['search' => '_']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('categories.data', 1)
                ->where('categories.data.0.name', 'Snake_Case'));
    });

    it('filters by status', function () {
        Category::factory()->create(['slug' => 'on']);
        Category::factory()->inactive()->create(['slug' => 'off']);

        $this->get(route('admin.categories.index', ['status' => 'inactive']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('categories.data', 1)
                ->where('categories.data.0.slug', 'off'));
    });

    it('rejects an unknown status filter', function () {
        $this->get(route('admin.categories.index', ['status' => 'archived']))
            ->assertSessionHasErrors('status');
    });

    it('clamps per_page between 10 and 50', function (int $requested, int $expected) {
        Category::factory()->count(3)->create();

        $this->get(route('admin.categories.index', ['per_page' => $requested]))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('categories.meta.per_page', $expected));
    })->with([
        'below minimum' => [1, 10],
        'zero' => [0, 10],
        'negative' => [-5, 10],
        'inside range' => [25, 25],
        'above maximum' => [1000, 50],
    ]);
});

describe('create', function () {
    beforeEach(fn () => $this->actingAs($this->admin = adminUser()));

    it('creates a category and records the creator', function () {
        $this->post(route('admin.categories.store'), validCategory())->assertRedirect();

        $category = Category::where('slug', 'kitchenware')->sole();

        expect($category->status)->toBe(CategoryStatus::Active)
            ->and($category->image)->toBeNull()
            ->and($category->created_by)->toBe($this->admin->id);
    });

    it('builds the slug from the name when none is given', function () {
        $this->post(route('admin.categories.store'), validCategory(['name' => 'Home Decor', 'slug' => '']))
            ->assertSessionHasNoErrors();

        expect(Category::where('slug', 'home-decor')->exists())->toBeTrue();
    });

    it('resizes the uploaded image to the category preset', function () {
        $this->post(route('admin.categories.store'), validCategory([
            'image' => UploadedFile::fake()->image('photo.png', 1600, 900),
        ]))->assertSessionHasNoErrors();

        $path = Category::sole()->image;

        expect($path)->toStartWith('categories/')->toEndWith('.webp')
            ->and(Storage::disk('uploads')->exists($path))->toBeTrue()
            ->and(storedImageSize($path))->toBe([800, 800]);
    });

    it('stores the original image when resizing is disabled', function () {
        config(['media.resize_enabled' => false]);

        $this->post(route('admin.categories.store'), validCategory([
            'image' => UploadedFile::fake()->image('photo.jpg', 1200, 700),
        ]))->assertSessionHasNoErrors();

        $path = Category::sole()->image;

        expect($path)->toStartWith('categories/')->toEndWith('.jpg')
            ->and(storedImageSize($path))->toBe([1200, 700]);
    });

    it('rejects files that are not valid images and raw image paths', function (mixed $image) {
        $this->post(route('admin.categories.store'), validCategory(['image' => $image]))
            ->assertSessionHasErrors('image');

        expect(Category::count())->toBe(0);
    })->with([
        'text file' => fn () => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        'gif image' => fn () => UploadedFile::fake()->image('anim.gif'),
        'oversized file' => fn () => UploadedFile::fake()->image('big.jpg')->size(5000),
        'too many pixels' => fn () => UploadedFile::fake()->image('huge.png', 5001, 10),
        'raw path string' => '/images/categories/evil.jpg',
    ]);

    it('rejects invalid data', function (array $overrides, string $field) {
        $this->post(route('admin.categories.store'), validCategory($overrides))->assertSessionHasErrors($field);
    })->with([
        'missing name' => [['name' => ''], 'name'],
        'bad slug' => [['slug' => 'not valid!'], 'slug'],
        'bad status' => [['status' => 'Active'], 'status'],
        'negative sort order' => [['sort_order' => -1], 'sort_order'],
        'sort order too large' => [['sort_order' => 70000], 'sort_order'],
        'long description' => [['description' => str_repeat('a', 501)], 'description'],
    ]);

    it('rejects a duplicate slug', function () {
        Category::factory()->create(['slug' => 'kitchenware']);

        $this->post(route('admin.categories.store'), validCategory(['name' => 'Other']))
            ->assertSessionHasErrors('slug');
    });

    it('rejects a slug that belongs to a soft-deleted category', function () {
        Category::factory()->create(['name' => 'Old', 'slug' => 'kitchenware'])->delete();

        $this->post(route('admin.categories.store'), validCategory(['name' => 'New']))
            ->assertSessionHasErrors('slug');

        expect(Category::count())->toBe(0);
    });

    it('allows reusing the name of a soft-deleted category with a new slug', function () {
        Category::factory()->create(['name' => 'Kitchenware', 'slug' => 'old-kitchenware'])->delete();

        $this->post(route('admin.categories.store'), validCategory())->assertSessionHasNoErrors();

        expect(Category::count())->toBe(1);
    });
});

describe('update', function () {
    beforeEach(fn () => $this->actingAs($this->admin = adminUser()));

    it('updates fields and records the editor', function () {
        $category = Category::factory()->create(['name' => 'Old', 'slug' => 'old']);

        updateRequest($category, validCategory(['name' => 'Renamed', 'slug' => 'renamed', 'status' => 'inactive']))
            ->assertSessionHasNoErrors();

        $category->refresh();

        expect($category->name)->toBe('Renamed')
            ->and($category->slug)->toBe('renamed')
            ->and($category->status)->toBe(CategoryStatus::Inactive)
            ->and($category->updated_by)->toBe($this->admin->id);
    });

    it('allows a partial update such as a status toggle', function () {
        $category = Category::factory()->create(['name' => 'Keep', 'slug' => 'keep']);

        updateRequest($category, ['status' => 'inactive'])->assertSessionHasNoErrors();

        expect($category->fresh())
            ->status->toBe(CategoryStatus::Inactive)
            ->name->toBe('Keep')
            ->slug->toBe('keep');
    });

    it('keeps its own slug and name without a uniqueness error', function () {
        $category = Category::factory()->create(['name' => 'Same', 'slug' => 'same']);

        updateRequest($category, validCategory(['name' => 'Same', 'slug' => 'same']))->assertSessionHasNoErrors();
    });

    it('replaces the image and deletes the old file', function () {
        $category = Category::factory()->create(['image' => 'categories/old.webp']);
        Storage::disk('uploads')->put('categories/old.webp', 'old-bytes');

        updateRequest($category, validCategory(['image' => UploadedFile::fake()->image('new.jpg', 900, 900)]))
            ->assertSessionHasNoErrors();

        $newPath = $category->fresh()->image;

        expect($newPath)->not->toBe('categories/old.webp')
            ->and(Storage::disk('uploads')->exists($newPath))->toBeTrue()
            ->and(Storage::disk('uploads')->exists('categories/old.webp'))->toBeFalse();
    });

    it('removes the image when asked', function () {
        $category = Category::factory()->create(['image' => 'categories/old.webp']);
        Storage::disk('uploads')->put('categories/old.webp', 'old-bytes');

        updateRequest($category, ['remove_image' => true])->assertSessionHasNoErrors();

        expect($category->fresh()->image)->toBeNull()
            ->and(Storage::disk('uploads')->exists('categories/old.webp'))->toBeFalse();
    });

    it('never deletes static seed images when replacing them', function () {
        $category = Category::factory()->create(['image' => '/images/categories/fashion.jpg']);

        updateRequest($category, validCategory(['image' => UploadedFile::fake()->image('new.jpg')]))
            ->assertSessionHasNoErrors();

        expect($category->fresh()->image)->toStartWith('categories/');
    });

    it('keeps the old image and removes the new upload when the update fails', function () {
        $category = Category::factory()->create(['image' => 'categories/old.webp']);
        Storage::disk('uploads')->put('categories/old.webp', 'old-bytes');
        Category::updating(fn () => throw new RuntimeException('boom'));

        $this->withoutExceptionHandling();

        expect(fn () => updateRequest($category, validCategory(['image' => UploadedFile::fake()->image('new.jpg')])))
            ->toThrow(RuntimeException::class, 'boom');

        expect(Storage::disk('uploads')->allFiles())->toBe(['categories/old.webp'])
            ->and($category->fresh()->image)->toBe('categories/old.webp');
    });

    it('rejects a slug used by another category, including soft-deleted ones', function () {
        Category::factory()->create(['slug' => 'taken'])->delete();
        $category = Category::factory()->create(['slug' => 'mine']);

        updateRequest($category, ['slug' => 'taken'])->assertSessionHasErrors('slug');
    });
});

describe('delete', function () {
    beforeEach(fn () => $this->actingAs($this->admin = adminUser()));

    it('soft deletes, records who deleted it and removes the image file', function () {
        $category = Category::factory()->create(['image' => 'categories/pic.webp']);
        Storage::disk('uploads')->put('categories/pic.webp', 'bytes');

        $this->delete(route('admin.categories.destroy', $category))->assertRedirect();

        $trashed = Category::withTrashed()->findOrFail($category->id);

        expect($trashed->trashed())->toBeTrue()
            ->and($trashed->deleted_by)->toBe($this->admin->id)
            ->and(Storage::disk('uploads')->exists('categories/pic.webp'))->toBeFalse();
    });

    it('does not touch static seed images', function () {
        $category = Category::factory()->create(['image' => '/images/categories/fashion.jpg']);

        $this->delete(route('admin.categories.destroy', $category))->assertRedirect();

        expect(Category::count())->toBe(0);
    });

    it('keeps the image file when the delete fails', function () {
        $category = Category::factory()->create(['image' => 'categories/pic.webp']);
        Storage::disk('uploads')->put('categories/pic.webp', 'bytes');
        Category::deleting(fn () => throw new RuntimeException('boom'));

        $this->withoutExceptionHandling();

        expect(fn () => $this->delete(route('admin.categories.destroy', $category)))
            ->toThrow(RuntimeException::class);

        expect(Storage::disk('uploads')->exists('categories/pic.webp'))->toBeTrue();
    });
});

describe('storefront cache', function () {
    beforeEach(fn () => $this->actingAs(adminUser()));

    function primeCategoryCache(): void
    {
        app(CategoryService::class)->activeForStorefront();
        expect(Cache::has(CategoryService::ACTIVE_CACHE_KEY))->toBeTrue();
    }

    it('caches only active categories as plain arrays', function () {
        Category::factory()->create(['name' => 'Shown', 'slug' => 'shown', 'image' => 'categories/a.webp']);
        Category::factory()->inactive()->create(['name' => 'Hidden', 'slug' => 'hidden']);

        $categories = app(CategoryService::class)->activeForStorefront();

        expect($categories)->toHaveCount(1)
            ->and(Cache::get(CategoryService::ACTIVE_CACHE_KEY))->toBe($categories)
            ->and($categories[0])->toBe([
                'slug' => 'shown',
                'label' => 'Shown',
                'headline' => $categories[0]['headline'],
                'description' => $categories[0]['description'],
                'image' => Storage::disk('uploads')->url('categories/a.webp'),
            ]);
    });

    it('is shared with storefront pages', function () {
        Category::factory()->create(['slug' => 'shown']);
        Category::factory()->inactive()->create(['slug' => 'hidden']);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('categories', 1)
                ->where('categories.0.slug', 'shown'));
    });

    it('is cleared when a category is created', function () {
        primeCategoryCache();

        $this->post(route('admin.categories.store'), validCategory());

        expect(Cache::has(CategoryService::ACTIVE_CACHE_KEY))->toBeFalse();
    });

    it('is cleared when a category is updated', function () {
        $category = Category::factory()->create();
        primeCategoryCache();

        updateRequest($category, ['status' => 'inactive']);

        expect(Cache::has(CategoryService::ACTIVE_CACHE_KEY))->toBeFalse();
    });

    it('is cleared when a category is deleted', function () {
        $category = Category::factory()->create();
        primeCategoryCache();

        $this->delete(route('admin.categories.destroy', $category));

        expect(Cache::has(CategoryService::ACTIVE_CACHE_KEY))->toBeFalse();
    });

    it('is left alone when validation fails', function () {
        primeCategoryCache();

        $this->post(route('admin.categories.store'), validCategory(['name' => '']));

        expect(Cache::has(CategoryService::ACTIVE_CACHE_KEY))->toBeTrue();
    });
});
