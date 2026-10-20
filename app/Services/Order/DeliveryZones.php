<?php

namespace App\Services\Order;

use InvalidArgumentException;

/**
 * Delivery areas and their charges, read from `config('shop.delivery_zones')`.
 */
class DeliveryZones
{
    /**
     * @return array<string, array{label: string, charge: int}>
     */
    public function all(): array
    {
        return config('shop.delivery_zones');
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    /**
     * @return array{label: string, charge: int}
     *
     * @throws InvalidArgumentException when the key is not a configured zone
     */
    public function get(string $key): array
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException("Unknown delivery zone [{$key}].");
    }

    /**
     * Delivery charge in minor units.
     */
    public function charge(string $key): int
    {
        return $this->get($key)['charge'];
    }
}
