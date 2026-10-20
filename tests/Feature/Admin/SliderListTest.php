<?php

use App\Models\Slider;
use Inertia\Testing\AssertableInertia as Assert;

describe('list', function () {
    it('paginates sliders and reports the page meta', function () {
        Slider::factory()->count(12)->create();

        $response = actingAsAdmin()->get(route('admin.sliders.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Sliders/Index', false)
            ->has('sliders.data', 10)
            ->where('sliders.meta.total', 12)
            ->where('sliders.meta.last_page', 2)
            ->where('filters.per_page', 10)
            ->where('filters.search', null)
            ->where('filters.status', null));
    });

    it('shows the remaining sliders on the second page', function () {
        Slider::factory()->count(12)->create();

        $response = actingAsAdmin()->get(route('admin.sliders.index', ['page' => 2]));

        $response->assertInertia(fn (Assert $page) => $page->has('sliders.data', 2));
    });
});

describe('search', function () {
    it('finds sliders by title', function () {
        Slider::factory()->create(['title' => 'Summer Sale']);
        Slider::factory()->create(['title' => 'New Arrivals']);

        $response = actingAsAdmin()->get(route('admin.sliders.index', ['search' => 'summer']));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('sliders.data', 1)
            ->where('sliders.data.0.title', 'Summer Sale')
            ->where('filters.search', 'summer'));
    });

    it('treats LIKE wildcards in the term literally', function (string $term, string $expected) {
        Slider::factory()->create(['title' => '100% Cotton']);
        Slider::factory()->create(['title' => 'Snake_Case']);
        Slider::factory()->create(['title' => 'Plain']);

        $response = actingAsAdmin()->get(route('admin.sliders.index', ['search' => $term]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('sliders.data', 1)
            ->where('sliders.data.0.title', $expected));
    })->with([
        'percent sign' => ['%', '100% Cotton'],
        'underscore' => ['_', 'Snake_Case'],
    ]);

    it('returns nothing when no title matches', function () {
        Slider::factory()->create(['title' => 'Summer Sale']);

        $response = actingAsAdmin()->get(route('admin.sliders.index', ['search' => 'winter']));

        $response->assertInertia(fn (Assert $page) => $page->has('sliders.data', 0));
    });
});

describe('status filter', function () {
    it('lists only sliders with the chosen status', function (string $status, string $expected) {
        Slider::factory()->create(['title' => 'On']);
        Slider::factory()->inactive()->create(['title' => 'Off']);

        $response = actingAsAdmin()->get(route('admin.sliders.index', ['status' => $status]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('sliders.data', 1)
            ->where('sliders.data.0.title', $expected)
            ->where('filters.status', $status));
    })->with([
        'active' => ['active', 'On'],
        'inactive' => ['inactive', 'Off'],
    ]);

    it('rejects an unknown status', function () {
        $response = actingAsAdmin()->get(route('admin.sliders.index', ['status' => 'archived']));

        $response->assertSessionHasErrors('status');
    });
});

describe('rows per page', function () {
    it('accepts only 10, 20 or 50 and falls back to 10', function (int $requested, int $expected) {
        Slider::factory()->count(3)->create();

        $response = actingAsAdmin()->get(route('admin.sliders.index', ['per_page' => $requested]));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('sliders.meta.per_page', $expected)
            ->where('filters.per_page', $expected));
    })->with([
        'ten' => [10, 10],
        'twenty' => [20, 20],
        'fifty' => [50, 50],
        'zero' => [0, 10],
        'negative' => [-5, 10],
        'not an option' => [25, 10],
        'above the maximum' => [1000, 10],
    ]);

    it('shows more rows per page when asked', function () {
        Slider::factory()->count(15)->create();

        $response = actingAsAdmin()->get(route('admin.sliders.index', ['per_page' => 20]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('sliders.data', 15)
            ->where('sliders.meta.last_page', 1));
    });
});

describe('url params', function () {
    it('combines search, status and rows per page', function () {
        Slider::factory()->count(3)->create(['title' => 'Eid Offer']);
        Slider::factory()->count(2)->inactive()->create(['title' => 'Eid Offer']);
        Slider::factory()->create(['title' => 'Other']);

        $response = actingAsAdmin()->get(route('admin.sliders.index', [
            'search' => 'eid',
            'status' => 'active',
            'per_page' => 20,
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('sliders.data', 3)
            ->where('sliders.meta.per_page', 20));
    });

    it('keeps the filters in the pagination links', function () {
        Slider::factory()->count(12)->create(['title' => 'Eid Offer']);

        $response = actingAsAdmin()->get(route('admin.sliders.index', [
            'search' => 'Eid',
            'status' => 'active',
            'per_page' => 10,
        ]));

        $response->assertInertia(function (Assert $page) {
            $next = $page->toArray()['props']['sliders']['links']['next'];

            expect($next)->toContain('search=Eid')
                ->toContain('status=active')
                ->toContain('per_page=10')
                ->toContain('page=2');
        });
    });
});

describe('access', function () {
    it('redirects guests to the admin login', function () {
        $response = $this->get(route('admin.sliders.index', ['search' => 'a']));

        $response->assertRedirect(route('admin.login'));
    });
});
