# Step 03 — Product page with Order Now

Read `CLAUDE.md`, `doc/order/README.md` and open `mockups/01-product-page.png`, `02-product-page-mobile.png`, `03-order-success.png`. Step 02 must be DONE.

**Scope:** storefront product page, Order Now form, success page.
**Start in plan mode.** Show routes, props shape and component list; wait for approval.

## Backend

1. Routes: `GET /products/{slug}` (`products.show`), `POST /orders` (`orders.store`, throttle 5/min per IP), `GET /orders/{token}/thank-you` (`orders.success`).
2. Old slugs: if a slug was changed, 301 to the current one (keep a `product_slug_redirects` table or document as TODO if not built yet — state which in the plan).
3. `Storefront\ProductController@show`: active product only (404 otherwise). Props: product (name, descriptions, images with srcset, heads + values, variants with id, value ids, effective price, sale price, discount, stock state, image id), breadcrumb, delivery zones (key, label, charge in taka), `maxQty`, `seo` (title, description, canonical, og image, Product + BreadcrumbList JSON-LD with price and availability).
4. Product page data cached per product (plain array, cleared on product write).
5. `PlaceOrderRequest`: name (2–80), phone (BD), address (10–500), delivery_zone (config keys), note (≤500), items (1 for buy now), honeypot `website` empty, idempotency_key (uuid), utm/fbclid optional. Builds `CheckoutData`; source `buy_now`.
6. `Storefront\OrderController@store` → `CheckoutService::place` → redirect to `orders.success`.
7. Capture UTM params and `fbclid` from the landing URL into the session (middleware) so they survive navigation until the order.
8. `@success`: order by `public_token` (404 otherwise), shows number, items, totals, phone to be called; `seo` noindex. Only works for 7 days after placing.

## Frontend (match mockups)

- `storefront/pages/Products/Show.tsx` thin; features:
  - `product/ProductGallery` (main + thumbs, first image priority, others lazy),
  - `product/VariantPicker` (one row per head, unavailable combinations disabled with strike-through, selection drives price, stock state and image),
  - `product/PriceTag`, `product/QuantityInput` (1..min(stock, maxQty)),
  - `order/OrderForm` (name, phone, address textarea, `DeliveryZonePicker` radios, collapsible note, `OrderSummary`, Confirm button with total, trust line). `useForm`, idempotency key generated once per form mount, button disabled while processing.
- Totals on screen are display only (server recalculates).
- Mobile: sticky bottom bar "Total · Order Now" that scrolls to the form (mockup 02).
- Do not render `Add to Cart` yet; step 04 adds it.
- `storefront/pages/Orders/Success.tsx` (mockup 03).
- Out-of-stock variant: Confirm disabled with "Out of stock".

## Tests

- Feature: product page 200 for active, 404 inactive/missing; props contain variants and zones; JSON-LD has price and availability.
- Feature: place order happy path redirects to success; validation errors (phone, address, zone, honeypot); throttle; success 404 for unknown token.
- Headless Chrome: pick variant → price/image change → place order → success page shows number; mobile viewport shows sticky bar.

## Done when

- `php artisan test`, `npx tsc --noEmit`, `vp lint` all exit 0.
- Commit: `feat: product page with order now`. Report only changed files, exit codes, E2E result, commit hash.
