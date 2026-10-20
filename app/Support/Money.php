<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Money is stored as integer minor units (paisa). Admin forms work in taka; this class converts
 * between the two without any floating point math.
 */
final class Money
{
    private const MINOR_PER_MAJOR = 100;

    private const CURRENCY_SYMBOL = '৳';

    /**
     * "1650" / "1,650.5" / 1650 → 165000. Null and "" stay null.
     *
     * @throws InvalidArgumentException when the value is not a plain amount with at most two decimals
     */
    public static function toMinor(string|int|float|null $taka): ?int
    {
        if ($taka === null || $taka === '') {
            return null;
        }

        $value = is_float($taka) ? number_format($taka, 2, '.', '') : str_replace(',', '', trim((string) $taka));

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException("Not a valid amount: [{$taka}].");
        }

        [$whole, $fraction] = array_pad(explode('.', $value), 2, '');

        return ((int) $whole * self::MINOR_PER_MAJOR) + (int) str_pad($fraction, 2, '0');
    }

    /**
     * 165000 → "1650", 165050 → "1650.50" (what an admin form field holds).
     */
    public static function toTaka(int $minor): string
    {
        $whole = intdiv($minor, self::MINOR_PER_MAJOR);
        $fraction = $minor % self::MINOR_PER_MAJOR;

        return $fraction === 0 ? (string) $whole : sprintf('%d.%02d', $whole, $fraction);
    }

    /**
     * 165000 → "৳1,650", 165050 → "৳1,650.50".
     */
    public static function format(int $minor): string
    {
        $whole = intdiv($minor, self::MINOR_PER_MAJOR);
        $fraction = $minor % self::MINOR_PER_MAJOR;

        return self::CURRENCY_SYMBOL.number_format($whole).($fraction === 0 ? '' : sprintf('.%02d', $fraction));
    }
}
