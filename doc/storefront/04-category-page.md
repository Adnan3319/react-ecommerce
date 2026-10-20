# Step 04 — Category page with filters

Read `CLAUDE.md`, `doc/storefront/README.md` (§4 filters), and open `mockups/02-category.png`, `03-category-mobile-filters.png`. Step 03 must be DONE.
**Start in plan mode**: show the query plan (joins/subqueries for head filters and price) and the props shape; wait for approval.

## Backend

1. Route `GET /categories/{slug}` → `Storefront\CategoryController@show`. Inactive/missing → 404. Old slug → 301 (new `category_slug_redirects`, written when an admin changes a slug).
2. `ProductFilter` (pure, unit-testable): parses and validates query params per README §4, ignores invalid ones.
3. `CatalogService::categoryPage(Category, ProductFilter)`:
    - Parent selected → products of all its children (+ its own if it has none).
    - Filters: price (effective variant price), in-stock, head values (AND across heads, OR within a head), sort.
    - Paginate 12, `withQueryString()`.
    - Available filters (price bounds, heads with values + counts) per README §4, cached 10 min per category.
    - Eager load only what `ProductCardResource` needs; no N+1 (assert query count in a test).
4. Props: category (name, slug, parent, children for tabs), products (paginated cards), filters (available + active), sort options, `seo` (title, description, canonical = clean category URL, plus `?page=N` when paginated; BreadcrumbList JSON-LD; `noindex` when any filter or sort is active).

## Frontend (match mockups)

- `pages/Categories/Show.tsx` thin; `features/catalog/`: `SubCategoryTabs`, `FilterSidebar` (desktop), `FilterSheet` (mobile bottom sheet with "Show N products"), `ActiveFilters` (chips + Clear all), `SortSelect`, `ProductGrid`, `useCatalogFilters` (state ↔ URL via `router.get` with `preserveScroll`, `only: ['products','filters']`; price inputs debounced).
- Variant-head filter sections render only for heads returned by the backend (Kitchenware shows only Price, Availability, Sort).
- Color values: show a swatch when the value matches a known color name map, otherwise a chip.
- Empty result: message + "Clear filters".

## Tests

- Unit: `ProductFilter` parsing dataset (valid, invalid, out-of-range, unknown head).
- Feature: parent vs child category; each filter alone and combined; price uses sale price; sort orders; pagination keeps filters; available filters only list heads present; 404/301; noindex/canonical rules; query count bounded.
- Headless Chrome: Fashion → pick M + Pink → URL updates and grid filters; mobile sheet applies; Kitchenware shows no Size/Color.

## Done when

`php artisan test`, `npx tsc --noEmit`, `npx vp lint` exit 0. Commit `feat: category page with filters`. Report changed files, exit codes, E2E result, hash.
