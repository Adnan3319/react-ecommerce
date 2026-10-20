# Step 02 — Product admin backend

Read `CLAUDE.md`, `testcase.md` and `doc/product/README.md` first. Step 01 must be DONE.

**Scope:** services, controller, requests, resources, routes, cache. No UI (step 03).

**Start in plan mode.** Show request payload shape, service methods and routes; wait for approval.

## Request payload

`POST /admin/products` and `POST /admin/products/{id}` with `_method=put`. Multipart (`forceFormData`), bracket notation. Money is typed in **taka** (decimal, max 2 places) and converted to minor units in the request. Ids are only sent for rows that already exist (update).

Basic, SEO and images (same for simple and variant products):

```
name, slug, short_description, description, status, is_featured, category_id
meta_title, meta_description
main_image               file   required on create; on update a new file replaces the main image
gallery[]                files  new gallery images
gallery_keep[]           ids    existing gallery images to keep, in display order (new files follow)
has_variants             bool
```

Simple product (`has_variants = false`):

```
price, sale_price, stock, sku        # the single default variant
```

Variant product (`has_variants = true`):

```
heads[0][id]                       # existing head only
heads[0][name]                     # "Size"        ≤ 30
heads[0][values][0][id]            # existing value only
heads[0][values][0][value]         # "M"           ≤ 50
heads[0][values][1][value]         # "L"
heads[1][name]                     # "Color"
heads[1][values][0][value]         # "Pink"
heads[1][values][1][value]         # "Black"

variants[0][id]                    # existing variant only
variants[0][values][0]             # "M"     the variant's value text for heads[0]
variants[0][values][1]             # "Pink"  the variant's value text for heads[1]
variants[0][sku]                   # empty = auto
variants[0][price], [sale_price], [stock]
variants[0][image_ref]             # null | "123" existing image id | "new:0" gallery upload index | "new:main" new main upload
variants[0][status]
```

- Variants reference their values by **text, in head order**, resolved against the submitted `heads`. A renamed value keeps its id, so its variants follow the rename.
- `variants` is a subset of the head/value combinations: the admin may remove combinations that are not sold (no "XL / Black").

## Tasks

1. Routes in the admin auth group: `Route::resource('products', Admin\ProductController::class)->except('show')` → `admin.products.*`.
2. `Admin\ProductController` (thin): `index`, `create`, `store`, `edit`, `update`, `destroy`. Writes redirect back / to index with flash.
3. `IndexProductRequest`: `search` (max 100), `category_id`, `status`, `featured`, `stock` (`in|out|low`), `per_page` clamped 10–50.
4. `SaveProductRequest` (store + update):
   - Basic: `name`, `slug` (auto from name when empty; unique incl. soft-deleted), `short_description` ≤300, `description`, `status`, `is_featured`, `category_id` (must be a leaf).
   - Images: upload rule from README §3. `main_image` is required on create. **Every product always keeps exactly one main image:** on update the current main stays unless a new `main_image` replaces it, the main image cannot be removed or sent in `gallery_keep[]`, and a product that has no main image (should never happen) must get one on the next save. `gallery_keep[]` ids must be non-main images of this product; kept + new gallery files ≤ `config('media.product_gallery_max')`.
   - `has_variants` bool.
   - Off: `price`, `sale_price` (< price), `stock`, `sku`.
   - On, heads: `heads` required, 1 to `config('shop.max_variant_heads')` items (the same config value is sent to the UI); `heads.*.name` required, unique among the product's heads (case-insensitive); `heads.*.values` min 1; `value` unique within its head (case-insensitive); `heads.*.id` / `values.*.id` must belong to this product (never accepted on create).
   - On, variants: `variants` min 1 and at most `config('shop.max_variants_per_product')` (add it, default 100, so a cartesian explosion cannot be submitted); each variant has exactly one value text per head, and each text must exist in that head; no two variants with the same combination; `variants.*.id` must belong to this product; `sku`, `price`, `sale_price` (< price), `stock`, `status`; `image_ref` must point to an image of this product or to an upload in this request.
   - Convert taka → minor units in `prepareForValidation`/`validated()`.
5. `App\Services\Product\ProductService`:
   - `paginate(filters)` — search name/SKU with escaped LIKE; category filter includes its children; eager load main image, category, variants (select only needed columns).
   - `create(data)`, `update(product, data)` in one `DB::transaction`. Order inside the transaction:
     1. product fields;
     2. images (FileUploadService preset `product`): replace main, add gallery, drop gallery rows not in `gallery_keep[]`;
     3. heads: create new, update existing by id (name, position);
     4. values: create new, update existing by id (text, position); values that no submitted variant uses are not saved;
     5. variants: resolve each variant's value texts to value ids and compute `combination_key` (`ProductVariant::combinationKeyFor`); match to existing variants by id; create / update; write the selections and key together with `syncVariantValues()`; variants missing from the payload are soft-deleted **and their `variant_selections` rows are removed**, so the values they used can be deleted and stay out of the way of the restrict FK;
     6. delete heads and values that are no longer submitted (nothing live references them any more);
     7. variant image links (`product_image_id`) and stock via `StockService`;
     8. assert the product still has exactly one main image, otherwise throw a `ValidationException`.
   - Combination changes on existing variants are applied in two passes (move to a temporary unique key, then the final key), so swapping two combinations in one request does not trip `unique (product_id, live_key)`.
   - Variants off → keep exactly one default variant (`combination_key = default`, no selections). Turning variants **on**: the default variant is soft-deleted and variants are created from the heads. Turning them **off**: all variants are soft-deleted (selections removed), heads and values are deleted, and a new default variant is created. The soft-deleted variants keep their own stock and movement history.
   - `delete(product)` — soft delete product + variants (selections removed); TODO guard for orders.
   - Old image files deleted only after commit.
6. `App\Services\Product\StockService`:
   - `setInitial(variant, qty)`, `adjust(variant, newQty, note)` → writes `stock_movements` with the difference; no row when unchanged.
   - Used by ProductService for every stock write (new variants created on update get an `initial` movement).
7. Resources:
   - `ProductListResource`: id, name, thumb_url, category path ("Fashion › Women's Clothing"), price range (taka, formatted), total_stock, stock_state (`in|low|out`), status, is_featured.
   - `ProductFormResource`: everything the 4 tabs need; prices in taka; images with ids and urls; `has_variants`; `heads` (id, name, values with id and text, in position order); `variants` (id, value texts in head order, sku, price, sale_price, stock, image id, status); simple products expose their default variant fields at the top level.
   - `create`/`edit` also pass: category tree (roots with children), `galleryMax`, `maxVariantHeads` (`shop.max_variant_heads`) and `maxVariants` (`shop.max_variants_per_product`).
8. Cache: `products.featured` (active + featured + in stock, limit 8, plain arrays: id, name, slug, thumb, srcset, price, sale_price, discount_percent). Cleared on every product write. Add `featuredProducts` to `HomeService::data()`.
9. Sidebar link is added in step 03.

## Tests (Feature, per testcase.md)

- Access matrix (testcase §7) for index and store.
- Create simple product → 1 default variant (no heads, no selections), 1 `initial` movement, main image + thumb files exist.
- Create variant product (2×2) → 2 heads, 4 values, 4 variants with selections and `combination_key`, 4 `initial` movements, SKUs auto-generated.
- Create variant product with a subset of combinations (3 of 4) → 3 variants.
- Update: stock 20 → 25 → one `manual_adjustment` +5; unchanged stock → no row.
- Update by id: renaming a value keeps its variants and their prices; adding a value + variants; removing a variant soft-deletes it, removes its selections and (with the value removed from the payload) deletes the unused value; swapping two combinations in one request succeeds.
- Switch variants on → default soft-deleted, heads/values/variants created; switch off → heads/values deleted, one default left.
- Validation dataset: non-leaf category, sale ≥ price, more heads than `shop.max_variant_heads`, duplicate head name, duplicate value in a head, variant value not in its head, variant with too few values, duplicate combination, ids of another product, variants over `max_variants_per_product`, gallery over limit, image too small/large/heavy, missing main image on create, `gallery_keep[]` containing the main image.
- Main image rule: update without `main_image` keeps the current one; replacing it removes the old files after commit; the product never ends up without or with two main images.
- Image replace and delete remove old files.
- Filters: search by SKU, category incl. children, stock in/out/low.
- Cache: product write clears only `products.featured`; HomeService returns featuredProducts.

## Done when

- `php artisan test`, `npx tsc --noEmit`, `vp lint` all exit 0.
- Commit: `feat: product admin backend`.
- Report only: changed files, three exit codes, commit hash.
