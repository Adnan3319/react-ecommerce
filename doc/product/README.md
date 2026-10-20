# Product Module — Architecture

> Source of truth for products, variants and stock. Read with `CLAUDE.md` and `testcase.md`.
> Execute the step files in order, one per session: `01` → `02` → `03`.
> Each step ends with tests, tsc and lint at exit 0 and a commit.

**Prerequisite:** two-level categories (`categories.parent_id`) must be DONE.

---

## 1. Decisions (fixed)

| Topic | Decision |
|---|---|
| Variants | Every product has ≥ 1 variant. A simple product = one default variant with null option values. Price and stock live only on variants. |
| Options | Max 2 options per product. Names are free text set by the admin (`Size`, `Color`, `Capacity`, `Material`…). |
| Discount | `sale_price` per variant, nullable, must be `< price`. No sale start/end dates yet. |
| Money | Integer minor units (paisa) in DB. Admin forms use taka; conversion happens in the FormRequest. |
| Images | 1 main (required) + gallery up to `config('media.product_gallery_max')` (default 3). |
| Variant image | Optional, chosen from the product's own images (no extra upload). |
| SKU | Optional input; auto-generated when empty, globally unique. |
| Category | Product belongs to a **leaf** category (a sub-category, or a parent that has no children). |
| Stock history | Every stock change writes a `stock_movements` row. Stock is never edited without one. |
| Delete | Soft delete. Variants/products with orders can never be hard-deleted (guard added when orders exist). |
| Description | Plain text (line breaks kept). Rich text later, only with server-side HTML purifier. |

---

## 2. Data model

```
categories (existing, 2 levels)
   │ 1
   │
   │ n
products ───────────1:n──────── product_images
   │ 1                              ▲ 0..1
   │ n                              │
product_variants ──────────────────┘  (product_image_id, same product only)
   │ 1
   │ n
stock_movements
```

### products
| Column | Type | Notes |
|---|---|---|
| id | bigint | |
| category_id | FK | leaf category, indexed |
| name | string(150) | |
| slug | string, unique | unique incl. soft-deleted rows |
| short_description | string(300), null | product cards |
| description | text, null | plain text |
| option1_name | string(30), null | e.g. Size |
| option2_name | string(30), null | e.g. Color |
| status | ActiveStatus | indexed |
| is_featured | bool | indexed, home page |
| meta_title | string(60), null | falls back to name |
| meta_description | string(160), null | falls back to short_description |
| created_by / updated_by / deleted_by | FK users | HasAuditFields |
| timestamps, deleted_at | | |

### product_images
| Column | Type | Notes |
|---|---|---|
| product_id | FK | indexed |
| path | string | main file; `-thumb` variant beside it |
| is_main | bool | exactly one per product |
| sort_order | smallint | gallery order |

### product_variants
| Column | Type | Notes |
|---|---|---|
| product_id | FK | indexed |
| option1_value / option2_value | string(50), null | null on the default variant |
| sku | string(64), unique | auto when empty |
| price | unsigned int | minor units |
| sale_price | unsigned int, null | `< price` |
| stock | unsigned int | never negative |
| product_image_id | FK, null | must belong to same product |
| status | ActiveStatus | |
| sort_order | smallint | |
| deleted_at | | soft delete |
| unique | (product_id, option1_value, option2_value) | |

### stock_movements
| Column | Type | Notes |
|---|---|---|
| product_variant_id | FK | index with created_at |
| quantity | signed int | +5 / −2 |
| reason | StockMovementReason | initial, manual_adjustment, order_placed, order_cancelled, order_returned |
| reference_type / reference_id | null | order link later |
| note | string, null | |
| created_by | FK users, null | |
| created_at | | no updated_at: rows are never edited |

---

## 3. Images

| Preset | Size | Fit | Format | Used for |
|---|---|---|---|---|
| `product` | 1200×1200 | contain | webp q78 | product page |
| `product` → `thumb` | 400×400 | contain | webp q75 | cards, admin list, variant picker |

Upload rule: `image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:min_width=600,min_height=600,max_width=5000,max_height=5000`.
All uploads go through `FileUploadService`; old files are deleted only after the DB transaction commits.

---

## 4. Stock rules

- All stock changes go through `App\Services\Product\StockService` — never `$variant->update(['stock' => …])`.
- Create product → `initial` movement per variant with its starting stock.
- Admin edits stock 20 → 25 → one `manual_adjustment` row with `+5`.
- Orders (later): `order_placed` −qty inside the order transaction with `lockForUpdate()`; `order_cancelled` / `order_returned` +qty.
- `stock` column = current value; `stock_movements` = history. Sum of movements must always equal `stock`.
- Low stock threshold: `config('shop.low_stock_threshold')`, default 5.

---

## 5. Code layout

```
app/
├── Enums/StockMovementReason.php
├── Models/{Product, ProductImage, ProductVariant, StockMovement}.php
├── Services/Product/
│   ├── ProductService.php        # create/update/delete, variant sync, images, cache
│   ├── StockService.php          # the only place stock changes
│   └── SkuGenerator.php
├── Http/Controllers/Admin/ProductController.php
├── Http/Requests/Admin/Product/{SaveProductRequest, IndexProductRequest}.php
└── Http/Resources/Admin/Product/{ProductListResource, ProductFormResource}.php

resources/js/admin/
├── pages/Products/{Index, Create, Edit}.tsx       # thin
└── features/products/
    ├── ProductManager.tsx       # index body
    ├── ProductTable.tsx
    ├── ProductFilters.tsx
    ├── ProductForm.tsx          # tabs shell, useForm
    ├── BasicInfoTab.tsx
    ├── ImagesTab.tsx
    ├── PriceStockTab.tsx
    ├── VariantTable.tsx
    ├── SeoTab.tsx
    ├── useVariantMatrix.ts      # option chips → variant rows
    ├── useProductFilters.ts
    └── types.ts
```

---

## 6. Admin UI (see `mockups/`)

| Tab | Mockup | Contents |
|---|---|---|
| 1 Basic info | `tab1-basic.png` | name, slug, short description, description, Active, Featured, category radio groups |
| 2 Images | `tab2-images.png` | main image (required), gallery "Add more" up to limit, counter, reorder |
| 3 Price & stock | `tab3-simple.png`, `tab3-variants.png` | switch off: price/sale/stock/SKU; on: options, chips, variant table, bulk apply |
| 4 SEO | `tab4-seo.png` | meta title/description with counters, Google preview |

Tab states: done (green), active (blue), error (red). On save errors, jump to the first tab with an error.

---

## 7. Steps

| File | Scope | Commit |
|---|---|---|
| `01-data-model.md` | migrations, models, enums, factories, SKU generator, unit tests | `feat: product data model` |
| `02-admin-backend.md` | services, controller, requests, resources, cache, feature tests | `feat: product admin backend` |
| `03-admin-ui.md` | index page, 4-tab form, E2E | `feat: product admin UI` |

After all three: update `CLAUDE.md` Build Order and Current Codebase State.
