<?php

namespace App\Services\Product;

use App\Enums\StockMovementReason;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The only place that changes `product_variants.stock`. Every change writes a stock movement,
 * so the movements of a variant always add up to its stock.
 */
class StockService
{
    public function __construct(private readonly ProductCache $cache) {}

    /**
     * Starting stock of a new variant. Writes an `initial` movement (none for zero).
     */
    public function setInitial(ProductVariant $variant, int $quantity): void
    {
        $this->moveTo($variant, $quantity, StockMovementReason::Initial);
    }

    /**
     * Admin edit to a new absolute stock: records the difference as `manual_adjustment`.
     * Nothing is written when the quantity did not change.
     */
    public function adjust(ProductVariant $variant, int $newQuantity, ?string $note = null): void
    {
        $this->moveTo($variant, $newQuantity, StockMovementReason::ManualAdjustment, $note);
    }

    private function moveTo(ProductVariant $variant, int $newQuantity, StockMovementReason $reason, ?string $note = null): void
    {
        DB::transaction(function () use ($variant, $newQuantity, $reason, $note): void {
            // Lock the row and work from the stored value, not from a possibly stale model.
            $current = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $difference = $newQuantity - $current->stock;

            if ($difference === 0) {
                return;
            }

            $current->forceFill(['stock' => $newQuantity])->save();

            StockMovement::create([
                'product_variant_id' => $current->id,
                'quantity' => $difference,
                'reason' => $reason,
                'note' => $note,
                'created_by' => Auth::id(),
            ]);

            $variant->setAttribute('stock', $newQuantity);
            $variant->syncOriginalAttribute('stock');
        });

        $this->cache->flush();
    }
}
