<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

/**
 * Builds readable, globally unique SKUs: {CAT3}-{product id, 4 digits}-{option codes}.
 *
 *   FAS-0012          default variant of a simple product
 *   FAS-0012-M-PNK    variant with the values "M" and "Pink"
 */
final class SkuGenerator
{
    private const CATEGORY_CODE_LENGTH = 3;

    private const OPTION_CODE_LENGTH = 3;

    private const FALLBACK_CATEGORY_CODE = 'GEN';

    /**
     * @param  array<int, string>  $values  the variant's value texts, in head order
     * @param  array<int, string>  $reserved  SKUs already chosen but not saved yet (batch creation)
     */
    public function generate(Product $product, array $values = [], array $reserved = []): string
    {
        $base = implode('-', [
            $this->categoryCode($product),
            str_pad((string) $product->id, 4, '0', STR_PAD_LEFT),
            ...array_map(fn (string $value) => $this->optionCode($value), $values),
        ]);

        $candidate = $base;
        $suffix = 1;

        while ($this->isTaken($candidate, $reserved)) {
            $suffix++;
            $candidate = "{$base}-{$suffix}";
        }

        return $candidate;
    }

    /**
     * First letters of the top-level category, so every Fashion sub-category gives "FAS".
     */
    private function categoryCode(Product $product): string
    {
        $product->loadMissing('category.parent');

        $category = $product->category->parent ?? $product->category;
        $code = substr($this->alphanumeric($category->name), 0, self::CATEGORY_CODE_LENGTH);

        return $code !== '' ? $code : self::FALLBACK_CATEGORY_CODE;
    }

    /**
     * Keeps the first character, drops later vowels and takes three characters
     * ("Pink" → "PNK", "M" → "M", "XL" → "XL"). Values with no Latin letters or digits get a short hash.
     */
    private function optionCode(string $value): string
    {
        $clean = $this->alphanumeric($value);

        if ($clean === '') {
            return strtoupper(substr(md5($value), 0, self::OPTION_CODE_LENGTH));
        }

        $code = $clean[0].preg_replace('/[AEIOU]/', '', substr($clean, 1));

        return substr($code, 0, self::OPTION_CODE_LENGTH);
    }

    private function alphanumeric(string $text): string
    {
        return (string) preg_replace('/[^A-Z0-9]/', '', strtoupper(Str::ascii($text)));
    }

    /**
     * @param  array<int, string>  $reserved
     */
    private function isTaken(string $sku, array $reserved): bool
    {
        return in_array($sku, $reserved, true)
            || ProductVariant::withTrashed()->where('sku', $sku)->exists();
    }
}
