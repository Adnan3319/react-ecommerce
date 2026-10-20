# Step 01 — Product data model

Read `CLAUDE.md`, `testcase.md` and `doc/product/README.md` first. Follow README §1–§4 exactly.

**Scope:** data layer only. No controllers, routes or UI.

**Start in plan mode.** Show tables, columns, indexes and relations; wait for approval.

## Tasks

1. Check the prerequisite: `categories.parent_id` exists. If not, stop and report.
2. New migrations for `products`, `variant_heads`, `variant_values`, `product_images`, `product_variants`, `variant_selections`, `stock_movements` exactly as README §2.
3. `App\Enums\StockMovementReason`.
4. Config: `media.product_gallery_max` (default 3), `media.presets.product` + `thumb` variant (README §3), `shop.low_stock_threshold` (default 5), `shop.max_variant_heads` (2).
5. Models:
   - `Product`: category, images, mainImage, galleryImages, variantHeads, variants, activeVariants; scopes `active()`, `featured()`, `inStock()`; accessors `price_range` (min/max effective price), `total_stock`, `has_variants` (has variant heads).
   - `VariantHead` (product, values), `VariantValue` (head, variants).
   - `ProductVariant`: product, image, stockMovements, variantValues; accessors `effective_price` (sale ?? price), `discount_percent` (int, null when no sale), `is_out_of_stock`, `label` ("M / Pink", "Default"); `combinationKeyFor()` and `syncVariantValues()`.
   - `ProductImage`, `StockMovement` (no `updated_at`).
   - Casts: enums, money as int, booleans.
6. `App\Services\Product\SkuGenerator`: `{CAT3}-{product id 4 digits}-{option short codes}` e.g. `FAS-0012-M-PNK`; default variant `FAS-0012`; appends `-2`, `-3` on collision.
7. Factories with states: `Product::factory()->simple()`, `->withVariants(int $n)`, `->withOptions(array $heads)`, `->outOfStock()`, `->featured()`, `->inactive()`.

## Tests (Unit, per testcase.md)

- Relations and scopes (`active`, `featured`, `inStock`).
- `price_range`, `total_stock`, `effective_price`, `discount_percent` with exact numbers (2000 → 1650 = 18%).
- `label` for default and two-head variants.
- SKU format and collision suffix.
- One live variant per product combination enforced at DB level (`unique (product_id, live_key)`), including the default variant and one-head products; a soft-deleted combination can be added again.

## Done when

- `php artisan migrate:fresh --seed` works on `checkit_db`.
- `php artisan test`, `npx tsc --noEmit`, `vp lint` all exit 0.
- Commit: `feat: product data model`.
- Report only: changed files, three exit codes, commit hash.
