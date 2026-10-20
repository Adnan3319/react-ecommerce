<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use DomainException;

final class InvalidOrderTransition extends DomainException
{
    public static function between(OrderStatus $from, OrderStatus $to): self
    {
        return new self("An order cannot move from {$from->value} to {$to->value}.");
    }
}
