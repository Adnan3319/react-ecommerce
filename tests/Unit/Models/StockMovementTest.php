<?php

use App\Enums\StockMovementReason;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;

function stockMovementFor(ProductVariant $variant, array $attributes = []): StockMovement
{
    return StockMovement::create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
        'reason' => StockMovementReason::ManualAdjustment,
        ...$attributes,
    ]);
}

describe('rows', function () {
    it('has a created_at but no updated_at', function () {
        $movement = stockMovementFor(ProductVariant::factory()->create());

        expect($movement->created_at)->not->toBeNull()
            ->and($movement->getAttributes())->not->toHaveKey('updated_at')
            ->and(StockMovement::UPDATED_AT)->toBeNull();
    });

    it('casts the reason to the enum and keeps the quantity signed', function () {
        $variant = ProductVariant::factory()->create();

        $movement = stockMovementFor($variant, ['quantity' => -2, 'reason' => StockMovementReason::OrderPlaced])->fresh();

        expect($movement->reason)->toBe(StockMovementReason::OrderPlaced)
            ->and($movement->quantity)->toBe(-2);
    });

    it('belongs to a variant and an optional creator', function () {
        $variant = ProductVariant::factory()->create();
        $admin = User::factory()->create();

        $movement = stockMovementFor($variant, ['created_by' => $admin->id]);

        expect($movement->variant->is($variant))->toBeTrue()
            ->and($movement->creator->is($admin))->toBeTrue()
            ->and(stockMovementFor($variant)->creator)->toBeNull();
    });

    it('can point at the record that caused it', function () {
        $variant = ProductVariant::factory()->create();
        $order = Product::factory()->create();

        $movement = stockMovementFor($variant, ['reference_type' => $order->getMorphClass(), 'reference_id' => $order->id]);

        expect($movement->reference->is($order))->toBeTrue();
    });

    it('keeps the history of a variant when it is soft-deleted', function () {
        $variant = ProductVariant::factory()->create();
        stockMovementFor($variant);

        $variant->delete();

        expect(StockMovement::where('product_variant_id', $variant->id)->count())->toBe(2);
    });

    it('sums to the stock of a variant created by the factory', function () {
        $variant = ProductVariant::factory()->create(['stock' => 12]);

        expect($variant->stockMovements()->sum('quantity'))->toBe(12);
    });
});

describe('reasons', function () {
    it('has the five reasons from the stock rules', function () {
        expect(array_map(fn (StockMovementReason $reason) => $reason->value, StockMovementReason::cases()))
            ->toBe(['initial', 'manual_adjustment', 'order_placed', 'order_cancelled', 'order_returned']);
    });

    it('has a readable label for each reason', function () {
        foreach (StockMovementReason::cases() as $reason) {
            expect($reason->label())->not->toBe('');
        }
    });
});
