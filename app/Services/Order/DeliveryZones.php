<?php

namespace App\Services\Order;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Delivery areas and their charges. The labels ("Inside Chattogram", "Outside Chattogram") and both charges
 * come from the company row (admin: Company settings → Delivery Charge); `config('shop.delivery_zones')` only
 * supplies the zone keys and the fallback values until that row exists.
 */
class DeliveryZones
{
    public const CACHE_KEY = 'delivery.settings';

    /** Zone key => [label prefix, `companies` column that holds its charge]. */
    private const ZONES = [
        'inside' => ['Inside', 'delivery_charge_inside'],
        'outside' => ['Outside', 'delivery_charge_outside'],
    ];

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, array{label: string, charge: int}>
     */
    public function all(): array
    {
        $zones = config('shop.delivery_zones');
        $saved = $this->saved();

        if ($saved === []) {
            return $zones;
        }

        foreach (self::ZONES as $key => [$prefix, $column]) {
            if (isset($zones[$key])) {
                $zones[$key] = ['label' => "{$prefix} {$saved['city']}", 'charge' => $saved[$column]];
            }
        }

        return $zones;
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
     * The zones as the order forms list them (charges in minor units).
     *
     * @return list<array{key: string, label: string, charge: int}>
     */
    public function forStorefront(): array
    {
        return array_map(fn (string $key) => ['key' => $key, ...$this->get($key)], $this->keys());
    }

    /**
     * Delivery charge in minor units.
     */
    public function charge(string $key): int
    {
        return $this->get($key)['charge'];
    }

    /**
     * @return array<string, mixed> city and both charges from the company row; empty before it exists
     */
    private function saved(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), function (): array {
            $columns = ['city', ...array_column(self::ZONES, 1)];

            return Company::query()->first($columns)?->only($columns) ?? [];
        });
    }
}
