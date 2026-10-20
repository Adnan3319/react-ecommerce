<?php

namespace App\Services\Product;

use Illuminate\Http\UploadedFile;

/**
 * A validated product save, ready for ProductService: prices are in minor units and variants
 * reference their values by text in head order (see doc/product/02-admin-backend.md).
 *
 * `fullSave` is false for partial updates such as the status/featured toggles of the list; then
 * only `attributes` is applied and images, heads and variants are left alone.
 */
final readonly class ProductPayload
{
    /**
     * @param  array<string, mixed>  $attributes  columns of the products table that were submitted
     * @param  array<int, UploadedFile>  $gallery  new gallery uploads, in order
     * @param  array<int, int>  $galleryKeep  ids of existing gallery images to keep, in display order
     * @param  array{sku: ?string, price: int, sale_price: ?int, stock: int}  $simple  the default variant (has_variants = false)
     * @param  list<array{id: ?int, name: string, values: list<array{id: ?int, value: string}>}>  $heads
     * @param  list<array{id: ?int, values: list<string>, sku: ?string, price: int, sale_price: ?int, stock: int, image_ref: ?string, status: string}>  $variants
     */
    public function __construct(
        public array $attributes,
        public bool $fullSave = true,
        public ?UploadedFile $mainImage = null,
        public array $gallery = [],
        public array $galleryKeep = [],
        public bool $hasVariants = false,
        public array $simple = ['sku' => null, 'price' => 0, 'sale_price' => null, 'stock' => 0],
        public array $heads = [],
        public array $variants = [],
    ) {}
}
