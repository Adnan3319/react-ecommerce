<?php

namespace App\Support;

/**
 * Bangladeshi mobile numbers. Customers are matched by the normalized form.
 */
final class Phone
{
    private const COUNTRY_CODE = '880';

    private const NORMALIZED_PATTERN = '/^01[3-9]\d{8}$/';

    private const SEARCH_TERM_PATTERN = '/^\+?[\d\s-]+$/';

    /**
     * "01712345678", "+8801712345678", "8801712345678" (spaces and dashes allowed) → "01712345678".
     * Null when the value is not a Bangladeshi mobile number.
     */
    public static function normalize(string $phone): ?string
    {
        $local = self::toLocal($phone);

        return preg_match(self::NORMALIZED_PATTERN, $local) === 1 ? $local : null;
    }

    /**
     * A search box term that looks like a (partial) phone number, in the local form stored phones have:
     * "+880 1712-34" → "0171234". Null for any other term.
     */
    public static function searchTerm(string $term): ?string
    {
        return preg_match(self::SEARCH_TERM_PATTERN, trim($term)) === 1 ? self::toLocal($term) : null;
    }

    private static function toLocal(string $phone): string
    {
        $value = (string) preg_replace('/[\s-]+/', '', trim($phone));

        if (str_starts_with($value, '+')) {
            $value = substr($value, 1);
        }

        if (str_starts_with($value, self::COUNTRY_CODE)) {
            $value = '0'.substr($value, strlen(self::COUNTRY_CODE));
        }

        return $value;
    }
}
