<?php

use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;

it('shows an active page with its content and seo', function () {
    Page::factory()->create(['title' => 'About Us', 'slug' => 'about-us', 'sub_title' => 'Who we are', 'content' => '<p>Hello</p>']);

    $this->get(route('pages.show', 'about-us'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Pages/Show', false)
            ->where('page.title', 'About Us')
            ->where('page.content', '<p>Hello</p>')
            ->where('page.image_url', null)
            ->where('seo.canonical', route('pages.show', 'about-us'))
            ->where('seo.description', 'Who we are')
            ->where('seo.noindex', false));
});

it('returns 404 for an inactive page', function () {
    Page::factory()->inactive()->create(['slug' => 'hidden']);

    $this->get(route('pages.show', 'hidden'))->assertNotFound();
});

it('returns 404 for an unknown page', function () {
    $this->get(route('pages.show', 'missing'))->assertNotFound();
});

it('serves the page from cache until the admin saves it', function () {
    $page = Page::factory()->create(['slug' => 'about-us', 'title' => 'Old']);
    $this->get(route('pages.show', 'about-us'))->assertOk();

    expect(Cache::has('pages.about-us'))->toBeTrue();

    actingAsAdmin()->put(route('admin.pages.update', $page), [
        'title' => 'New',
        'slug' => 'about-us',
        'status' => 'active',
    ])->assertRedirect();

    $this->get(route('pages.show', 'about-us'))
        ->assertInertia(fn (AssertableInertia $inertia) => $inertia->where('page.title', 'New'));
});
