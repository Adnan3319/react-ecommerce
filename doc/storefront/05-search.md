# Step 05 — Search

Read `CLAUDE.md`, `doc/storefront/README.md`, and open `mockups/04-search.png`. Step 04 must be DONE.
**Start in plan mode**, wait for approval.

## Backend

1. Migration: FULLTEXT index on `products(name, short_description)` — MySQL only (skip on SQLite inside the migration).
2. `SearchService::search(term, filters)`:
    - Normalize term (trim, collapse spaces, max 100 chars, min 2).
    - Exact SKU match first (variant SKU → its product).
    - MySQL: `MATCH ... AGAINST` in boolean mode with prefix `*` per word; relevance ordering for "Best match".
    - SQLite/tests: escaped LIKE fallback on name + short_description.
    - Active products only; category filter (incl. children); sort (best match default, newest, price asc/desc); paginate 12.
3. `SearchService::suggest(term)`: ≤ 6 cards (name, thumb, price, url), cached 5 min per normalized term.
4. Routes: `GET /search` (Inertia page, `seo` noindex, canonical `/search`), `GET /search/suggest` (JSON via `ApiResponse`, throttle 60/min per IP).
5. Empty term → page with prompt, no query.

## Frontend

- `features/search/SearchBox` in the header (desktop field; mobile icon opens a full-width field): debounced 250 ms fetch to the suggest endpoint (AbortController cancels stale requests), dropdown with highlighted match, keyboard ↑/↓/Enter/Esc, "See all N results" → `/search?q=`.
- `pages/Search.tsx` thin: title "Results for “q”", count, category filter, sort, `ProductGrid` (reuse), `EmptyResults` with links to root categories.

## Tests

- Feature: SKU exact hit; name match; inactive excluded; category filter; sort; pagination; suggest JSON shape and limit; throttle; empty and too-short terms.
- MySQL group: FULLTEXT relevance (a name hit ranks above a description-only hit).
- Headless Chrome: type "dre" → suggestions appear → Enter → results page; Esc closes dropdown.

## Done when

`php artisan test`, `php artisan test -c phpunit-mysql.xml --group=mysql`, `npx tsc --noEmit`, `npx vp lint` exit 0. Commit `feat: product search`. Report changed files, exit codes, E2E result, hash.
