<?php

use App\Enums\ActiveStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Queue::fake();
});

/**
 * JSON-LD block of the given type from the Inertia page props.
 *
 * @return array<string, mixed>
 */
function productPageJsonLd(TestResponse $response, string $type): array
{
    $schemas = $response->viewData('page')['props']['seo']['jsonLd'];

    return collect($schemas)->firstWhere('@type', $type);
}

function productPagePartialUpdate(Product $product, array $data): void
{
    test()->post(route('admin.products.update', $product), ['_method' => 'put', ...$data])->assertSessionHasNoErrors();
}

describe('availability', function () {
    it('shows an active product', function () {
        $product = Product::factory()->simple(165000, 10)->create(['name' => "Women's Summer Dress"]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Products/Show', false)
                ->where('product.name', "Women's Summer Dress")
                ->where('product.id', $product->id)
                ->has('product.variants', 1)
                ->where('product.variants.0.effective_price', 165000));
    });

    it('answers 404 for an inactive, soft-deleted or unknown product', function (Closure $make) {
        $slug = $make();

        $this->get(route('products.show', $slug))->assertNotFound();
    })->with([
        'inactive' => [fn () => Product::factory()->simple()->inactive()->create()->slug],
        'soft deleted' => [function () {
            $product = Product::factory()->simple()->create();
            $product->delete();

            return $product->slug;
        }],
        'unknown' => [fn () => 'no-such-product'],
    ]);
});

describe('props', function () {
    it('sends heads, value ids, variants, delivery zones in minor units and the quantity limit', function () {
        $product = Product::factory()->withOptions(['Size' => ['M', 'L'], 'Color' => ['Pink']], 200000, 7)->create();
        $variant = $product->variants()->orderBy('sort_order')->first();
        $variant->update(['sale_price' => 150000]);

        $this->get(route('products.show', $product->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('product.heads', 2)
                ->where('product.heads.0.name', 'Size')
                ->has('product.heads.0.values', 2)
                ->has('product.variants', 2)
                ->has('product.variants.0.value_ids', 2)
                ->where('product.variants.0.label', 'M / Pink')
                ->where('product.variants.0.price', 200000)
                ->where('product.variants.0.sale_price', 150000)
                ->where('product.variants.0.discount_percent', 25)
                ->where('product.variants.0.stock', 7)
                ->where('product.variants.0.stock_state', 'in')
                ->where('deliveryZones.0', ['key' => 'inside', 'label' => 'Inside City', 'charge' => 7000])
                ->where('deliveryZones.1.charge', 13000)
                ->where('maxQty', 10));
    });

    it('sends stock states and leaves out inactive variants', function () {
        $product = Product::factory()->withVariants(3, 10)->create();
        [$low, $out, $hidden] = $product->variants()->orderBy('sort_order')->get();
        $low->update(['stock' => 3]);
        $out->update(['stock' => 0]);
        $hidden->update(['status' => ActiveStatus::Inactive]);

        $this->get(route('products.show', $product->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('product.variants', 2)
                ->where('product.variants.0.stock_state', 'low')
                ->where('product.variants.1.stock_state', 'out'));
    });

    it('sends the category trail and no label for a simple product variant', function () {
        $parent = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);
        $child = Category::factory()->childOf($parent)->create(['name' => "Women's Clothing", 'slug' => 'womens-clothing']);
        $product = Product::factory()->simple()->create(['category_id' => $child->id]);

        $this->get(route('products.show', $product->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('category.trail', [
                    ['slug' => 'fashion', 'label' => 'Fashion'],
                    ['slug' => 'womens-clothing', 'label' => "Women's Clothing"],
                ])
                ->where('product.variants.0.label', null)
                ->where('product.has_variants', false));
    });
});

describe('seo', function () {
    it('uses the meta fields, the canonical url and noindex false', function () {
        $product = Product::factory()->simple()->create(['meta_title' => 'Buy the dress', 'meta_description' => 'Light cotton dress.']);

        $this->get(route('products.show', $product->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('seo.title', 'Buy the dress')
                ->where('seo.description', 'Light cotton dress.')
                ->where('seo.canonical', route('products.show', $product->slug))
                ->where('seo.noindex', false));
    });

    it('falls back to the product name and short description', function () {
        $product = Product::factory()->simple()->create(['name' => 'Linen Shirt', 'short_description' => 'Cool linen.', 'meta_title' => null, 'meta_description' => null]);

        $this->get(route('products.show', $product->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('seo.title', 'Linen Shirt | '.config('app.name'))
                ->where('seo.description', 'Cool linen.'));
    });

    it('renders the title, canonical and json-ld in the first HTML response', function () {
        $product = Product::factory()->simple(165000, 5)->create(['name' => 'Linen Shirt']);

        $html = $this->get(route('products.show', $product->slug))->getContent();

        expect($html)->toContain('<title data-inertia="title">Linen Shirt | '.config('app.name').'</title>')
            ->and($html)->toContain('rel="canonical" href="'.route('products.show', $product->slug).'"')
            ->and($html)->toContain('<script type="application/ld+json"');
    });

    it('describes the price and availability in the Product json-ld', function () {
        $product = Product::factory()->simple(165050, 5)->create();

        $schema = productPageJsonLd($this->get(route('products.show', $product->slug)), 'Product');

        expect($schema['offers'])->toMatchArray([
            '@type' => 'Offer',
            'price' => '1650.50',
            'priceCurrency' => 'BDT',
            'availability' => 'https://schema.org/InStock',
        ])->and($schema['sku'])->not->toBeEmpty();
    });

    it('reports OutOfStock when no variant has stock', function () {
        $product = Product::factory()->simple(100000, 0)->create();

        $schema = productPageJsonLd($this->get(route('products.show', $product->slug)), 'Product');

        expect($schema['offers']['availability'])->toBe('https://schema.org/OutOfStock');
    });

    it('uses an AggregateOffer when the variants have different prices', function () {
        $product = Product::factory()->withVariants(2, 10, 100000)->create();
        $product->variants()->orderBy('sort_order')->get()->last()->update(['price' => 250000]);

        $offers = productPageJsonLd($this->get(route('products.show', $product->slug)), 'Product')['offers'];

        expect($offers)->toMatchArray(['@type' => 'AggregateOffer', 'lowPrice' => '1000', 'highPrice' => '2500', 'offerCount' => 2]);
    });

    it('adds a BreadcrumbList from Home to the product', function () {
        $parent = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);
        $product = Product::factory()->simple()->create(['category_id' => $parent->id, 'name' => 'Linen Shirt']);

        $schema = productPageJsonLd($this->get(route('products.show', $product->slug)), 'BreadcrumbList');

        expect(collect($schema['itemListElement'])->pluck('name')->all())->toBe(['Home', 'Fashion', 'Linen Shirt'])
            ->and(collect($schema['itemListElement'])->pluck('position')->all())->toBe([1, 2, 3]);
    });
});

describe('old slugs', function () {
    beforeEach(fn () => actingAsAdmin());

    it('redirect with a 301 to the current slug after the slug was changed', function () {
        $product = Product::factory()->simple()->create(['slug' => 'old-name']);

        productPagePartialUpdate($product, ['slug' => 'new-name']);

        $this->get(route('products.show', 'old-name'))->assertStatus(301)->assertRedirect(route('products.show', 'new-name'));
        $this->get(route('products.show', 'new-name'))->assertOk();
    });

    it('follow a chain of renames to the latest slug', function () {
        $product = Product::factory()->simple()->create(['slug' => 'first']);

        productPagePartialUpdate($product, ['slug' => 'second']);
        productPagePartialUpdate($product, ['slug' => 'third']);

        $this->get(route('products.show', 'first'))->assertRedirect(route('products.show', 'third'));
        $this->get(route('products.show', 'second'))->assertRedirect(route('products.show', 'third'));
    });

    it('stop redirecting when the product goes back to an earlier slug', function () {
        $product = Product::factory()->simple()->create(['slug' => 'first']);

        productPagePartialUpdate($product, ['slug' => 'second']);
        productPagePartialUpdate($product, ['slug' => 'first']);

        $this->get(route('products.show', 'first'))->assertOk();
        $this->get(route('products.show', 'second'))->assertRedirect(route('products.show', 'first'));
    });

    it('answer 404 when the product is no longer active', function () {
        $product = Product::factory()->simple()->create(['slug' => 'old-name']);
        productPagePartialUpdate($product, ['slug' => 'new-name']);
        productPagePartialUpdate($product, ['status' => 'inactive']);

        $this->get(route('products.show', 'old-name'))->assertNotFound();
    });

    it('are not kept when the slug did not change', function () {
        $product = Product::factory()->simple()->create(['slug' => 'same']);

        productPagePartialUpdate($product, ['name' => 'Renamed only']);

        expect(DB::table('product_slug_redirects')->count())->toBe(0);
    });
});

describe('cache', function () {
    it('reads the product data from the cache on the second request', function () {
        $product = Product::factory()->withVariants(2)->create();
        $this->get(route('products.show', $product->slug))->assertOk();

        DB::enableQueryLog();
        $this->get(route('products.show', $product->slug))->assertOk();
        $tables = collect(DB::getQueryLog())->pluck('query')->implode(' ');
        DB::disableQueryLog();

        expect($tables)->not->toContain('product_variants')->not->toContain('product_images')->not->toContain('variant_heads');
    });

    it('is refreshed by an admin update of the product', function () {
        actingAsAdmin();
        $product = Product::factory()->simple()->create(['name' => 'Before']);
        $this->get(route('products.show', $product->slug))->assertInertia(fn (AssertableInertia $page) => $page->where('product.name', 'Before'));

        productPagePartialUpdate($product, ['name' => 'After']);

        $this->get(route('products.show', $product->slug))->assertInertia(fn (AssertableInertia $page) => $page->where('product.name', 'After'));
    });

    it('is refreshed when an order changes the stock', function () {
        $product = Product::factory()->simple(100000, 5)->create();
        $variant = $product->variants()->sole();
        $this->get(route('products.show', $product->slug))->assertInertia(fn (AssertableInertia $page) => $page->where('product.variants.0.stock', 5));

        $this->post(route('orders.store'), [
            'name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'address' => 'House 1, Road 2, Dhaka',
            'delivery_zone' => 'inside',
            'items' => [['variant_id' => $variant->id, 'quantity' => 2]],
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->get(route('products.show', $product->slug))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.variants.0.stock', 3)
            ->where('product.variants.0.stock_state', 'low'));
    });

    it('stops serving a product that was deactivated', function () {
        actingAsAdmin();
        $product = Product::factory()->simple()->create();
        $this->get(route('products.show', $product->slug))->assertOk();

        productPagePartialUpdate($product, ['status' => 'inactive']);

        $this->get(route('products.show', $product->slug))->assertNotFound();
    });
});
