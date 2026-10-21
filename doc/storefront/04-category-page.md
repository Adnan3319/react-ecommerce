# Step 04 — Shared listing page (category) with filters

Read `CLAUDE.md`, `doc/storefront/README.md` (§4 filters), and open `mockups/02-category.png`, `03-category-mobile-filters.png`, `04-search.png`. Step 03 must be DONE.
**Start in plan mode**: show the query plan (joins/subqueries for head filters and price) and the props shape; wait for approval.

## One page for categories and search

Category pages and the search page are **the same page component** (`features/catalog/ShopListing`) and the **same backend query** (`CatalogService::listing()` + `ProductFilter`). They differ only in the route, the preselected category, the SEO rules and the heading:

| Route                           | Inertia page        | Category           | SEO                                                                |
| ------------------------------- | ------------------- | ------------------ | ------------------------------------------------------------------ |
| `GET /categories/{slug}`        | `Categories/Show`   | fixed by the route | indexable, canonical rules below                                   |
| `GET /search?q=&category=&...`  | `Search`            | optional `category` | always `noindex`, canonical `/search`                             |

Shared layout (top to bottom): breadcrumbs, heading + count, **search box prefilled with `q`**, sub-category tabs (when the selected category has children), filter sidebar (desktop) / filter sheet (mobile), active-filter chips + Clear all, sort, product grid, pagination.

- Typing a search on a category page submits to `/search?q=…&category={slug}` — the category filter is kept. Clearing the category tab on `/search` removes `category`.
- Step 04 builds the whole page, the `q` input and the `q` URL parameter, but `q` does **no matching yet** (`ProductFilter` parses and normalizes it; `CatalogService` ignores it). Step 05 adds the matching and the suggestion dropdown on top.

## Backend

1. Route `GET /categories/{slug}` → `Storefront\CategoryController@show`. Inactive/missing → 404 (no slug redirects for categories: category URLs are not shared outside the site). `GET /search` → `Storefront\SearchController@index` (replaces the `Route::inertia` stub) using the same service.
2. `ProductFilter` (pure, unit-testable): parses and validates query params per README §4 plus `q` (trim, collapse spaces, max 100 chars; step 05 applies it) and `category` (slug; unknown → ignored). Invalid params are ignored.
3. `CatalogService::listing(?Category, ProductFilter)`:
    - Parent selected → products of all its children (+ its own if it has none). No category → all active products.
    - Filters: price (effective variant price), in-stock, head values (AND across heads, OR within a head), sort. Step 05 adds the `q` condition and "Best match" sort in this same query.
    - Paginate 12, `withQueryString()`.
    - Available filters (price bounds, heads with values + counts) per README §4, cached 10 min per category (and one key for "all products").
    - Eager load only what `ProductCardResource` needs; no N+1 (assert query count in a test).
4. Props (same shape on both routes): `category` (name, slug, parent, children for tabs; null on search without a category), `products` (paginated cards), `filters` (available + active, incl. `q`, `category`), `sortOptions`, `seo`. Category SEO: title, description, canonical = clean category URL plus `?page=N` when paginated, BreadcrumbList JSON-LD, `noindex` when any filter or sort is active. Search SEO: `noindex`, canonical `/search`.

## Frontend (match mockups)

- `pages/Categories/Show.tsx` and `pages/Search.tsx` are thin wrappers around `ShopListing`.
- `features/catalog/`: `ShopListing`, `ListingSearchBox` (plain submit now; step 05 adds suggestions), `SubCategoryTabs`, `FilterSidebar` (desktop), `FilterSheet` (mobile bottom sheet with "Show N products"), `ActiveFilters` (chips + Clear all), `SortSelect`, `ProductGrid`, `Pagination` (real links), `EmptyResults`, `useCatalogFilters` (state ↔ URL via `router.get` with `preserveScroll`, `only: ['products','filters','category','seo']`; price inputs debounced). The current URL path stays (`/categories/x` or `/search`).
- Variant-head filter sections render only for heads returned by the backend (Kitchenware shows only Price, Availability, Sort).
- Color values: show a swatch when the value matches a known color name map, otherwise a chip.
- Empty result: message + "Clear filters", and links to the root categories.
- `categoryHref` in `lib/category-href.ts` returns `/categories/{slug}` (Wayfinder); remove the old `/search?category=` usage and the old `features/search` code.
- Deleted `public/images/products` and `public/images/product` (unreferenced sample photos).

## Tests

- Unit: `ProductFilter` parsing dataset (valid, invalid, out-of-range, unknown head, `q` normalization).
- Feature: parent vs child category; each filter alone and combined; price uses sale price; sort orders; pagination keeps filters; available filters only list heads present; 404; noindex/canonical rules; `/search` renders the same props shape, is `noindex`, keeps `category`; query count bounded.
- Headless Chrome: Fashion → pick M + Pink → URL updates and grid filters; mobile sheet applies; Kitchenware shows no Size/Color; typing in the search box on a category page lands on `/search?q=…&category=…`.

## Done when

`php artisan test`, `npx tsc --noEmit`, `npx vp lint` exit 0. Commit `feat: category page with filters`. Report changed files, exit codes, E2E result, hash.
