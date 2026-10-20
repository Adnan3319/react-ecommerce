<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    /**
     * The statuses an order in this status may move to (doc/order/README.md §4).
     *
     * @return array<int, self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered, self::Returned],
            self::Delivered => [self::Returned],
            self::Cancelled, self::Returned => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /**
     * Moving an order into this status puts its stock back.
     */
    public function restoresStock(): bool
    {
        return match ($this) {
            self::Cancelled, self::Returned => true,
            default => false,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Badge color of the admin UI (`admin/components/ui/badge`).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Processing, self::Shipped => 'primary',
            self::Delivered => 'success',
            self::Cancelled => 'error',
            self::Returned => 'dark',
        };
    }
}
