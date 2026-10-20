<?php

use App\Enums\StockMovementReason;
use App\Exceptions\InsufficientStock;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Product\StockService;

beforeEach(function () {
    $this->stock = app(StockService::class);
    $this->variant = ProductVariant::factory()->create(['stock' => 0]);
});

describe('setInitial', function () {
    it('sets the stock and writes an initial movement', function () {
        $this->stock->setInitial($this->variant, 12);

        $movement = StockMovement::sole();

        expect($this->variant->fresh()->stock)->toBe(12)
            ->and($movement->quantity)->toBe(12)
            ->and($movement->reason)->toBe(StockMovementReason::Initial);
    });

    it('writes no movement for zero', function () {
        $this->stock->setInitial($this->variant, 0);

        expect(StockMovement::count())->toBe(0);
    });
});

describe('adjust', function () {
    it('records the difference as a manual adjustment by the acting user', function () {
        $admin = User::factory()->create();
        $this->actingAs($admin);
        $this->stock->setInitial($this->variant, 20);

        $this->stock->adjust($this->variant, 15, 'recount');

        $movement = StockMovement::latest('id')->first();

        expect($movement->quantity)->toBe(-5)
            ->and($movement->reason)->toBe(StockMovementReason::ManualAdjustment)
            ->and($movement->note)->toBe('recount')
            ->and($movement->created_by)->toBe($admin->id)
            ->and($this->variant->fresh()->stock)->toBe(15);
    });

    it('writes nothing when the quantity is unchanged', function () {
        $this->stock->setInitial($this->variant, 7);

        $this->stock->adjust($this->variant, 7);

        expect(StockMovement::count())->toBe(1);
    });

    it('works from the stored stock, not from a stale model', function () {
        $this->stock->setInitial($this->variant, 10);
        $stale = ProductVariant::find($this->variant->id);
        ProductVariant::whereKey($this->variant->id)->update(['stock' => 4]);

        $this->stock->adjust($stale, 6);

        expect(StockMovement::latest('id')->first()->quantity)->toBe(2)
            ->and($this->variant->fresh()->stock)->toBe(6);
    });

    it('keeps the sum of the movements equal to the stock', function () {
        $this->stock->setInitial($this->variant, 10);
        $this->stock->adjust($this->variant, 25);
        $this->stock->adjust($this->variant, 3);

        expect(StockMovement::sum('quantity'))->toBe($this->variant->fresh()->stock);
    });
});

describe('deduct and restore', function () {
    it('removes and returns units for an order with a signed, referenced movement', function () {
        $this->stock->setInitial($this->variant, 10);
        $order = Order::factory()->create();

        $this->stock->deduct($this->variant, 4, StockMovementReason::OrderPlaced, $order);
        $this->stock->restore($this->variant, 4, StockMovementReason::OrderCancelled, $order);

        $movements = StockMovement::whereIn('reason', [StockMovementReason::OrderPlaced, StockMovementReason::OrderCancelled])->orderBy('id')->get();

        expect($movements->pluck('quantity')->all())->toBe([-4, 4])
            ->and($movements->pluck('reference_id')->unique()->all())->toBe([$order->id])
            ->and($this->variant->fresh()->stock)->toBe(10)
            ->and(StockMovement::sum('quantity'))->toBe(10);
    });

    it('never goes below zero', function () {
        $this->stock->setInitial($this->variant, 2);

        $this->stock->deduct($this->variant, 3, StockMovementReason::OrderPlaced, Order::factory()->create());
    })->throws(InsufficientStock::class);

    it('leaves stock and movements untouched when it refuses', function () {
        $this->stock->setInitial($this->variant, 2);

        try {
            $this->stock->deduct($this->variant, 3, StockMovementReason::OrderPlaced, Order::factory()->create());
        } catch (InsufficientStock) {
        }

        expect($this->variant->fresh()->stock)->toBe(2)->and(StockMovement::count())->toBe(1);
    });
});
