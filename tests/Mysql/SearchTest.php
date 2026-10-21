<?php

use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia;

/*
 * FULLTEXT behaviour on real MySQL. InnoDB only indexes committed rows, so these run in the
 * truncation group; words shorter than 3 characters and stopwords must still match via LIKE.
 */

beforeEach(function () {
    $this->category = Category::factory()->create(['slug' => 'home']);
});

function mysqlProduct(string $name, string $description = 'Plain description'): Product
{
    return Product::factory()->simple()->create([
        'name' => $name,
        'short_description' => $description,
        'category_id' => test()->category->id,
    ]);
}

function mysqlSearchNames(string $query): array
{
    return collect(test()->get('/search?'.$query)->viewData('page')['props']['products']['data'])->pluck('name')->all();
}

it('ranks a name hit above a description-only hit', function () {
    mysqlProduct('Cotton Shirt', 'Soft and light');
    mysqlProduct('Plain Tee', 'Made of pure cotton');

    expect(mysqlSearchNames('q=cotton'))->toBe(['Cotton Shirt', 'Plain Tee']);
});

it('finds "tv stand" in "TV Stand Wooden" although "tv" is too short for FULLTEXT', function () {
    mysqlProduct('TV Stand Wooden');
    mysqlProduct('Wooden Chair');
    mysqlProduct('Hi-Fi Stand');

    expect(mysqlSearchNames('q='.urlencode('tv stand')))->toBe(['TV Stand Wooden']);
});

it('still requires a stopword like "the" to appear in the name, via LIKE', function () {
    mysqlProduct('Summer Dress');
    mysqlProduct('Summer Hat');

    expect(mysqlSearchNames('q='.urlencode('the dress')))->toBe([]);
    mysqlProduct('The Dress Shop Bag');

    expect(mysqlSearchNames('q='.urlencode('the dress')))->toBe(['The Dress Shop Bag']);
});

it('matches word prefixes', function () {
    mysqlProduct('Summer Dress');

    expect(mysqlSearchNames('q=dre'))->toBe(['Summer Dress']);
});

it('searches by LIKE alone when no word qualifies for FULLTEXT', function () {
    mysqlProduct('TV Box');
    mysqlProduct('Tea Pot');

    expect(mysqlSearchNames('q=tv'))->toBe(['TV Box']);
});

it('shows the too-short prompt for a single character', function () {
    mysqlProduct('Mug');

    $this->get('/search?q=m')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('filters.active.q_too_short', true));
});
