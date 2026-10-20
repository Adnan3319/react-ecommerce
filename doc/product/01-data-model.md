# Step 01 — Product data model

Read `CLAUDE.md`, `testcase.md` and `docs/product/README.md` first. Follow README §1–§4 exactly.

**Scope:** data layer only. No controllers, routes or UI.

**Start in plan mode.** Show tables, columns, indexes and relations; wait for approval.

## Tasks

1. Check the prerequisite: `categories.parent_id` exists. If not, stop and report.
2. New migrations for `products`, `product_images`, `product_variants`, `stock_movements` exactly as README §2.
3. `App\Enums\StockMovementReason`.
4. Config: `media.product_gallery_max` (default 3), `media.presets.product` + `thumb` variant (README §3), `shop.low_stock_threshold` (default 5).
5. Models:
   - `Product`: category, images, mainImage, galleryImages, variants, activeVariants; scopes `active()`, `featured()`, `inStock()`; accessors `price_range` (min/max effective price), `total_stock`, `has_variants`.
   - `ProductVariant`: product, image, stockMovements; accessors `effective_price` (sale ?? price), `discount_percent` (int, null when no sale), `is_out_of_stock`, `label` ("M / Pink", "Default").
   - `ProductImage`, `StockMovement` (no `updated_at`).
   - Casts: enums, money as int, booleans.
6. `App\Services\Product\SkuGenerator`: `{CAT3}-{product id 4 digits}-{option short codes}` e.g. `FAS-0012-M-PNK`; default variant `FAS-0012`; appends `-2`, `-3` on collision.
7. Factories with states: `Product::factory()->simple()`, `->withVariants(int $n)`, `->outOfStock()`, `->featured()`, `->inactive()`.

## Tests (Unit, per testcase.md)

- Relations and scopes (`active`, `featured`, `inStock`).
- `price_range`, `total_stock`, `effective_price`, `discount_percent` with exact numbers (2000 → 1650 = 18%).
- `label` for default and two-option variants.
- SKU format and collision suffix.
- Unique (product_id, option1_value, option2_value) enforced at DB level.

## Done when

- `php artisan migrate:fresh --seed` works on `checkit_db`.
- `php artisan test`, `npx tsc --noEmit`, `vp lint` all exit 0.
- Commit: `feat: product data model`.
- Report only: changed files, three exit codes, commit hash.
