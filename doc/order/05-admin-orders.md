# Step 05 — Admin orders

Read `CLAUDE.md`, `doc/order/README.md` (§4) and open `mockups/05-admin-orders.png`, `06-admin-order-detail.png`. Step 02 must be DONE (03/04 recommended for real data).

**Scope:** admin order list, detail, status actions, notes, block phone.
**Start in plan mode.** Wait for approval.

## Backend

1. Routes (admin auth group): `orders.index`, `orders.show`, `POST orders/{order}/transition`, `POST orders/{order}/notes`, `POST customers/{customer}/block`, `DELETE customers/{customer}/block`.
2. `Admin\OrderController` (thin) → `OrderStatusService` / `CustomerService`.
3. Index filters: status (tabs with counts in one grouped query), search (number, name, phone), date range presets, source. Paginated; eager load customer + items count.
4. Show: order, items (snapshot fields + thumb), totals, customer card, customer history counts (delivered / cancelled / pending for this phone), status history with user names, `allowedNext` actions from the enum, UTM/fbclid.
5. Transition request: `to` must be in `allowedNext`; note required for cancelled/returned.
6. Shared prop `admin.pendingOrders` (count, cached 60s) for the sidebar badge.

## Frontend (match mockups)

- `admin/pages/Orders/Index.tsx`, `Show.tsx` thin; `admin/features/orders/`:
    - `StatusTabs` (counts), `OrderFilters` (URL synced), `OrderTable`,
    - `OrderItems`, `CustomerCard` (tel: link "Call", copy phone), `CustomerHistory`, `StatusTimeline`, `StatusActions` (primary = next normal step, cancel as danger with `ConfirmDialog` + note), note input, block/unblock button.
- Sidebar "Orders" with pending badge.
- Status badge colors from `OrderStatus::color()`.

## Tests

- Feature: access matrix; tab counts; filters; show props; every action button maps to an allowed transition; forbidden transition rejected; cancel restores stock and writes history; note added; block prevents a new order from that phone.
- Headless Chrome: open pending order → confirm → ship → deliver (payment becomes paid); another → cancel with note → stock back.

## Done when

- `php artisan test`, `npx tsc --noEmit`, `vp lint` all exit 0.
- Commit: `feat: admin orders`. Report only changed files, exit codes, E2E result, commit hash.
