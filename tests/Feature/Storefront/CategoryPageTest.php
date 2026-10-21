<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

/**
 * A product with Size and Color options where only the listed combinations ("M/Pink") have stock.
 *
 * @param  array<int, string>  $inStock
 */
function listingDress(Category $category, string $name, array $inStock, array $options = ['Size' => ['M', 'L'], 'Color' => ['Pink', 'Blue']]): Product
{
    $product = Product::factory()->withOptions($options)->create(['name' => $name, 'category_id' => $category->id]);

    $product->variants()->with('variantValues')->get()->each(function (ProductVariant $variant) use ($inStock): void {
        $combination = $variant->variantValues->pluck('value')->implode('/');
        $variant->update(['stock' => in_array($combination, $inStock, true) ? 5 : 0]);
    });

    return $product;
}

function listingNames(AssertableInertia $page): array
{
    return collect($page->toArray()['props']['products']['data'])->pluck('name')->all();
}

function categoryNames(string $url): array
{
    $names = [];
    test()->get($url)->assertOk()->assertInertia(function (AssertableInertia $page) use (&$names) {
        $names = listingNames($page);
    });

    return $names;
}

beforeEach(function () {
    $this->fashion = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);
    $this->women = Category::factory()->childOf($this->fashion)->create(['name' => "Women's Clothing", 'slug' => 'womens-clothing']);
    $this->bags = Category::factory()->childOf($this->fashion)->create(['name' => 'Bags', 'slug' => 'bags']);
    $this->kitchen = Category::factory()->create(['name' => 'Kitchenware', 'slug' => 'kitchenware']);
});

describe('category scope', function () {
    it('lists the products of a parent category together with its sub-categories', function () {
        Product::factory()->simple()->create(['name' => 'Dress', 'category_id' => $this->women->id]);
        Product::factory()->simple()->create(['name' => 'Tote', 'category_id' => $this->bags->id]);
        Product::factory()->simple()->create(['name' => 'Pot', 'category_id' => $this->kitchen->id]);

        expect(categoryNames('/categories/fashion'))->toEqualCanonicalizing(['Dress', 'Tote']);
    });

    it('lists only its own products for a sub-category', function () {
        Product::factory()->simple()->create(['name' => 'Dress', 'category_id' => $this->women->id]);
        Product::factory()->simple()->create(['name' => 'Tote', 'category_id' => $this->bags->id]);

        expect(categoryNames('/categories/womens-clothing'))->toBe(['Dress']);
    });

    it('hides inactive products and products of inactive sub-categories', function () {
        $hidden = Category::factory()->childOf($this->fashion)->inactive()->create();
        Product::factory()->simple()->create(['name' => 'Shown', 'category_id' => $this->women->id]);
        Product::factory()->simple()->inactive()->create(['name' => 'Inactive', 'category_id' => $this->women->id]);
        Product::factory()->simple()->create(['name' => 'In hidden category', 'category_id' => $hidden->id]);

        expect(categoryNames('/categories/fashion'))->toBe(['Shown']);
    });

    it('returns 404 for an unknown, inactive or soft-deleted category', function () {
        Category::factory()->inactive()->create(['slug' => 'off']);
        $gone = Category::factory()->create(['slug' => 'gone']);
        $gone->delete();

        $this->get('/categories/nothing')->assertNotFound();
        $this->get('/categories/off')->assertNotFound();
        $this->get('/categories/gone')->assertNotFound();
    });

    it('returns 404 for a sub-category whose parent is inactive', function () {
        $this->fashion->update(['status' => 'inactive']);

        $this->get('/categories/womens-clothing')->assertNotFound();
    });
});

describe('props', function () {
    it('sends the category, products, filters, sort options and seo', function () {
        Product::factory()->simple()->create(['category_id' => $this->women->id]);

        $this->get('/categories/womens-clothing')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Categories/Show', false)
            ->where('category.slug', 'womens-clothing')
            ->where('category.parent.slug', 'fashion')
            ->has('products.data', 1)
            ->has('products.meta')
            ->has('filters.available.price')
            ->has('filters.available.heads')
            ->has('filters.active')
            ->has('sortOptions', 3)
            ->has('seo'));
    });

    it('paginates twelve per page and keeps the filters in the page links', function () {
        Product::factory()->count(14)->simple()->create(['category_id' => $this->women->id]);

        $this->get('/categories/womens-clothing?sort=price_asc')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('products.data', 12)
            ->where('products.meta.total', 14)
            ->where('products.links.next', fn (string $url) => str_contains($url, 'sort=price_asc') && str_contains($url, 'page=2')));

        $this->get('/categories/womens-clothing?page=2')->assertInertia(fn (AssertableInertia $page) => $page->has('products.data', 2));
    });
});

describe('filters', function () {
    it('filters by price using the sale price', function () {
        $sale = Product::factory()->simple(300000)->create(['name' => 'On sale', 'category_id' => $this->women->id]);
        $sale->variants()->update(['sale_price' => 200000]);
        Product::factory()->simple(500000)->create(['name' => 'Dear', 'category_id' => $this->women->id]);

        expect(categoryNames('/categories/womens-clothing?max=2500'))->toBe(['On sale'])
            ->and(categoryNames('/categories/womens-clothing?min=2500'))->toBe(['Dear'])
            ->and(categoryNames('/categories/womens-clothing?min=2000&max=2000'))->toBe(['On sale']);
    });

    it('filters by stock', function () {
        Product::factory()->simple()->create(['name' => 'In', 'category_id' => $this->women->id]);
        Product::factory()->simple(stock: 0)->create(['name' => 'Out', 'category_id' => $this->women->id]);

        expect(categoryNames('/categories/womens-clothing?in_stock=1'))->toBe(['In'])
            ->and(categoryNames('/categories/womens-clothing'))->toEqualCanonicalizing(['In', 'Out']);
    });

    it('matches option values with OR inside a head and AND across heads', function () {
        listingDress($this->women, 'M Pink', ['M/Pink'], ['Size' => ['M'], 'Color' => ['Pink']]);
        listingDress($this->women, 'L Pink', ['L/Pink'], ['Size' => ['L'], 'Color' => ['Pink']]);
        listingDress($this->women, 'M Blue', ['M/Blue'], ['Size' => ['M'], 'Color' => ['Blue']]);

        expect(categoryNames('/categories/womens-clothing?f[size]=m,l'))->toEqualCanonicalizing(['M Pink', 'L Pink', 'M Blue'])
            ->and(categoryNames('/categories/womens-clothing?f[size]=m&f[color]=pink'))->toBe(['M Pink'])
            ->and(categoryNames('/categories/womens-clothing?f[size]=l,m&f[color]=blue'))->toBe(['M Blue']);
    });

    it('treats head and value names case-insensitively', function () {
        listingDress($this->women, 'M Pink', ['M/Pink'], ['Size' => ['M'], 'Color' => ['Pink']]);

        expect(categoryNames('/categories/womens-clothing?f[SIZE]=M&f[Color]=PINK'))->toBe(['M Pink']);
    });

    it('requires price, stock and every option to match the same variant', function () {
        // M/Pink is sold out while L/Blue is in stock.
        listingDress($this->women, 'Split stock', ['L/Blue']);

        expect(categoryNames('/categories/womens-clothing?f[size]=m&f[color]=pink&in_stock=1'))->toBe([])
            ->and(categoryNames('/categories/womens-clothing?f[size]=m&f[color]=pink'))->toBe(['Split stock'])
            ->and(categoryNames('/categories/womens-clothing?f[size]=l&f[color]=blue&in_stock=1'))->toBe(['Split stock'])
            // M is in stock only as M/..., which is sold out; L/Pink is not in stock either.
            ->and(categoryNames('/categories/womens-clothing?f[size]=m&in_stock=1'))->toBe([]);
    });

    it('requires the price and the option to match the same variant', function () {
        $product = listingDress($this->women, 'Priced', ['M/Pink', 'L/Blue']);
        $product->variants()->with('variantValues')->get()->each(fn (ProductVariant $variant) => $variant->update([
            'price' => $variant->variantValues->pluck('value')->contains('M') ? 100000 : 900000,
        ]));

        expect(categoryNames('/categories/womens-clothing?f[size]=m&min=5000'))->toBe([])
            ->and(categoryNames('/categories/womens-clothing?f[size]=m&max=1500'))->toBe(['Priced']);
    });

    it('ignores sold-out inactive variants when matching', function () {
        $product = listingDress($this->women, 'Retired', ['M/Pink']);
        $product->variants()->with('variantValues')->get()
            ->first(fn (ProductVariant $variant) => $variant->variantValues->pluck('value')->sort()->values()->all() === ['M', 'Pink'])
            ->update(['status' => 'inactive']);

        expect(categoryNames('/categories/womens-clothing?f[size]=m&f[color]=pink'))->toBe([]);
    });

    it('ignores unknown heads, unknown values and broken parameters', function () {
        listingDress($this->women, 'Dress', ['M/Pink']);

        $this->get('/categories/womens-clothing?f[material]=wool&f[size]=xxl&min=abc&sort=nope&f=oops')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('products.data', 1)
                ->where('filters.active.f', [])
                ->where('filters.active.sort', 'newest'));
    });
});

describe('sorting', function () {
    it('orders newest first by default and by price in both directions', function () {
        Product::factory()->simple(300000)->create(['name' => 'Middle', 'category_id' => $this->women->id]);
        Product::factory()->simple(100000)->create(['name' => 'Cheap', 'category_id' => $this->women->id]);
        Product::factory()->simple(900000)->create(['name' => 'Dear', 'category_id' => $this->women->id]);

        expect(categoryNames('/categories/womens-clothing'))->toBe(['Dear', 'Cheap', 'Middle'])
            ->and(categoryNames('/categories/womens-clothing?sort=price_asc'))->toBe(['Cheap', 'Middle', 'Dear'])
            ->and(categoryNames('/categories/womens-clothing?sort=price_desc'))->toBe(['Dear', 'Middle', 'Cheap']);
    });

    it('sorts by the cheapest effective price over the active variants', function () {
        $multi = Product::factory()->withVariants(2, price: 500000)->create(['name' => 'Multi', 'category_id' => $this->women->id]);
        $multi->variants()->orderBy('id')->first()->update(['sale_price' => 100000]);
        Product::factory()->simple(300000)->create(['name' => 'Flat', 'category_id' => $this->women->id]);

        expect(categoryNames('/categories/womens-clothing?sort=price_asc'))->toBe(['Multi', 'Flat']);
    });
});

describe('available filters', function () {
    it('lists only the heads the category products have, merged case-insensitively', function () {
        listingDress($this->women, 'A', ['M/Pink'], ['Size' => ['M'], 'Color' => ['Pink']]);
        listingDress($this->women, 'B', ['L/Blue'], ['size' => ['L', 'M'], 'Colour' => ['Blue']]);
        Product::factory()->simple()->create(['category_id' => $this->kitchen->id]);

        $this->get('/categories/womens-clothing')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.available.heads', fn ($heads) => collect($heads)->pluck('key')->all() === ['size', 'color', 'colour']));

        $this->get('/categories/kitchenware')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.available.heads', []));
    });

    it('counts the products per value and reports the price bounds', function () {
        $first = listingDress($this->women, 'A', ['M/Pink'], ['Size' => ['M', 'L'], 'Color' => ['Pink']]);
        listingDress($this->women, 'B', ['M/Blue'], ['Size' => ['M'], 'Color' => ['Blue']]);
        $first->variants()->update(['sale_price' => 90000]);

        $this->get('/categories/womens-clothing')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.available.heads.0.values', fn ($values) => collect($values)->pluck('count', 'key')->all() === ['m' => 2, 'l' => 1])
            ->where('filters.available.price', ['min' => '900', 'max' => '1500']));
    });

    it('caches the available filters per category for ten minutes', function () {
        Product::factory()->simple()->create(['category_id' => $this->women->id]);

        $this->get('/categories/womens-clothing');

        expect(Cache::has('catalog.filters.'.$this->women->id))->toBeTrue()
            ->and(Cache::has('catalog.filters.'.$this->kitchen->id))->toBeFalse();
    });
});

describe('seo', function () {
    it('is indexable with a clean canonical and a breadcrumb', function () {
        Product::factory()->simple()->create(['category_id' => $this->women->id]);

        $seo = $this->get('/categories/womens-clothing')->viewData('page')['props']['seo'];

        expect($seo['noindex'])->toBeFalse()
            ->and($seo['canonical'])->toBe(route('categories.show', 'womens-clothing'))
            ->and($seo['jsonLd'][0]['@type'])->toBe('BreadcrumbList')
            ->and(collect($seo['jsonLd'][0]['itemListElement'])->pluck('name')->all())->toBe(['Home', 'Fashion', "Women's Clothing"]);
    });

    it('adds the page number to the canonical of page two', function () {
        Product::factory()->count(13)->simple()->create(['category_id' => $this->women->id]);

        $seo = $this->get('/categories/womens-clothing?page=2')->viewData('page')['props']['seo'];

        expect($seo['canonical'])->toBe(route('categories.show', 'womens-clothing').'?page=2')
            ->and($seo['noindex'])->toBeFalse();
    });

    it('is noindex with the clean canonical when a filter or sort is active', function (string $query) {
        $seo = $this->get('/categories/womens-clothing?'.$query)->viewData('page')['props']['seo'];

        expect($seo['noindex'])->toBeTrue()
            ->and($seo['canonical'])->toBe(route('categories.show', 'womens-clothing'));
    })->with(['price' => 'min=10', 'stock' => 'in_stock=1', 'sort' => 'sort=price_asc', 'option' => 'f[size]=m']);

    it('ignores a search term on a category page', function () {
        Product::factory()->simple()->create(['name' => 'Dress', 'category_id' => $this->women->id]);

        $this->get('/categories/womens-clothing?q=zzz&category=bags')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('products.data', 1)
            ->where('filters.active.q', null)
            ->where('filters.active.category', null)
            ->where('seo.noindex', false));
    });
});

it('loads a page with the same number of queries however many products it lists', function () {
    $queriesFor = function (): int {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();
        test()->get('/categories/womens-clothing')->assertOk();

        return count(DB::getQueryLog());
    };

    Product::factory()->count(3)->withOptions(['Size' => ['M', 'L'], 'Color' => ['Pink']])->create(['category_id' => $this->women->id]);
    $few = $queriesFor();

    Product::factory()->count(9)->withOptions(['Size' => ['M', 'L'], 'Color' => ['Pink']])->create(['category_id' => $this->women->id]);
    $many = $queriesFor();

    expect($many)->toBe($few);
});
