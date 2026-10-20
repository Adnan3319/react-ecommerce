<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Cross-field rules of a product save that single-field validation rules cannot express.
 * Runs after the basic rules passed, so every field it reads has the right type, and before
 * anything is uploaded or written. It touches the database a fixed number of times
 * (product relations once, SKUs in one query), whatever the number of variants.
 *
 * Returns messages keyed by the payload path of the offending field ("variants.2.values.1").
 */
class ProductPayloadChecker
{
    /**
     * @param  array<string, mixed>  $input  validated request input
     * @return array<string, string>
     */
    public function check(array $input, ?Product $product, bool $hasMainUpload, int $galleryUploads): array
    {
        $product?->loadMissing(['mainImage', 'galleryImages', 'variantHeads.values', 'variants']);

        $errors = $this->checkImages($input, $product, $hasMainUpload, $galleryUploads);

        if (! array_key_exists('has_variants', $input)) {
            return $errors; // partial update (status / featured toggle)
        }

        return $errors + (filter_var($input['has_variants'], FILTER_VALIDATE_BOOL)
            ? $this->checkMatrix($input, $product, $hasMainUpload, $galleryUploads)
            : $this->checkSimple($input, $product));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    private function checkImages(array $input, ?Product $product, bool $hasMainUpload, int $galleryUploads): array
    {
        $errors = [];
        $max = (int) config('media.product_gallery_max');
        $keep = array_map('intval', (array) ($input['gallery_keep'] ?? []));

        if (count($keep) + $galleryUploads > $max) {
            $errors['gallery'] = "A product can have at most {$max} gallery images.";
        }

        $ownGallery = $product?->galleryImages->pluck('id')->all() ?? [];

        if (array_diff($keep, $ownGallery) !== []) {
            $errors['gallery_keep'] = 'One of the gallery images does not belong to this product.';
        }

        if (array_key_exists('has_variants', $input) && ! $hasMainUpload && $product?->mainImage === null) {
            $errors['main_image'] = 'A main image is required.';
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    private function checkSimple(array $input, ?Product $product): array
    {
        $errors = [];

        if ($this->salePriceTooHigh($input['price'] ?? null, $input['sale_price'] ?? null)) {
            $errors['sale_price'] = 'The sale price must be lower than the price.';
        }

        $default = $product?->variants->firstWhere('combination_key', ProductVariant::DEFAULT_COMBINATION);
        $sku = $input['sku'] ?? null;

        if (filled($sku) && $this->skuOwnedByOther($this->variantIdsBySku([(string) $sku]), (string) $sku, $default?->id)) {
            $errors['sku'] = 'This SKU is already used.';
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    private function checkMatrix(array $input, ?Product $product, bool $hasMainUpload, int $galleryUploads): array
    {
        $heads = array_values((array) ($input['heads'] ?? []));
        $variants = array_values((array) ($input['variants'] ?? []));

        $errors = $this->checkHeads($heads, $product);

        if ($errors !== []) {
            return $errors;
        }

        return $this->checkVariants($variants, $heads, $product, $hasMainUpload, $galleryUploads, $this->validImageIds($input, $product));
    }

    /**
     * @param  list<array<string, mixed>>  $heads
     * @return array<string, string>
     */
    private function checkHeads(array $heads, ?Product $product): array
    {
        $errors = [];
        $max = (int) config('shop.max_variant_heads');

        if (count($heads) > $max) {
            $errors['heads'] = "A product can have at most {$max} variant heads.";
        }

        $ownHeads = $product?->variantHeads->keyBy('id');
        $seenNames = [];

        foreach ($heads as $i => $head) {
            $nameKey = $this->normalize($head['name']);

            if (isset($seenNames[$nameKey])) {
                $errors["heads.{$i}.name"] = 'Each variant head needs its own name.';
            }

            $seenNames[$nameKey] = true;

            if (filled($head['id'] ?? null) && ! $ownHeads?->has((int) $head['id'])) {
                $errors["heads.{$i}.id"] = 'This variant head does not belong to the product.';
            }

            $ownValues = $ownHeads?->get((int) ($head['id'] ?? 0))?->values->keyBy('id');
            $seenValues = [];

            foreach (array_values((array) $head['values']) as $j => $value) {
                $valueKey = $this->normalize($value['value']);

                if (isset($seenValues[$valueKey])) {
                    $errors["heads.{$i}.values.{$j}.value"] = 'Each value of a head must be different.';
                }

                $seenValues[$valueKey] = true;

                if (filled($value['id'] ?? null) && ! $ownValues?->has((int) $value['id'])) {
                    $errors["heads.{$i}.values.{$j}.id"] = 'This value does not belong to the variant head.';
                }
            }
        }

        return $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     * @param  list<array<string, mixed>>  $heads
     * @param  array<int, int>  $validImageIds
     * @return array<string, string>
     */
    private function checkVariants(array $variants, array $heads, ?Product $product, bool $hasMainUpload, int $galleryUploads, array $validImageIds): array
    {
        $errors = [];
        $max = (int) config('shop.max_variants_per_product');

        if (count($variants) > $max) {
            $errors['variants'] = "A product can have at most {$max} variants.";
        }

        $knownValues = array_map(
            fn (array $head) => array_map(fn (array $value) => $this->normalize($value['value']), array_values((array) $head['values'])),
            $heads,
        );
        $ownVariants = $product?->variants->keyBy('id');
        $variantIdsBySku = $this->variantIdsBySku(array_filter(array_map(fn (array $variant) => $variant['sku'] ?? null, $variants), 'filled'));
        $combinations = [];
        $ids = [];
        $skus = [];

        foreach ($variants as $k => $variant) {
            $values = array_values((array) $variant['values']);

            if (count($values) !== count($heads)) {
                $errors["variants.{$k}.values"] = 'Choose one value for each variant head.';

                continue;
            }

            foreach ($values as $i => $text) {
                if (! in_array($this->normalize($text), $knownValues[$i], true)) {
                    $errors["variants.{$k}.values.{$i}"] = "\"{$text}\" is not a value of {$heads[$i]['name']}.";
                }
            }

            $combination = implode("\x1F", array_map($this->normalize(...), $values));

            if (isset($combinations[$combination])) {
                $errors["variants.{$k}.values"] = 'Another variant already uses this combination.';
            }

            $combinations[$combination] = true;

            $errors += $this->checkVariantIdentity($k, $variant, $ownVariants, $variantIdsBySku, $ids, $skus);

            if ($this->salePriceTooHigh($variant['price'] ?? null, $variant['sale_price'] ?? null)) {
                $errors["variants.{$k}.sale_price"] = 'The sale price must be lower than the price.';
            }

            if (! $this->imageRefIsValid($variant['image_ref'] ?? null, $validImageIds, $hasMainUpload, $galleryUploads)) {
                $errors["variants.{$k}.image_ref"] = 'Choose one of this product\'s images.';
            }
        }

        return $errors;
    }

    /**
     * Variant id belongs to the product and is sent once; SKU is unique in the request and in the database.
     *
     * @param  array<string, mixed>  $variant
     * @param  Collection<int, ProductVariant>|null  $ownVariants
     * @param  array<string, int>  $variantIdsBySku
     * @param  array<int, bool>  $ids
     * @param  array<string, bool>  $skus
     * @return array<string, string>
     */
    private function checkVariantIdentity(int $k, array $variant, ?Collection $ownVariants, array $variantIdsBySku, array &$ids, array &$skus): array
    {
        $errors = [];
        $id = filled($variant['id'] ?? null) ? (int) $variant['id'] : null;

        if ($id !== null) {
            if (! $ownVariants?->has($id) || isset($ids[$id])) {
                $errors["variants.{$k}.id"] = 'This variant does not belong to the product.';
            }

            $ids[$id] = true;
        }

        if (filled($variant['sku'] ?? null)) {
            $sku = (string) $variant['sku'];
            $key = strtoupper($sku);

            if (isset($skus[$key]) || $this->skuOwnedByOther($variantIdsBySku, $sku, $id)) {
                $errors["variants.{$k}.sku"] = 'This SKU is already used.';
            }

            $skus[$key] = true;
        }

        return $errors;
    }

    /**
     * Images a variant may point to: the main image (kept even when replaced) and the kept gallery images.
     *
     * @param  array<string, mixed>  $input
     * @return array<int, int>
     */
    private function validImageIds(array $input, ?Product $product): array
    {
        $ids = array_map('intval', (array) ($input['gallery_keep'] ?? []));

        if ($product?->mainImage !== null) {
            $ids[] = $product->mainImage->id;
        }

        return $ids;
    }

    /**
     * @param  array<int, int>  $validImageIds
     */
    private function imageRefIsValid(mixed $ref, array $validImageIds, bool $hasMainUpload, int $galleryUploads): bool
    {
        if ($ref === null || $ref === '') {
            return true;
        }

        $ref = (string) $ref;

        if (ctype_digit($ref)) {
            return in_array((int) $ref, $validImageIds, true);
        }

        if ($ref === 'new:main') {
            return $hasMainUpload;
        }

        return (bool) preg_match('/^new:(\d+)$/', $ref, $match) && (int) $match[1] < $galleryUploads;
    }

    private function salePriceTooHigh(mixed $price, mixed $salePrice): bool
    {
        if (blank($price) || blank($salePrice)) {
            return false;
        }

        return Money::toMinor($salePrice) >= Money::toMinor($price);
    }

    /**
     * Which variant (soft-deleted ones included) already owns each requested SKU: one query for all.
     *
     * @param  array<int, string>  $skus
     * @return array<string, int> variant id by upper-cased SKU
     */
    private function variantIdsBySku(array $skus): array
    {
        if ($skus === []) {
            return [];
        }

        return ProductVariant::withTrashed()
            ->whereIn('sku', array_map('strtoupper', array_values($skus)))
            ->pluck('id', 'sku')
            ->mapWithKeys(fn (int $id, string $sku) => [strtoupper($sku) => $id])
            ->all();
    }

    /**
     * @param  array<string, int>  $variantIdsBySku
     */
    private function skuOwnedByOther(array $variantIdsBySku, string $sku, ?int $ownVariantId): bool
    {
        $owner = $variantIdsBySku[strtoupper($sku)] ?? null;

        return $owner !== null && $owner !== $ownVariantId;
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(trim($text));
    }
}
