# Step 02 — Checkout service

Read `CLAUDE.md`, `testcase.md` and `doc/order/README.md` first. Step 01 must be DONE.

**Scope:** services and their tests. No controllers or UI.
**Start in plan mode.** Show `place()` input/output, the transaction steps and exceptions; wait for approval.

## Tasks

1. `CheckoutService::place(CheckoutData $data): Order` exactly as README §5.
   - `CheckoutData` (readonly DTO): items `[variant_id, quantity]`, name, phone, address, delivery_zone, note, source, idempotency_key, utm fields, fbclid, ip, user_agent.
   - Errors as `ValidationException` with keys the form can show: `phone`, `items.N.quantity`, `items.N.variant`, `delivery_zone`, `order` (generic).
   - Variants locked in a fixed order (by id) to avoid deadlocks.
   - Stock changes only through `StockService` with reason `order_placed` and the order as reference.
   - Customer upsert by normalized phone; counters updated.
   - After commit: dispatch `NotifyAdminOfNewOrder` (job class can be a stub until step 06).
2. `OrderStatusService`:
   - `transition(Order, OrderStatus $to, ?string $note, ?User $by)` — validates via enum, applies README §4 side effects, writes history, all in one transaction with the order row locked.
   - `addNote(Order, string $note, User $by)` — history row without status change.
3. `CustomerService::block(phone)` / `unblock(phone)`.
4. No HTTP here; controllers come in steps 03–05.

## Tests (Unit, per testcase.md — exact numbers)

- Happy path: 2 lines, inside Dhaka → subtotal, delivery 7000, total correct; items snapshot; stock reduced; `order_placed` movements; history row; job dispatched.
- Client-sent price is ignored (DTO has no price; assert DB price used after changing it).
- Out of stock / quantity above stock → error on that line, nothing written.
- Quantity above `max_qty_per_item` → error.
- Inactive variant or inactive product → error.
- Blocked phone → error.
- Same idempotency key twice → one order, same order returned.
- Same phone + same items inside the duplicate window → existing order returned.
- 4th pending order for a phone within 24h → error.
- Concurrency: two placements for the last unit (simulate with two sequential calls after locking assumptions, or a `mysql` group test with real parallel connections) → only one succeeds.
- Transitions: cancel restores stock with `order_cancelled`; return restores with `order_returned`; delivered sets `paid` and counters; forbidden transition throws and changes nothing.

## Done when

- `php artisan test`, `npx tsc --noEmit`, `vp lint` all exit 0.
- Commit: `feat: checkout service`. Report only changed files, exit codes, commit hash.
