# Step 06 — Storefront SEO

Read `CLAUDE.md` (SEO rules) and `doc/storefront/README.md` first. Steps 01–05 must be DONE.
**Start in plan mode**, wait for approval.

## Tasks

1. `GET /sitemap.xml`: home, active root + child categories, active products (lastmod = updated_at), static pages that exist. Cached 1 hour, cleared on product/category writes. Valid XML, `Content-Type: application/xml`.
2. `GET /robots.txt`: allow all, disallow `/admin`, `/cart`, `/orders`, `/search`, point to the sitemap. Production only allows indexing; non-production (`APP_ENV != production`) returns `Disallow: /` and pages get `noindex`.
3. Audit every storefront page's `seo` prop: unique title (≤ 60), description (≤ 160, fallback from content), canonical, og:title/description/image/url, twitter card. One `h1` per page.
4. JSON-LD check: Organization + WebSite (Home), BreadcrumbList (category, product), Product with offers (price, priceCurrency BDT, availability) on product pages — validate shape in tests.
5. `storefront.blade.php`: confirm meta tags render server-side from the `seo` prop on first load (view-source must show them without JS).
6. 404 page for the storefront (not the Laravel default), `noindex`, links to categories and search.

## Tests

- Sitemap contains only active items and is valid XML; robots differs by environment; every storefront route returns the required seo keys; JSON-LD shape per page type; 404 page renders.
- Headless Chrome with JS disabled: product and category pages still have title, description and og tags in the HTML.

## Done when

`php artisan test`, `npx tsc --noEmit`, `npx vp lint` exit 0. Commit `feat: storefront seo`. Update `CLAUDE.md` Build Order + Current Codebase State. Report changed files, exit codes, hash.
