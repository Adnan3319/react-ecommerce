<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Cash on Delivery',
        };
    }
}
