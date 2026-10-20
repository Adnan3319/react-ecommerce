# Step 03 — Landing page

Read `CLAUDE.md`, `doc/storefront/README.md`, and open `mockups/01-landing.png`. Steps 01–02 must be DONE.
**Start in plan mode**: list every static array/sample file you will delete, the Home prop shape, and the components; wait for approval.

## Backend

1. `HomeService::data()` returns: `heroSlides`, `promo` (nullable), `categories` (roots with children: name, slug, image, children names/slugs), `featured` (8), `newArrivals` (8, newest active), `testimonials`. Product lists use `ProductCardResource`. All from caches (plain arrays).
2. `seo` prop: title, description, canonical `/`, og image (first hero slide), Organization + WebSite (with SearchAction → `/search?q=`) JSON-LD.
3. Caches cleared by product, category, slider and testimonial writes (already per-key; verify the new keys).

## Frontend (match the mockup)

- `pages/Home.tsx` thin. Sections in `features/home/`: Hero (existing optimized hero — keep its loading strategy), `TrustStrip` (COD, delivery charges read from shared delivery zones, 7-day return, support), `CategoryShowcase` (parent cards with sub-category chips linking to `/categories/{child}`), `ProductRail` (used for Featured and New Arrivals, "View all" → category or search), `PromoBanner` (hidden when null), `Testimonials` (hidden when empty).
- `features/catalog/ProductCard` (new, reused by steps 04–05): image with srcset, discount badge, "New" badge, options summary, price + strike price, two buttons **Order Now** (link to the product page) and **Add to Cart**, out-of-stock state ("View", no Add to Cart).
    - Simple product: Add to Cart posts `default_variant_id`, qty 1, to the existing cart endpoint; reuse the cart toast ("Added to cart · View cart") and the header count.
    - Variant product: Add to Cart opens `features/catalog/QuickAddPicker` (modal desktop, bottom sheet mobile) that loads `GET /products/{slug}/options` once, reuses the product page's `VariantPicker` (unavailable combinations disabled), shows price for the selection and an "Add to Cart" button; on success closes and shows the toast.
    - Backend for the picker: `Storefront\ProductOptionsController` → JSON via `ApiResponse`, from the product page cache.
- Do not change the product page Order Now / Add to Cart or the cart checkout; reuse their hooks (`useAddToCart`, `VariantPicker`).
- Delete all hardcoded product/category/sample arrays and their types/files from the storefront. Search the codebase for leftovers and list them in the report.
- Images: first row eager, the rest lazy; explicit width/height (no layout shift).
- Header nav "Kitchenware" / "Fashion" link to real category pages (roots from shared props).

## Tests

- Feature: Home props shape; promo null hides; no testimonials → empty array; only active/in-stock rules respected; cache cleared on product write.
- Feature: options endpoint shape, 404 for inactive product, throttle.
- Headless Chrome: Home renders with seeded data, every product/category link returns 200, no console errors, no layout shift on load (images have dimensions). Card Add to Cart on a simple product → count +1; on a variant product → picker → choose values → count +1; Order Now on a card opens the product page.

## Done when

`php artisan test`, `npx tsc --noEmit`, `npx vp lint` exit 0. Commit `feat: landing page`. Report changed files, removed static files, exit codes, E2E result, hash.
