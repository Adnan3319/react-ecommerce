<?php

use App\Models\Testimonial;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TestimonialSampleSeeder;

/**
 * Runs the whole DatabaseSeeder as if the app were in the given environment.
 */
function seedDatabaseIn(string $environment): void
{
    config(['admin.username' => 'seed-admin', 'admin.password' => 'Passw0rd!x']);
    app()->detectEnvironment(fn () => $environment);

    // Not `db:seed`: that command asks for confirmation in production.
    (new DatabaseSeeder)->setContainer(app())->run();
}

describe('environments', function () {
    it('creates no testimonials when seeding production', function () {
        seedDatabaseIn('production');

        expect(app()->environment('production'))->toBeTrue()
            ->and(Testimonial::withTrashed()->count())->toBe(0);
    });

    it('creates no testimonials when seeding the testing or staging environment', function (string $environment) {
        seedDatabaseIn($environment);

        expect(Testimonial::withTrashed()->count())->toBe(0);
    })->with(['testing', 'staging']);

    it('creates the 3 sample testimonials when seeding local', function () {
        seedDatabaseIn('local');

        expect(Testimonial::count())->toBe(3);
    });
});

describe('samples', function () {
    it('are clearly marked as samples', function () {
        $this->seed(TestimonialSampleSeeder::class);

        expect(Testimonial::pluck('customer_name')->every(fn (string $name) => str_starts_with($name, '[Sample] ')))->toBeTrue();
    });

    it('are sorted, rated 1-5 and active', function () {
        $this->seed(TestimonialSampleSeeder::class);

        $all = Testimonial::query()->ordered()->get();
        expect($all->pluck('sort_order')->all())->toBe([1, 2, 3])
            ->and($all->every(fn (Testimonial $t) => $t->rating >= 1 && $t->rating <= 5))->toBeTrue()
            ->and($all->every(fn (Testimonial $t) => $t->status->value === 'active'))->toBeTrue();
    });

    it('are not duplicated when the seeder runs twice', function () {
        $this->seed(TestimonialSampleSeeder::class);
        $this->seed(TestimonialSampleSeeder::class);

        expect(Testimonial::count())->toBe(3);
    });
});
