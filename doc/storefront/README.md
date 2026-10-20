# Storefront — Architecture

> Customer-facing pages: Landing, Category, Search, plus the admin pieces they need.
> Read with `CLAUDE.md`, `testcase.md`, `doc/product/README.md`, `doc/order/README.md`.
> Execute step files in order, one per session: `01` → `06`. Each step ends with tests, tsc, lint at exit 0 and a commit.

**Prerequisite:** product and order modules DONE.

---

## 1. Decisions (fixed)

| Topic            | Decision                                                                                                                                                                                                                                                                                                                                                                                         |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Static data      | Remove every hardcoded product/category array from storefront pages. All data comes from Inertia props.                                                                                                                                                                                                                                                                                          |
| Promo banner     | A slider with `position = promo`. Hero slides use `position = hero`. Same admin, upload rules and cache.                                                                                                                                                                                                                                                                                         |
| Testimonials     | Admin-managed (name, text, rating, source, active, sort). Section hidden when none are active. **Sample testimonials only in a local/dev seeder — never seeded in production.**                                                                                                                                                                                                                  |
| Product card     | Image and name link to the product page. Two buttons: **Order Now** (→ product page, where the existing Order Now form is) and **Add to Cart**. Simple product (one default variant) → Add to Cart adds directly. Variant product → Add to Cart opens a quick picker (modal on desktop, bottom sheet on mobile) to choose head values, then adds. Out of stock → "View" + badge, no Add to Cart. |
| Existing flows   | The product page keeps both Order Now and Add to Cart exactly as built in the order module. The cart page checkout is unchanged.                                                                                                                                                                                                                                                                 |
| Category filters | Price range, in-stock only and sort on every category. Variant-head filters (Size, Color…) appear **only** when products in that category have those heads — generated from data, no admin setting.                                                                                                                                                                                              |
| Sort             | Newest (default), price low→high, price high→low. Search adds "Best match".                                                                                                                                                                                                                                                                                                                      |
| Search           | MySQL FULLTEXT on product name + short description (+ SKU exact match). LIKE fallback on SQLite for tests. Suggestions via a JSON endpoint (`ApiResponse`).                                                                                                                                                                                                                                      |
| SEO              | Category pages indexable; filtered/sorted/paginated URLs canonical to the clean category URL (page 2+ keeps `?page`). Search results `noindex`.                                                                                                                                                                                                                                                  |
| Performance      | Landing and category first page cached (plain arrays), cleared on product/category/slider/testimonial writes. Product images: thumb + srcset, lazy except first row.                                                                                                                                                                                                                             |

---

## 2. Data changes

| Table                           | Change                                                                                                                                                               |
| ------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `sliders`                       | add `position` (`hero` / `promo`), index. Max 5 active per position.                                                                                                 |
| `testimonials` (new)            | `customer_name` (80), `text` (500), `rating` (1–5), `source` (`facebook`, `messenger`, `whatsapp`, `other`), `status` ActiveStatus, `sort_order`, audit, softDeletes |
| `products`                      | FULLTEXT index on (`name`, `short_description`) — MySQL only migration branch                                                                                        |
| `category_slug_redirects` (new) | like `product_slug_redirects`, for renamed categories                                                                                                                |

---

## 3. Pages and routes

| Route                                 | Page              | Data                                                                                                                       |
| ------------------------------------- | ----------------- | -------------------------------------------------------------------------------------------------------------------------- |
| `GET /`                               | `Home`            | HomeService: hero sliders, promo slider, categories (roots + children), featured (8), new arrivals (8), testimonials (≤ 6) |
| `GET /categories/{slug}`              | `Categories/Show` | category, children tabs, filters available, paginated products (12), active filters                                        |
| `GET /search?q=`                      | `Search`          | paginated results, category filter, sort                                                                                   |
| `GET /search/suggest?q=`              | JSON              | ≤ 6 products (name, thumb, price, url), throttled 60/min                                                                   |
| `GET /sitemap.xml`, `GET /robots.txt` | —                 | step 06                                                                                                                    |

Product card data shape (one Resource reused everywhere): id, name, slug, thumb, srcset, price, sale_price, discount_percent, options summary ("3 sizes · 2 colors"), in_stock, is_new (created ≤ 14 days), has_variants, default_variant_id (simple products only).

| Route                          | Purpose                                                                                                                                                                        |
| ------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `GET /products/{slug}/options` | JSON (`ApiResponse`) for the quick picker: heads, values, variants (id, value ids, effective price, stock state, thumb). Throttled 60/min, cached with the product page cache. |
| `POST /cart/items`             | existing cart endpoint, reused by the card and the quick picker                                                                                                                |

---

## 4. Filters (category page)

Query string: `?min=500&max=2500&in_stock=1&sort=price_asc&f[size]=m,l&f[color]=pink&page=2`.

- Available filters are computed for the current category (incl. children when the parent is selected):
    - price min/max of active variants,
    - each variant head name present (case-insensitive merge: "Size" and "size" are one filter) with its values and product counts.
- Filtering by head values: product matches when **one active, in-stock-or-not variant** has all selected values (AND across heads, OR within a head).
- Price filter uses variant effective price (sale ?? price).
- Invalid params are ignored, never 500.
- Counts and price bounds cached per category for 10 min.

---

## 5. Code layout

```
app/Services/Storefront/{CatalogService, ProductFilter, SearchService}.php
app/Services/Testimonial/TestimonialService.php
app/Http/Controllers/Storefront/{HomeController, CategoryController, SearchController}.php
app/Http/Controllers/Admin/TestimonialController.php
app/Http/Resources/Storefront/ProductCardResource.php

resources/js/storefront/
├── pages/{Home, Categories/Show, Search}.tsx          # thin
└── features/
    ├── home/      Hero (existing), TrustStrip, CategoryShowcase, ProductRail, PromoBanner, Testimonials
    ├── catalog/   ProductCard, ProductGrid, FilterSidebar, FilterSheet (mobile), ActiveFilters, SortSelect, SubCategoryTabs, useCatalogFilters
    └── search/    SearchBox (header, suggestions dropdown, keyboard nav), SearchResults, EmptyResults
resources/js/admin/features/testimonials/
```

---

## 6. Mockups (`mockups/`)

| File                                                | Step |
| --------------------------------------------------- | ---- |
| `01-landing.png`, `05-quick-add.png`                | 03   |
| `02-category.png`, `03-category-mobile-filters.png` | 04   |
| `04-search.png`                                     | 05   |

Testimonial text and names in the mockup are placeholders.

---

## 7. Steps

| File                    | Scope                                        | Commit                             |
| ----------------------- | -------------------------------------------- | ---------------------------------- |
| `01-slider-position.md` | slider `position` (hero / promo)             | `feat: slider positions`           |
| `02-testimonials.md`    | testimonials admin + model                   | `feat: testimonials`               |
| `03-landing.md`         | real Home page                               | `feat: landing page`               |
| `04-category-page.md`   | category page + filters                      | `feat: category page with filters` |
| `05-search.md`          | search page + suggestions                    | `feat: product search`             |
| `06-seo.md`             | sitemap, robots, JSON-LD, category redirects | `feat: storefront seo`             |

After all six: update `CLAUDE.md` Build Order and Current Codebase State.
