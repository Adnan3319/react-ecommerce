# Step 04 — Cart

Read `CLAUDE.md`, `doc/order/README.md` (§6) and open `mockups/04-cart.png`. Step 03 must be DONE.

**Scope:** session cart, Add to Cart, cart page checkout.
**Start in plan mode.** Wait for approval.

## Backend

1. `CartService` (session key `cart`): `add(variantId, qty)`, `update(variantId, qty)`, `remove(variantId)`, `clear()`, `count()`, `lines()` (loads variants fresh from DB with product, image, current price, stock state, `price_changed` flag vs price stored at add time, `unavailable` flag).
2. Limits: `max_cart_lines`, `max_qty_per_item`, qty ≤ stock at add time.
3. Routes (throttle 30/min per IP): `GET /cart`, `POST /cart/items`, `PATCH /cart/items/{variant}`, `DELETE /cart/items/{variant}` → Inertia redirect back with flash.
4. Shared prop `cart.count` on storefront pages (cheap: count from session, no DB).
5. Cart checkout: `POST /orders` accepts `source=cart`; request builds items from `CartService::lines()` (never from the client), excluding unavailable lines; cart cleared after commit.

## Frontend

- `cart/AddToCartButton` on the product page (uses selected variant + qty), toast "Added to cart" with "View cart".
- Header cart icon shows `cart.count`.
- `storefront/pages/Cart.tsx` (mockup 04): lines with image, name, variant label, `QuantityInput`, line price, remove; price-changed and unavailable notices; same `OrderForm` + `OrderSummary` on the right; empty state with "Continue shopping".
- Quantity changes use partial reloads (`only: ['cart']`) and are debounced.

## Tests

- Feature: add / update / remove / limits; unavailable line excluded at checkout; price shown is current DB price; cart cleared after order; order source `cart`; client cannot inject a price or a variant not in the cart.
- Headless Chrome: add 2 products → cart → change qty → place order → success; cart count back to 0.

## Done when

- `php artisan test`, `npx tsc --noEmit`, `vp lint` all exit 0.
- Commit: `feat: cart checkout`. Report only changed files, exit codes, E2E result, commit hash.
