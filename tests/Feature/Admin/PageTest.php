<?php

use App\Models\Page;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
});

function pagePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'About Us',
        'sub_title' => 'Who we are',
        'slug' => 'about-us',
        'content' => '<p>Hello</p>',
        'status' => 'active',
    ], $overrides);
}

it('creates a page with an image', function () {
    actingAsAdmin()->post(route('admin.pages.store'), pagePayload(['image' => fakeImage(800, 600)]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $page = Page::firstWhere('slug', 'about-us');

    expect($page->image)->not->toBeNull();
    Storage::disk('uploads')->assertExists($page->image);
});

it('rejects a duplicate slug', function () {
    Page::factory()->create(['slug' => 'about-us']);

    actingAsAdmin()->post(route('admin.pages.store'), pagePayload())
        ->assertSessionHasErrors('slug');
});

it('replaces the image and deletes the old file', function () {
    $page = Page::factory()->create(['slug' => 'about-us', 'image' => 'pages/old.webp']);
    Storage::disk('uploads')->put('pages/old.webp', 'x');

    actingAsAdmin()->put(route('admin.pages.update', $page), pagePayload(['image' => fakeImage(800, 600)]))->assertRedirect();

    Storage::disk('uploads')->assertMissing('pages/old.webp');
    expect($page->fresh()->image)->not->toBe('pages/old.webp');
});

it('removes the image when asked', function () {
    $page = Page::factory()->create(['slug' => 'about-us', 'image' => 'pages/old.webp']);
    Storage::disk('uploads')->put('pages/old.webp', 'x');

    actingAsAdmin()->put(route('admin.pages.update', $page), pagePayload(['remove_image' => true]))->assertRedirect();

    expect($page->fresh()->image)->toBeNull();
    Storage::disk('uploads')->assertMissing('pages/old.webp');
});

it('keeps the image when the update sends none', function () {
    $page = Page::factory()->create(['slug' => 'about-us', 'image' => 'pages/keep.webp']);

    actingAsAdmin()->put(route('admin.pages.update', $page), pagePayload())->assertRedirect();

    expect($page->fresh()->image)->toBe('pages/keep.webp');
});

it('filters the list by status together with the search text', function () {
    Page::factory()->create(['title' => 'Shipping', 'slug' => 'shipping']);
    Page::factory()->inactive()->create(['title' => 'Shipping old', 'slug' => 'shipping-old']);

    actingAsAdmin()->get(route('admin.pages.index', ['search' => 'Shipping', 'status' => 'active']))
        ->assertInertia(fn ($page) => $page->has('pages.data', 1)->where('pages.data.0.slug', 'shipping'));
});

it('deletes a page and its image', function () {
    $page = Page::factory()->create(['image' => 'pages/gone.webp']);
    Storage::disk('uploads')->put('pages/gone.webp', 'x');

    actingAsAdmin()->delete(route('admin.pages.destroy', $page))->assertRedirect();

    expect(Page::count())->toBe(0);
    Storage::disk('uploads')->assertMissing('pages/gone.webp');
});
