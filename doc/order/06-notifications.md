# Step 06 — Notifications and dashboard

Read `CLAUDE.md` and `doc/order/README.md` first. Steps 01–05 must be DONE.

**Scope:** admin new-order email, real dashboard data.
**Start in plan mode.** Wait for approval.

## Tasks

1. `NotifyAdminOfNewOrder` job (database queue, `tries=3`, backoff): Markdown mail to `config('shop.admin_order_email')` with number, customer name + phone, area, items, total, link to the admin order. Skip silently when the email is not configured. Never send customer phone/address to logs.
2. Mail settings documented in `.env.example` (`MAIL_*`). On shared hosting the cron already runs `queue:work --stop-when-empty` (CLAUDE.md hosting section) — verify the schedule entry exists.
3. Replace TailAdmin demo widgets on the admin dashboard with real data (cached 5 min, cleared on order write):
   - Today: orders, revenue (delivered), pending count.
   - Last 30 days: orders per day (simple bar chart), delivered vs cancelled ratio.
   - Recent 5 orders (link to detail).
   - Low stock variants (≤ `shop.low_stock_threshold`) with link to the product.
4. Remove `EcommerceMetrics` / `RecentOrders` demo components.

## Tests

- Job: dispatched on place; mail contains number and total; not sent when email empty (`Mail::fake`).
- Dashboard: numbers match seeded orders exactly; cache cleared on order transition.

## Done when

- `php artisan test`, `npx tsc --noEmit`, `vp lint` all exit 0.
- Commit: `feat: order notifications`. Update `CLAUDE.md` Build Order + Current Codebase State. Report only changed files, exit codes, commit hash.
