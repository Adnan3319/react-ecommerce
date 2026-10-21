<?php

use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->fashion = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);
    $this->kitchen = Category::factory()->create(['name' => 'Kitchenware', 'slug' => 'kitchenware']);
});

function searchProduct(string $name, ?Category $category = null, array $attributes = []): Product
{
    return Product::factory()->simple()->create([
        'name' => $name,
        'short_description' => 'Plain description',
        'category_id' => ($category ?? test()->fashion)->id,
        ...$attributes,
    ]);
}

function searchNames(string $query): array
{
    return collect(test()->get('/search?'.$query)->viewData('page')['props']['products']['data'])->pluck('name')->all();
}

describe('matching', function () {
    it('finds a product by a word of its name', function () {
        searchProduct('Summer Dress');
        searchProduct('Steel Pot', $this->kitchen);

        expect(searchNames('q=dress'))->toBe(['Summer Dress']);
    });

    it('finds a product by a word of its short description', function () {
        searchProduct('Maxi', attributes: ['short_description' => 'A flowing linen gown']);

        expect(searchNames('q=linen'))->toBe(['Maxi']);
    });

    it('requires every word of the term', function () {
        searchProduct('Summer Dress');
        searchProduct('Winter Dress');

        expect(searchNames('q=summer+dress'))->toBe(['Summer Dress']);
    });

    it('finds the product of an exact variant SKU', function () {
        $product = searchProduct('Plain Tee');
        searchProduct('Other Tee');
        $sku = $product->variants()->sole()->sku;

        expect(searchNames('q='.urlencode($sku)))->toBe(['Plain Tee']);
    });

    it('does not find a product by the SKU of a deleted variant', function () {
        $product = searchProduct('Plain Tee');
        $variant = $product->variants()->sole();
        $sku = $variant->sku;
        $variant->delete();

        expect(searchNames('q='.urlencode($sku)))->toBe([]);
    });

    it('matches LIKE wildcards literally', function () {
        searchProduct('Dress');

        expect(searchNames('q=%25%25'))->toBe([]);
    });

    it('never lists inactive products or products of inactive categories', function () {
        searchProduct('Dress Active');
        searchProduct('Dress Hidden', attributes: ['status' => 'inactive']);
        $off = Category::factory()->inactive()->create();
        searchProduct('Dress Off', $off);

        expect(searchNames('q=dress'))->toBe(['Dress Active']);
    });

    it('lists nothing for a term without letters or digits', function () {
        searchProduct('Dress');

        expect(searchNames('q=%2B%2B'))->toBe([]);
    });
});

describe('with the other filters', function () {
    it('combines the term with the category', function () {
        searchProduct('Dress');
        searchProduct('Dress Stand', $this->kitchen);

        expect(searchNames('q=dress&category=kitchenware'))->toBe(['Dress Stand']);
    });

    it('combines the term with price, in-stock and option filters', function () {
        $cheap = Product::factory()->withOptions(['Size' => ['M']], price: 100000)->create(['name' => 'Dress Cheap', 'category_id' => $this->fashion->id]);
        Product::factory()->withOptions(['Size' => ['L']], price: 500000)->create(['name' => 'Dress Dear', 'category_id' => $this->fashion->id]);
        Product::factory()->withOptions(['Size' => ['M']], price: 100000, stock: 0)->create(['name' => 'Dress Sold', 'category_id' => $this->fashion->id]);
        Product::factory()->withOptions(['Size' => ['M']], price: 100000)->create(['name' => 'Gown', 'category_id' => $this->fashion->id]);

        expect(searchNames('q=dress&max=2000&in_stock=1&f[size]=m'))->toBe([$cheap->name]);
    });

    it('paginates with the term kept in the links', function () {
        foreach (range(1, 13) as $number) {
            searchProduct("Dress {$number}");
        }
        searchProduct('Pot', $this->kitchen);

        $this->get('/search?q=dress&page=2')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('products.data', 1)
            ->where('products.meta.total', 13)
            ->where('products.meta.links', fn ($links) => collect($links)->pluck('url')->filter()->every(fn (string $url) => str_contains($url, 'q=dress'))));
    });
});

describe('sorting', function () {
    it('defaults to best match and ranks a name hit above a description-only hit', function () {
        searchProduct('Linen Gown', attributes: ['short_description' => 'Soft']);
        searchProduct('Gown', attributes: ['short_description' => 'Made of linen']);
        searchProduct('Linen Shirt', attributes: ['short_description' => 'Linen shirt']);

        $names = searchNames('q=linen');

        expect($names[0])->toBe('Linen Shirt')
            ->and($names)->toBe(['Linen Shirt', 'Linen Gown', 'Gown'])
            ->and(end($names))->toBe('Gown');
    });

    it('puts an exact SKU hit first', function () {
        searchProduct('Zeta Dress');
        $hit = searchProduct('Plain');
        $hit->variants()->sole()->update(['sku' => 'DRESS']);

        expect(searchNames('q=dress')[0])->toBe('Plain');
    });

    it('offers best match only while a term is set', function () {
        $this->get('/search?q=dress')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sortOptions.0.value', 'best_match')
            ->where('filters.active.sort', 'best_match')
            ->has('sortOptions', 4));
        $this->get('/search')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.active.sort', 'newest')
            ->has('sortOptions', 3));
    });

    it('sorts by price when asked, even with a term', function () {
        Product::factory()->simple(900000)->create(['name' => 'Dress Dear', 'category_id' => $this->fashion->id]);
        Product::factory()->simple(100000)->create(['name' => 'Dress Cheap', 'category_id' => $this->fashion->id]);

        expect(searchNames('q=dress&sort=price_asc'))->toBe(['Dress Cheap', 'Dress Dear'])
            ->and(searchNames('q=dress&sort=price_desc'))->toBe(['Dress Dear', 'Dress Cheap']);
    });

    it('falls back to newest for best match without a term', function () {
        $this->get('/search?sort=best_match')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.active.sort', 'newest'));
    });
});

describe('short terms', function () {
    it('applies no matching and flags a one-character term', function () {
        searchProduct('Dress');
        searchProduct('Pot', $this->kitchen);

        $this->get('/search?q=m')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.active.q_too_short', true)
            ->where('filters.active.sort', 'newest')
            ->has('products.data', 2));
    });

    it('does not flag an empty or usable term', function () {
        $this->get('/search?q=')->assertInertia(fn (AssertableInertia $page) => $page->where('filters.active.q_too_short', false));
        $this->get('/search?q=dr')->assertInertia(fn (AssertableInertia $page) => $page->where('filters.active.q_too_short', false));
    });
});

describe('suggest', function () {
    it('returns at most six cards with the total', function () {
        foreach (range(1, 8) as $number) {
            searchProduct("Dress {$number}");
        }

        $this->getJson('/search/suggest?q=dress')
            ->assertOk()
            ->assertJsonCount(6, 'data.items')
            ->assertJsonPath('data.total', 8)
            ->assertJsonStructure(['data' => ['items' => [['name', 'category', 'thumb', 'price', 'url']], 'total']]);
    });

    it('returns the product card fields', function () {
        $product = Product::factory()->simple(250000)->create(['name' => 'Summer Dress', 'slug' => 'summer-dress', 'category_id' => $this->fashion->id]);

        $this->getJson('/search/suggest?q=summer')
            ->assertOk()
            ->assertJsonPath('data.items.0.name', 'Summer Dress')
            ->assertJsonPath('data.items.0.category', 'Fashion')
            ->assertJsonPath('data.items.0.price', 250000)
            ->assertJsonPath('data.items.0.url', route('products.show', $product->slug, false));
    });

    it('stays inside the given category', function () {
        searchProduct('Dress');
        searchProduct('Dress Stand', $this->kitchen);

        $this->getJson('/search/suggest?q=dress&category=kitchenware')
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.name', 'Dress Stand');
    });

    it('returns nothing for an empty or too short term', function (string $query) {
        searchProduct('Dress');

        $this->getJson('/search/suggest'.$query)
            ->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.items', []);
    })->with([
        'no term' => [''],
        'empty term' => ['?q='],
        'one character' => ['?q=d'],
    ]);

    it('rejects an oversized term', function () {
        $this->getJson('/search/suggest?q='.str_repeat('a', 101))->assertUnprocessable();
    });

    it('caches the answer for a normalized term', function () {
        searchProduct('Dress');

        $this->getJson('/search/suggest?q=dress');
        searchProduct('Dress Two');

        $this->getJson('/search/suggest?q=++DRESS')->assertJsonPath('data.total', 1);
    });

    it('is throttled to 60 requests a minute per IP', function () {
        foreach (range(1, 60) as $_) {
            $this->getJson('/search/suggest?q=dress')->assertOk();
        }

        $this->getJson('/search/suggest?q=dress')->assertTooManyRequests();
    });
});
