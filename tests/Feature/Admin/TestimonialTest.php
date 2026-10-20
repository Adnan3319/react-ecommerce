<?php

use App\Enums\ActiveStatus;
use App\Enums\TestimonialSource;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\Testimonial\TestimonialService;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validTestimonial(array $overrides = []): array
{
    return [
        'customer_name' => 'Nusrat Jahan',
        'text' => 'The dress fits perfectly and arrived in two days.',
        'rating' => 5,
        'source' => 'facebook',
        'status' => 'active',
        'sort_order' => 1,
        ...$overrides,
    ];
}

describe('access', function () {
    it('redirects guests to the login page', function () {
        $testimonial = Testimonial::factory()->create();

        $this->get(route('admin.testimonials.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.testimonials.store'), validTestimonial())->assertRedirect(route('admin.login'));
        $this->put(route('admin.testimonials.update', $testimonial), ['status' => 'inactive'])->assertRedirect(route('admin.login'));
        $this->delete(route('admin.testimonials.destroy', $testimonial))->assertRedirect(route('admin.login'));

        expect(Testimonial::count())->toBe(1);
    });

    it('lets an active admin open the list', function () {
        actingAsAdmin();

        $this->get(route('admin.testimonials.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Testimonials/Index', false)
            ->has('sources', 4)
            ->where('limits', ['text' => 500, 'min_rating' => 1, 'max_rating' => 5]));
    });
});

describe('list', function () {
    beforeEach(fn () => actingAsAdmin());

    it('lists testimonials by sort order then id with their source label', function () {
        Testimonial::factory()->create(['customer_name' => 'Second', 'sort_order' => 2, 'source' => TestimonialSource::Whatsapp]);
        Testimonial::factory()->create(['customer_name' => 'First', 'sort_order' => 1]);

        $this->get(route('admin.testimonials.index'))->assertInertia(fn (Assert $page) => $page
            ->where('testimonials.data.0.customer_name', 'First')
            ->where('testimonials.data.1.source_label', 'WhatsApp'));
    });

    it('searches by customer name, treating LIKE wildcards literally', function () {
        Testimonial::factory()->create(['customer_name' => 'Nusrat Jahan']);
        Testimonial::factory()->create(['customer_name' => '100% Happy']);

        $names = fn (array $query) => collect($this->get(route('admin.testimonials.index', $query))->viewData('page')['props']['testimonials']['data'])->pluck('customer_name')->all();

        expect($names(['search' => 'nusrat']))->toBe(['Nusrat Jahan'])
            ->and($names(['search' => '%']))->toBe(['100% Happy']);
    });

    it('filters by status', function () {
        Testimonial::factory()->create(['customer_name' => 'Shown']);
        Testimonial::factory()->inactive()->create(['customer_name' => 'Hidden']);

        $this->get(route('admin.testimonials.index', ['status' => 'inactive']))->assertInertia(fn (Assert $page) => $page
            ->has('testimonials.data', 1)
            ->where('testimonials.data.0.customer_name', 'Hidden'));
    });

    it('paginates and falls back to 10 rows for an unknown size', function () {
        Testimonial::factory()->count(12)->create();

        $this->get(route('admin.testimonials.index', ['per_page' => 7]))->assertInertia(fn (Assert $page) => $page
            ->has('testimonials.data', 10)
            ->where('testimonials.meta.total', 12));
    });
});

describe('create', function () {
    beforeEach(fn () => actingAsAdmin());

    it('creates a testimonial and records the creator', function () {
        $admin = User::query()->latest('id')->first();

        $this->post(route('admin.testimonials.store'), validTestimonial())->assertRedirect()->assertSessionHasNoErrors();

        $testimonial = Testimonial::sole();
        expect($testimonial->customer_name)->toBe('Nusrat Jahan')
            ->and($testimonial->rating)->toBe(5)
            ->and($testimonial->source)->toBe(TestimonialSource::Facebook)
            ->and($testimonial->status)->toBe(ActiveStatus::Active)
            ->and($testimonial->created_by)->toBe($admin->id);
    });

    it('rejects invalid data and writes nothing', function (array $overrides, string $field) {
        $this->post(route('admin.testimonials.store'), validTestimonial($overrides))->assertSessionHasErrors($field);

        expect(Testimonial::count())->toBe(0);
    })->with([
        'missing name' => [['customer_name' => ''], 'customer_name'],
        'one letter name' => [['customer_name' => 'A'], 'customer_name'],
        'name over 80' => [['customer_name' => str_repeat('a', 81)], 'customer_name'],
        'missing text' => [['text' => ''], 'text'],
        'text over 500' => [['text' => str_repeat('a', 501)], 'text'],
        'rating 0' => [['rating' => 0], 'rating'],
        'rating 6' => [['rating' => 6], 'rating'],
        'rating text' => [['rating' => 'great'], 'rating'],
        'missing rating' => [['rating' => null], 'rating'],
        'unknown source' => [['source' => 'twitter'], 'source'],
        'unknown status' => [['status' => 'pending'], 'status'],
        'negative order' => [['sort_order' => -1], 'sort_order'],
    ]);

    it('accepts text of exactly 500 characters and every rating from 1 to 5', function (int $rating) {
        $this->post(route('admin.testimonials.store'), validTestimonial(['text' => str_repeat('a', 500), 'rating' => $rating]))
            ->assertSessionHasNoErrors();

        expect(Testimonial::sole()->rating)->toBe($rating);
    })->with([1, 2, 3, 4, 5]);
});

describe('update', function () {
    beforeEach(fn () => actingAsAdmin());

    it('updates fields and records the editor', function () {
        $testimonial = Testimonial::factory()->create();
        $admin = User::query()->latest('id')->first();

        $this->put(route('admin.testimonials.update', $testimonial), validTestimonial(['customer_name' => 'Rafiq Ahmed', 'rating' => 4]))
            ->assertRedirect()->assertSessionHasNoErrors();

        $fresh = $testimonial->fresh();
        expect($fresh->customer_name)->toBe('Rafiq Ahmed')
            ->and($fresh->rating)->toBe(4)
            ->and($fresh->updated_by)->toBe($admin->id);
    });

    it('allows a partial update such as a status toggle', function () {
        $testimonial = Testimonial::factory()->create(['customer_name' => 'Keep me']);

        $this->put(route('admin.testimonials.update', $testimonial), ['status' => 'inactive'])->assertSessionHasNoErrors();

        expect($testimonial->fresh()->status)->toBe(ActiveStatus::Inactive)
            ->and($testimonial->fresh()->customer_name)->toBe('Keep me');
    });

    it('validates on update too', function () {
        $testimonial = Testimonial::factory()->create(['rating' => 3]);

        $this->put(route('admin.testimonials.update', $testimonial), ['rating' => 9])->assertSessionHasErrors('rating');

        expect($testimonial->fresh()->rating)->toBe(3);
    });

    it('answers 404 for a missing testimonial', function () {
        $this->put(route('admin.testimonials.update', 999), ['status' => 'inactive'])->assertNotFound();
    });
});

describe('delete', function () {
    beforeEach(fn () => actingAsAdmin());

    it('soft deletes and records who deleted it', function () {
        $testimonial = Testimonial::factory()->create();
        $admin = User::query()->latest('id')->first();

        $this->delete(route('admin.testimonials.destroy', $testimonial))->assertRedirect();

        $deleted = Testimonial::withTrashed()->find($testimonial->id);
        expect(Testimonial::count())->toBe(0)
            ->and($deleted->deleted_at)->not->toBeNull()
            ->and($deleted->deleted_by)->toBe($admin->id);
    });

    it('answers 404 for a missing testimonial', function () {
        $this->delete(route('admin.testimonials.destroy', 999))->assertNotFound();
    });
});

describe('storefront cache', function () {
    beforeEach(fn () => actingAsAdmin());

    function primeTestimonialCache(): void
    {
        app(TestimonialService::class)->activeForStorefront();
        expect(Cache::has(TestimonialService::ACTIVE_CACHE_KEY))->toBeTrue();
    }

    it('caches only active testimonials in order as plain arrays', function () {
        $later = Testimonial::factory()->create(['customer_name' => 'Later', 'sort_order' => 2, 'source' => TestimonialSource::Messenger]);
        $first = Testimonial::factory()->create(['customer_name' => 'First', 'sort_order' => 1]);
        Testimonial::factory()->inactive()->create(['customer_name' => 'Hidden']);

        $list = app(TestimonialService::class)->activeForStorefront();

        expect($list)->toHaveCount(2)
            ->and(Cache::get(TestimonialService::ACTIVE_CACHE_KEY))->toBe($list)
            ->and($list[0])->toBe([
                'id' => $first->id,
                'customer_name' => 'First',
                'text' => $first->text,
                'rating' => $first->rating,
                'source' => 'facebook',
                'source_label' => 'Facebook',
            ])
            ->and($list[1]['id'])->toBe($later->id)
            ->and($list[1]['source_label'])->toBe('Messenger');
    });

    it('returns at most 6 testimonials', function () {
        Testimonial::factory()->count(8)->create();

        expect(app(TestimonialService::class)->activeForStorefront())->toHaveCount(6);
    });

    it('is empty without active testimonials so the section can be hidden', function () {
        Testimonial::factory()->inactive()->create();

        expect(app(TestimonialService::class)->activeForStorefront())->toBe([]);
    });

    it('is shared with the Home page as the testimonials prop', function () {
        Testimonial::factory()->create(['customer_name' => 'Shown']);
        Testimonial::factory()->inactive()->create(['customer_name' => 'Hidden']);

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->has('testimonials', 1)
            ->where('testimonials.0.customer_name', 'Shown'));
    });

    it('is cleared when a testimonial is created, updated or deleted', function (Closure $write) {
        $testimonial = Testimonial::factory()->create();
        primeTestimonialCache();

        $write($testimonial);

        expect(Cache::has(TestimonialService::ACTIVE_CACHE_KEY))->toBeFalse();
    })->with([
        'create' => [fn () => test()->post(route('admin.testimonials.store'), validTestimonial())],
        'update' => [fn (Testimonial $t) => test()->put(route('admin.testimonials.update', $t), ['status' => 'inactive'])],
        'delete' => [fn (Testimonial $t) => test()->delete(route('admin.testimonials.destroy', $t))],
    ]);

    it('is left alone when validation fails', function () {
        primeTestimonialCache();

        $this->post(route('admin.testimonials.store'), validTestimonial(['rating' => 0]));

        expect(Cache::has(TestimonialService::ACTIVE_CACHE_KEY))->toBeTrue();
    });

    it('does not touch the other domain caches', function () {
        Cache::put('sliders.active.hero', ['keep']);
        Cache::put('categories.active', ['keep']);

        $this->post(route('admin.testimonials.store'), validTestimonial());

        expect(Cache::get('sliders.active.hero'))->toBe(['keep'])
            ->and(Cache::get('categories.active'))->toBe(['keep']);
    });
});
