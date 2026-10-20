# Testing Convention

> Read this file BEFORE writing any test. CLAUDE.md points here.
> Generic rules only. Feature-specific test scope is given with each task.

Stack: Laravel 13 (standard structure, not modular), Inertia 3 + React, Pest,
MySQL in production, SQLite in-memory for tests. Session auth on the `web` guard
(admin only). No roles/permissions yet — every active admin can do everything.

---

## 1. Golden rules

1. Test real behavior: real routes, middleware, validation, DB. Mock only external
   services (mail, HTTP, courier/payment APIs later) — never our own Services or Models.
2. One test = one behavior. The name reads as a sentence.
3. Arrange → Act → Assert, separated by one blank line each.
4. Every test is independent: passes alone and in any order. No shared state.
5. No flakiness: no `sleep()`, no real clock (`$this->freezeTime()` / `$this->travel()`),
   no real disk (`Storage::fake('uploads')`), no real mail (`Mail::fake()`).
6. Setup via factories (with states) and helpers — never copy-paste long arrange blocks.
7. Comment only a non-obvious WHY, never WHAT.

---

## 2. Unit vs Feature

- **HTTP request involved** (route, middleware, controller) → `tests/Feature`
- **Only calling a class method** (service, model, enum, helper) → `tests/Unit`

"Unit" means no HTTP, not no DB — unit tests may use the DB.

| What | Folder |
|---|---|
| Admin CRUD pages, validation, redirects, flash | `Feature/Admin/` |
| Storefront pages, order placement, cart | `Feature/Storefront/` |
| Auth (login, logout, throttle, guest redirect) | `Feature/Auth/` |
| Service logic (price calc, stock, cache, upload) | `Unit/Services/` |
| Model scopes, casts, relations, enum transitions | `Unit/Models/`, `Unit/Enums/` |
| Seeder idempotency | `Unit/Seeders/` |

File name: `<Thing>Test.php` — `CategoryTest`, `ProductVariantTest`, `CheckoutServiceTest`.
Split a file when it passes ~300 lines: `ProductTest` (CRUD) + `ProductValidationTest`.

---

## 3. Database & environment

- Feature tests use `RefreshDatabase` (set once in `tests/Pest.php`). Never truncate manually.
- Tests run on SQLite. Anything MySQL-only (FULLTEXT search, MySQL JSON functions)
  goes in `->group('mysql')` and is skipped on SQLite. Do not change app code to please SQLite.
- Cache and session use the `array` driver and queue uses the `sync` driver in tests (`phpunit.xml`).
- Files: `Storage::fake('uploads')` in `beforeEach` of any test that uploads.
- Queued jobs: `Queue::fake()` and assert pushed; run the job class directly in its own Unit test.

---

## 4. Shared helpers (`tests/Pest.php`)

```php
function actingAsAdmin(?User $admin = null): TestCase   // login on web guard, returns test case
function fakeImage(int $width, int $height, int $kb = 100): UploadedFile
```

- Per-file payload helpers live in the test file and are prefixed with the feature
  (`categoryPayload()`, `productPayload()`) — Pest helpers are global, names must not clash.
- Factory states over inline attributes: `User::factory()->inactive()`,
  `Product::factory()->withVariants(3)`, `Category::factory()->inactive()`.

---

## 5. Naming (Pest)

```php
describe('create', function () {
    it('stores a category with a resized logo', ...);
    it('rejects a logo smaller than 300px', ...);
});
```

- `describe()` = the action or area (`access`, `list`, `create`, `update`, `delete`, `cache`).
- `it()` = subject + expected behavior, present tense, no "should".
- Validation cases use datasets with readable keys: `'missing name' => [...]`.

---

## 6. Inertia assertions

| Situation | Assert |
|---|---|
| Page renders | `->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Categories/Index')->has('categories.data', 2))` |
| Successful write | `->assertRedirect()->assertSessionHasNoErrors()` + DB state |
| Validation fails | `->assertSessionHasErrors('field')` (not 422 — Inertia redirects back) + DB unchanged |
| Flash message | assert it the same way existing `CategoryTest` does |
| Prop shape | assert only the keys the page uses; never dump the whole prop |
| JSON endpoint (search suggest, webhook) | `->assertOk()->assertJsonPath('data.0.name', ...)` |

---

## 7. Access matrix (every admin route)

No roles yet, so the matrix is:

| Actor | Expected |
|---|---|
| Guest | redirect to `route('admin.login')` |
| Inactive admin | cannot log in (same generic error as wrong password) |
| Active admin | 2xx / redirect back on success |

When roles/permissions are added, this section is extended — do not add permission
tests before that.

---

## 8. What every admin feature must cover

- **Access:** the §7 matrix for the index route + one write route.
- **CRUD happy path:** list (with each filter), create, update, delete (soft delete).
- **Validation:** one dataset covering every rule that protects data (required,
  unique incl. soft-deleted, size/dimension limits, cross-field rules like
  `sale_price < price`). Assert the DB did not change.
- **Not found:** editing/deleting a missing id → 404.
- **Audit:** `created_by` on create, `updated_by` on update, `deleted_by` on delete.
  Read soft-deleted rows with `withTrashed()` — `fresh()` returns null.
- **Files:** every preset file exists after upload (incl. `-sm` / `-thumb` variants);
  old files removed after replace and after delete.
- **Cache:** the feature's cache key is cleared on create/update/delete, and only that key.

## 9. Business rules (e-commerce)

Always unit-test these in the Service, with exact numbers:

- Money is stored in minor units: input `1650` taka → DB `165000`.
- Totals are recalculated server-side; a tampered client price is ignored.
- Stock never goes below zero; two orders for the last item → only one succeeds.
- Enum transitions: every allowed transition passes, every forbidden one throws.
- Phone validation: valid `01XXXXXXXXX` passes; dataset of invalid formats fails.

---

## 10. Running

```bash
php artisan test --compact                      # everything
php artisan test --compact --filter=CategoryTest
php artisan test --compact --exclude-group=mysql
```

A task is done only when `php artisan test`, `npx tsc --noEmit` and `vp lint` all exit 0.

---

## 11. Reference example

`tests/Feature/Admin/CategoryTest.php` (shortened):

```php
<?php

use App\Enums\ActiveStatus;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('uploads');
});

function categoryPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Kitchenware',
        'status' => ActiveStatus::Active->value,
        'sort_order' => 1,
        'image' => fakeImage(500, 500),
    ], $overrides);
}

describe('access', function () {
    it('redirects guests to the admin login', function () {
        $response = $this->get(route('admin.categories.index'));

        $response->assertRedirect(route('admin.login'));
    });

    it('shows the category list to an admin', function () {
        Category::factory()->count(2)->create();

        $response = actingAsAdmin()->get(route('admin.categories.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Categories/Index')
            ->has('categories.data', 2));
    });
});

describe('create', function () {
    it('stores a category with a 300x300 webp logo', function () {
        $payload = categoryPayload();

        actingAsAdmin()->post(route('admin.categories.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $category = Category::sole();
        expect($category->slug)->toBe('kitchenware');
        Storage::disk('uploads')->assertExists($category->image);
    });

    it('rejects invalid input', function (array $overrides, string $field) {
        $payload = categoryPayload($overrides);

        $response = actingAsAdmin()->post(route('admin.categories.store'), $payload);

        $response->assertSessionHasErrors($field);
        expect(Category::count())->toBe(0);
    })->with([
        'missing name' => [['name' => ''], 'name'],
        'logo smaller than 300px' => [['image' => fakeImage(200, 200)], 'image'],
        'logo larger than 1000px' => [['image' => fakeImage(1200, 1200)], 'image'],
        'logo over 512KB' => [['image' => fakeImage(500, 500, 600)], 'image'],
    ]);

    it('rejects a slug used by a soft-deleted category', function () {
        Category::factory()->create(['slug' => 'kitchenware'])->delete();

        $response = actingAsAdmin()->post(route('admin.categories.store'), categoryPayload());

        $response->assertSessionHasErrors('slug');
    });
});

describe('delete', function () {
    it('soft deletes the category and records who deleted it', function () {
        $admin = User::factory()->create();
        $category = Category::factory()->create();

        actingAsAdmin($admin)->delete(route('admin.categories.destroy', $category));

        $this->assertSoftDeleted($category);
        expect(Category::withTrashed()->find($category->id)->deleted_by)->toBe($admin->id);
    });
});

describe('cache', function () {
    it('clears only the category cache on update', function () {
        $category = Category::factory()->create();
        Cache::put('categories.active', ['stale']);
        Cache::put('sliders.active', ['keep']);

        actingAsAdmin()->put(route('admin.categories.update', $category), categoryPayload(['name' => 'Kitchen']));

        expect(Cache::has('categories.active'))->toBeFalse()
            ->and(Cache::has('sliders.active'))->toBeTrue();
    });
});
```

---

## Checklist before finishing a test file

- [ ] Right folder: Feature = HTTP, Unit = no HTTP.
- [ ] `Storage::fake` / `Mail::fake` / `Queue::fake` / frozen time where relevant.
- [ ] AAA with blank lines; one behavior per test; runs alone.
- [ ] §7 access matrix for admin routes.
- [ ] §8 coverage: CRUD, validation dataset, 404, audit, files, cache.
- [ ] §9 business rules with exact numbers where the feature touches money, stock or status.
- [ ] Sentence-style `describe` / `it` names; helpers prefixed by feature.
- [ ] `php artisan test`, `tsc`, `vp lint` all exit 0.
