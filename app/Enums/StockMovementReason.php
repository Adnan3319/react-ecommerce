<?php

namespace App\Enums;

enum StockMovementReason: string
{
    case Initial = 'initial';
    case ManualAdjustment = 'manual_adjustment';
    case OrderPlaced = 'order_placed';
    case OrderCancelled = 'order_cancelled';
    case OrderReturned = 'order_returned';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Initial stock',
            self::ManualAdjustment => 'Manual adjustment',
            self::OrderPlaced => 'Order placed',
            self::OrderCancelled => 'Order cancelled',
            self::OrderReturned => 'Order returned',
        };
    }
}
