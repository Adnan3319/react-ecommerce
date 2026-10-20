<?php

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\QueryException;

describe('relations', function () {
    it('belongs to an order, a product and a variant', function () {
        $item = OrderItem::factory()->create();

        expect($item->order->items)->toHaveCount(1)
            ->and($item->product->id)->toBe($item->variant->product_id)
            ->and($item->variant->sku)->toBe($item->sku);
    });

    it('still shows a soft-deleted product and variant', function () {
        $item = OrderItem::factory()->create();
        $item->variant->delete();
        $item->product->delete();

        $fresh = $item->fresh();

        expect($fresh->variant)->not->toBeNull()->and($fresh->product)->not->toBeNull();
    });
});

describe('hard delete guard', function () {
    it('blocks force deleting a variant that was ordered', function () {
        $item = OrderItem::factory()->create();

        $item->variant->forceDelete();
    })->throws(QueryException::class);

    it('blocks force deleting a product that was ordered', function () {
        $item = OrderItem::factory()->create();

        $item->product->forceDelete();
    })->throws(QueryException::class);

    it('still allows force deleting a product nobody ordered', function () {
        $product = Product::factory()->create();

        $product->forceDelete();

        expect(Product::withTrashed()->find($product->id))->toBeNull();
    });
});
