# Step 05 — Search

Read `CLAUDE.md`, `doc/storefront/README.md`, and open `mockups/04-search.png`. Step 04 must be DONE.
**Start in plan mode**, wait for approval.

Step 04 already built the shared listing page (`ShopListing`), `GET /search`, `ProductFilter` and `CatalogService::listing()`. This step only adds **matching** and the **suggestion dropdown** on top. No new page, no second query path.

## Backend

1. Migration: FULLTEXT index on `products(name, short_description)` — MySQL only (skip on SQLite inside the migration).
2. Matching, inside `CatalogService::listing()` through a `SearchMatcher` collaborator (so filters, category, sort and pagination stay shared):
    - `ProductFilter` already normalizes `q` (trim, collapse spaces, max 100 chars); here add min 2 characters — shorter or empty means no matching condition (the page shows the "type to search" prompt only when `q` was given but too short).
    - Exact SKU match first (variant SKU → its product).
    - MySQL: `MATCH ... AGAINST` in boolean mode with prefix `*` per word; relevance ordering for the new "Best match" sort (default when `q` is set; otherwise Newest).
    - SQLite/tests: escaped LIKE fallback on name + short_description.
    - Active products only; the category filter and all other filters keep working with `q`.
3. `SearchService::suggest(term)`: ≤ 6 cards (name, category, thumb, price, url), cached 5 min per normalized term; uses the same matcher.
4. Route `GET /search/suggest` (JSON via `ApiResponse`, throttle 60/min per IP). `GET /search` stays as built in step 04.

## Frontend

- Upgrade `features/catalog/ListingSearchBox` (the box on the listing page) and add the **header** search box: debounced 250 ms fetch to the suggest endpoint (AbortController cancels stale requests), dropdown with highlighted match, keyboard ↑/↓/Enter/Esc, "See all N results" → `/search?q=` (and the current `category` when on a category page). The mobile header icon opens a full-width field.
- `pages/Search.tsx` stays the thin wrapper; the title becomes "Results for “q”" with the count; `EmptyResults` shows "Nothing found for “q”" with root-category links (already built in step 04).

## Tests

- Feature: SKU exact hit; name match; inactive excluded; `q` combined with category, price, in-stock and head filters; Best match vs other sorts; pagination keeps `q`; suggest JSON shape and limit; throttle; empty and too-short terms.
- MySQL group: FULLTEXT relevance (a name hit ranks above a description-only hit).
- Headless Chrome: type "dre" → suggestions appear → Enter → results page; Esc closes dropdown; searching from a category page keeps the category.

## Done when

`php artisan test`, `php artisan test -c phpunit-mysql.xml --group=mysql`, `npx tsc --noEmit`, `npx vp lint` exit 0. Commit `feat: product search`. Report changed files, exit codes, E2E result, hash.
