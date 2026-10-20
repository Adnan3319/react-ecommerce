<?php

namespace App\Enums;

enum OrderSource: string
{
    case BuyNow = 'buy_now';
    case Cart = 'cart';

    public function label(): string
    {
        return match ($this) {
            self::BuyNow => 'Order now',
            self::Cart => 'Cart',
        };
    }
}
