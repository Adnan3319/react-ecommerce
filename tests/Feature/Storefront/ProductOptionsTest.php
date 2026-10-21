<?php

use App\Models\Product;
use Illuminate\Support\Facades\RateLimiter;

it('returns the heads and variants of a product for the quick picker', function () {
    $product = Product::factory()->withOptions(['Size' => ['S', 'M'], 'Color' => ['Pink']])->create(['name' => 'Dress']);

    $this->getJson(route('products.options', $product->slug))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Dress')
        ->assertJsonCount(2, 'data.heads')
        ->assertJsonCount(2, 'data.variants')
        ->assertJsonStructure(['data' => [
            'id', 'name', 'slug', 'thumb',
            'heads' => [['id', 'name', 'values' => [['id', 'value']]]],
            'variants' => [['id', 'value_ids', 'price', 'sale_price', 'effective_price', 'discount_percent', 'stock', 'stock_state', 'thumb']],
        ]]);
});

it('returns 404 for an inactive product', function () {
    $product = Product::factory()->simple()->inactive()->create();

    $this->getJson(route('products.options', $product->slug))->assertNotFound();
});

it('returns 404 for an unknown slug', function () {
    $this->getJson(route('products.options', 'nothing-here'))->assertNotFound();
});

it('throttles to 60 requests a minute', function () {
    $product = Product::factory()->simple()->create();
    RateLimiter::clear('product-options');

    foreach (range(1, 60) as $ignored) {
        $this->getJson(route('products.options', $product->slug))->assertOk();
    }

    $this->getJson(route('products.options', $product->slug))->assertTooManyRequests();
});
