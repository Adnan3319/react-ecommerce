# Step 01 — Order data model

Read `CLAUDE.md`, `testcase.md` and `doc/order/README.md` first. Follow README §1–§4.

**Scope:** data layer only. No controllers, routes or UI.
**Start in plan mode.** Show tables, columns, indexes, enums and the transition map; wait for approval.

## Tasks

1. `config/shop.php`: add every key from README §2. `.env.example`: `SHOP_MAX_QTY_PER_ITEM`, `SHOP_ADMIN_ORDER_EMAIL`.
2. `App\Services\Order\DeliveryZones`: `all()`, `get(key)`, `charge(key)`, `keys()` — reads config, throws on unknown key.
3. Enums:
   - `OrderStatus` with `canTransitionTo(self)`, `allowedNext(): array`, `restoresStock(): bool`, `label()`, `color()` (for admin badges).
   - `PaymentStatus` (`unpaid`, `paid`), `PaymentMethod` (`cod`), `OrderSource` (`buy_now`, `cart`).
4. Migrations: `customers`, `orders`, `order_items`, `order_status_histories` exactly as README §3. `order_items` FKs to products/variants are `restrictOnDelete`.
5. Models with relations, casts (enums, money ints) and scopes: `Order::status()`, `Order::search(term)` (number, name, phone; escaped LIKE), `Order::placedBetween()`. `Customer::byPhone()`.
6. `App\Support\Phone::normalize(string): ?string` — accepts `01712345678`, `+8801712345678`, `8801712345678`, spaces/dashes; returns `01XXXXXXXXX` or null.
7. `OrderNumberGenerator`: `CK-YYMMDD-NNNN`, daily sequence, safe under concurrency (lock or unique retry). Public token: 32 random chars.
8. Factories: `Customer`, `Order` (states per status, `buyNow()`, `cart()`), `OrderItem`.
9. Add `restrictOnDelete` guard TODO from product README: a variant/product referenced by `order_items` cannot be force-deleted.

## Tests (Unit, per testcase.md)

- Every allowed transition passes; a dataset of forbidden ones fails (e.g. delivered → pending, cancelled → confirmed).
- `restoresStock()` true only for cancelled and returned.
- Phone normalization dataset (valid and invalid).
- Order number format and sequence (two orders same day → 0001, 0002; next day restarts).
- DeliveryZones reads config and throws on unknown key.

## Done when

- `php artisan test`, `npx tsc --noEmit`, `vp lint` all exit 0.
- Commit: `feat: order data model`. Report only changed files, exit codes, commit hash.
