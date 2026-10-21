<?php

use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->fashion = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);
    $this->kitchen = Category::factory()->create(['name' => 'Kitchenware', 'slug' => 'kitchenware']);
    Product::factory()->simple()->create(['name' => 'Dress', 'category_id' => $this->fashion->id]);
    Product::factory()->simple()->create(['name' => 'Pot', 'category_id' => $this->kitchen->id]);
});

it('renders the Search page with the same props as a category page', function () {
    $this->get(route('search'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Search', false)
        ->where('category', null)
        ->has('products.data', 2)
        ->has('products.meta')
        ->has('filters.available.price')
        ->has('filters.available.heads')
        ->has('filters.active')
        ->has('sortOptions', 3)
        ->has('seo'));
});

it('is never indexable and always canonical to /search', function () {
    $seo = $this->get('/search?q=dress&category=fashion&page=2')->viewData('page')['props']['seo'];

    expect($seo['noindex'])->toBeTrue()
        ->and($seo['canonical'])->toBe(route('search'));
});

it('keeps the category and the term in the active filters', function () {
    $this->get('/search?q=dress&category=fashion')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('category.slug', 'fashion')
        ->where('filters.active.q', 'dress')
        ->where('filters.active.category', 'fashion')
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Dress'));
});

it('ignores an unknown or inactive category', function () {
    Category::factory()->inactive()->create(['slug' => 'off']);

    $this->get('/search?category=off')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('category', null)
        ->has('products.data', 2));
    $this->get('/search?category=nothing')->assertInertia(fn (AssertableInertia $page) => $page->has('products.data', 2));
});

it('applies the shared filters and sorting', function () {
    Product::factory()->simple(900000)->create(['name' => 'Gown', 'category_id' => $this->fashion->id]);

    $this->get('/search?category=fashion&min=2000&sort=price_desc')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Gown'));
});

it('never lists products of inactive categories', function () {
    $off = Category::factory()->inactive()->create();
    Product::factory()->simple()->create(['name' => 'Hidden', 'category_id' => $off->id]);

    $this->get(route('search'))->assertInertia(fn (AssertableInertia $page) => $page->has('products.data', 2));
});
