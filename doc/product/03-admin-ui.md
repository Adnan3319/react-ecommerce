# Step 03 — Product admin UI

Read `CLAUDE.md` and `doc/product/README.md` first, and open every image in `doc/product/mockups/`. Step 02 must be DONE (its "Request payload" section is the contract for this UI).

**Scope:** admin pages for products. Match the mockups (the mockups show two fixed option fields; build the head cards below instead, same look).

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
    - **Images** (`tab2-images.png`): main image required with Replace (it cannot be removed, the product always keeps one); gallery "Add more" until `galleryMax`, remove, drag to reorder, "x / max used" counter, helper text with limits.
    - **Price & stock** (`tab3-simple.png`, `tab3-variants.png`): "This product has variants" switch.
        - Off: price, sale price with `-x%` badge, stock, SKU (placeholder "Auto").
        - On, **variant heads**: one card per head with a name input (`Size`) and its value chips (Enter or comma adds, × removes, duplicates in the same head are ignored case-insensitively). "Add variant head" adds a card and is disabled once the number of heads reaches `maxVariantHeads` (from the page props, i.e. `shop.max_variant_heads`), with the helper text "Up to N variant heads". A head card can be removed. Adding chips is blocked with a warning when the combinations would exceed `maxVariants`.
        - On, **variant rows**: `useVariantMatrix` builds one row per combination of the heads' values and keeps already-typed row data when chips change. Rows are matched by value identity (the value id, or a client key for a new chip), not by text, so renaming a chip keeps its rows and their prices. A row removed by the admin stays removed until "Add missing combinations" is clicked. Row: image picker (from gallery and main image), label in head order ("M / Pink"), SKU (auto placeholder), price, sale price + badge, stock + out-of-stock badge, active switch, remove. "Set for all" bulk apply for price / sale / stock.
        - Turning the switch off keeps the heads/rows in memory until Save, so turning it back on restores them; Save with the switch off removes them on the server (step 02).
    - **SEO** (`tab4-seo.png`): meta title (60) and description (160) with counters; Google preview falls back to name / short description.
5. `useForm` with `forceFormData`; update sends `_method: 'put'`. The form state maps to step 02's payload: `heads[].{id?, name, values[].{id?, value}}` and `variants[].{id?, values[] (texts in head order), sku, price, sale_price, stock, image_ref, status}`. Errors shown under each field, including `heads.N.name`, `heads.N.values.M.value`, `heads` (too many heads) and `variants.N.field`; a head/value/variant error switches to tab 3.
6. Add "Products" to the admin sidebar.
7. Prices typed in taka; never compute totals client-side beyond display (discount badge).

## Verify

- `npx tsc --noEmit`, `vp lint`, `php artisan test` all exit 0.
- Headless Chrome:
    1. Create a simple product (with gallery image).
    2. Create a variant product with two heads (Size: M, L / Color: Pink, Black) → 4 rows, one variant image picked; "Add variant head" is disabled at the max; removing a chip removes its rows; renaming a chip keeps the typed price of its rows.
    3. Edit: change one variant stock, confirm a `stock_movements` row exists.
    4. Trigger a validation error on tab 3 while on tab 1 → form jumps to tab 3.
    5. Filters and pagination keep URL state.
    6. Delete via ConfirmDialog.
    7. Clean up created rows and files.
- Commit: `feat: product admin UI`.
- Update `CLAUDE.md`: Build Order (Product admin DONE) and Current Codebase State.
- Report only: changed files, exit codes, E2E result, commit hash.
