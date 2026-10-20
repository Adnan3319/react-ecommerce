# Step 02 — Product admin backend

Read `CLAUDE.md`, `testcase.md` and `docs/product/README.md` first. Step 01 must be DONE.

**Scope:** services, controller, requests, resources, routes, cache. No UI (step 03).

**Start in plan mode.** Show request payload shape, service methods and routes; wait for approval.

## Tasks

1. Routes in the admin auth group: `Route::resource('products', Admin\ProductController::class)->except('show')` → `admin.products.*`.
2. `Admin\ProductController` (thin): `index`, `create`, `store`, `edit`, `update`, `destroy`. Writes redirect back / to index with flash.
3. `IndexProductRequest`: `search` (max 100), `category_id`, `status`, `featured`, `stock` (`in|out|low`), `per_page` clamped 10–50.
4. `SaveProductRequest` (store + update):
   - Basic: `name`, `slug` (auto from name when empty; unique incl. soft-deleted), `short_description` ≤300, `description`, `status`, `is_featured`, `category_id` (must be a leaf).
   - Images: `main_image` required on create; `gallery[]` new files + `gallery_keep[]` existing ids; total ≤ `media.product_gallery_max`; upload rule from README §3.
   - `has_variants` bool.
   - Off: `price`, `sale_price` (< price), `stock`, `sku`.
   - On: `option1_name` required, `option2_name` nullable, `variants[]` with `id` (on update), `option1_value`, `option2_value`, `sku`, `price`, `sale_price`, `stock`, `image_ref` (existing image id or gallery index), `status`. No duplicate option combos; min 1 variant.
   - Convert taka → minor units in `prepareForValidation`/`validated()`.
5. `App\Services\Product\ProductService`:
   - `paginate(filters)` — search name/SKU with escaped LIKE; category filter includes its children; eager load main image, category, variants (select only needed columns).
   - `create(data)`, `update(product, data)` in one `DB::transaction`: product, images (FileUploadService preset `product`), variants synced by id (create / update / soft-delete missing), variant image links.
   - Variants off → keep exactly one default variant. Turning on → default replaced by generated variants.
   - `delete(product)` — soft delete product + variants; TODO guard for orders.
   - Old image files deleted only after commit.
6. `App\Services\Product\StockService`:
   - `setInitial(variant, qty)`, `adjust(variant, newQty, note)` → writes `stock_movements` with the difference; no row when unchanged.
   - Used by ProductService for every stock write.
7. Resources:
   - `ProductListResource`: id, name, thumb_url, category path ("Fashion › Women's Clothing"), price range (taka, formatted), total_stock, stock_state (`in|low|out`), status, is_featured.
   - `ProductFormResource`: everything the 4 tabs need; prices in taka; images with ids and urls; variants with image id.
   - `create`/`edit` also pass: category tree (roots with children), `galleryMax`.
8. Cache: `products.featured` (active + featured + in stock, limit 8, plain arrays: id, name, slug, thumb, srcset, price, sale_price, discount_percent). Cleared on every product write. Add `featuredProducts` to `HomeService::data()`.
9. Sidebar link is added in step 03.

## Tests (Feature, per testcase.md)

- Access matrix (testcase §7) for index and store.
- Create simple product → 1 variant, 1 `initial` movement, main image + thumb files exist.
- Create variant product (2×2) → 4 variants, 4 `initial` movements, SKUs auto-generated.
- Update: stock 20 → 25 → one `manual_adjustment` +5; unchanged stock → no row.
- Switch variants on → default removed; switch off → one default left.
- Validation dataset: non-leaf category, sale ≥ price, duplicate option combo, gallery over limit, image too small/large/heavy, missing main image on create.
- Image replace and delete remove old files.
- Filters: search by SKU, category incl. children, stock in/out/low.
- Cache: product write clears only `products.featured`; HomeService returns featuredProducts.

## Done when

- `php artisan test`, `npx tsc --noEmit`, `vp lint` all exit 0.
- Commit: `feat: product admin backend`.
- Report only: changed files, three exit codes, commit hash.
