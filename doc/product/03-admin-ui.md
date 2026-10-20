# Step 03 — Product admin UI

Read `CLAUDE.md` and `docs/product/README.md` first, and open every image in `docs/product/mockups/`. Step 02 must be DONE.

**Scope:** admin pages for products. Match the mockups.

**Start in plan mode.** Show the component list and the form state shape; wait for approval.

## Tasks

1. Pages (thin): `admin/pages/Products/Index.tsx`, `Create.tsx`, `Edit.tsx`. Create and Edit share `ProductForm`.
2. Feature folder `admin/features/products/` as README §5 (flat; `components/` subfolder only if > 10 files). Reuse `admin/components/ui` and `admin/components/common` (ConfirmDialog, StatusToggle, FlashMessage, Pagination).
3. Index:
   - Table: thumb, name, category path, price range, stock with `low` / `out` badges, status toggle, featured toggle, edit, delete via ConfirmDialog.
   - Filters synced to URL (`router.get`, `preserveState`): search (debounced), category, status, featured, stock.
   - "Add product" button.
4. Form — 4 tabs (`ProductForm` + one file per tab):
   - Tab header: done (green), active (blue), error (red). After a failed save, switch to the first tab with an error. Back / Next buttons per tab; Save always in the page header.
   - **Basic info** (`tab1-basic.png`): name; slug auto-filled from name until edited; short description with counter; description; Active and Featured switches; category as radio groups — parents as headers with their children as radios, a parent without children is a radio itself; "Selected: Parent › Child" line.
   - **Images** (`tab2-images.png`): main image required with Replace; gallery "Add more" until `galleryMax`, remove, drag to reorder, "x / max used" counter, helper text with limits.
   - **Price & stock** (`tab3-simple.png`, `tab3-variants.png`): "This product has variants" switch.
     - Off: price, sale price with `-x%` badge, stock, SKU (placeholder "Auto").
     - On: option 1 / option 2 name + value chips (Enter adds, × removes). `useVariantMatrix` builds rows from the combinations and keeps already-typed values when chips change. Row: image picker (from gallery), label, SKU (auto placeholder), price, sale price + badge, stock + out-of-stock badge, active switch, remove. "Set for all" bulk apply for price / sale / stock.
   - **SEO** (`tab4-seo.png`): meta title (60) and description (160) with counters; Google preview falls back to name / short description.
5. `useForm` with `forceFormData`; update sends `_method: 'put'`. Errors shown under each field, including `variants.N.field`.
6. Add "Products" to the admin sidebar.
7. Prices typed in taka; never compute totals client-side beyond display (discount badge).

## Verify

- `npx tsc --noEmit`, `vp lint`, `php artisan test` all exit 0.
- Headless Chrome:
  1. Create a simple product (with gallery image).
  2. Create a variant product 2×2 with one variant image picked.
  3. Edit: change one variant stock, confirm a `stock_movements` row exists.
  4. Trigger a validation error on tab 3 while on tab 1 → form jumps to tab 3.
  5. Filters and pagination keep URL state.
  6. Delete via ConfirmDialog.
  7. Clean up created rows and files.
- Commit: `feat: product admin UI`.
- Update `CLAUDE.md`: Build Order (Product admin DONE) and Current Codebase State.
- Report only: changed files, exit codes, E2E result, commit hash.
