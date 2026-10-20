# Order Module — Architecture

> Source of truth for placing and managing orders (Cash on Delivery, guest checkout).
> Read with `CLAUDE.md`, `testcase.md` and `doc/product/README.md`.
> Execute step files in order, one per session: `01` → `06`. Each step ends with tests, tsc and lint at exit 0 and a commit.

**Prerequisite:** product module steps 01–03 DONE.

---

## 1. Decisions (fixed)

| Topic | Decision |
|---|---|
| Payment | Cash on Delivery only. `PaymentMethod` enum has `cod`; gateways later without changing the order flow. |
| Account | Guest only: name, phone, address. Customer is identified by phone. |
| Flows | **Buy now** (form on the product page, one variant) and **Cart** (session cart, form on the cart page). Both use the same `OrderForm` component and the same `CheckoutService`. |
| Delivery area | From `config('shop.delivery_zones')` (key → label + charge). No admin UI yet. |
| Free delivery | Not now. |
| Max quantity | `config('shop.max_qty_per_item')` per order line. |
| Stock | Decreases when the order is placed (status `pending`). Restored on `cancelled` and `returned`. |
| Address | One textarea + delivery area radio. |
| Customer SMS | Later (needs a paid gateway). Admin gets a queued email now. |
| Prices | Always recalculated on the server from the DB. Client totals are display only. |
| Snapshot | Order lines store product name, variant label, SKU, unit price at purchase time. |

---

## 2. Config (`config/shop.php`)

```php
'delivery_zones' => [
    'inside_dhaka'  => ['label' => 'Inside Dhaka',  'charge' => 7000],   // minor units
    'outside_dhaka' => ['label' => 'Outside Dhaka', 'charge' => 13000],
],
'max_qty_per_item'          => env('SHOP_MAX_QTY_PER_ITEM', 10),
'max_cart_lines'            => 20,
'max_pending_per_phone'     => 3,      // per 24h
'duplicate_window_minutes'  => 10,
'order_number_prefix'       => 'CK',
'admin_order_email'         => env('SHOP_ADMIN_ORDER_EMAIL'),
```

Changing a zone charge = edit config + `php artisan config:cache` on deploy. Orders keep the charge they were placed with (snapshot).

---

## 3. Data model

```
customers 1──n orders 1──n order_items n──1 product_variants
                 │
                 └──1──n order_status_histories
```

### customers
| Column | Notes |
|---|---|
| name | last used name |
| phone | unique, normalized `01XXXXXXXXX` |
| is_blocked | bool, blocked phones cannot order |
| orders_count, delivered_count, cancelled_count | counters for the admin "order history" card |
| timestamps | |

### orders
| Column | Notes |
|---|---|
| number | unique, `CK-YYMMDD-NNNN` (daily sequence) |
| public_token | unique, random 32 chars — used in the success page URL, never the id |
| customer_id | FK |
| status | `OrderStatus` |
| payment_method | `PaymentMethod` (`cod`) |
| payment_status | `PaymentStatus` (`unpaid`, `paid`) |
| source | `OrderSource` (`buy_now`, `cart`) |
| subtotal, delivery_charge, total | minor units |
| shipping_name, shipping_phone, shipping_address | snapshot |
| delivery_zone, delivery_zone_label | snapshot |
| customer_note | nullable, 500 |
| idempotency_key | unique, from the form |
| utm_source, utm_medium, utm_campaign, fbclid | nullable |
| ip_address, user_agent | |
| confirmed_at, shipped_at, delivered_at, cancelled_at | nullable |
| timestamps | |

Indexes: status + created_at, customer_id, shipping_phone, created_at.

### order_items
| Column | Notes |
|---|---|
| order_id, product_id, product_variant_id | FKs (restrict delete) |
| product_name, variant_label, sku, image_path | snapshot |
| unit_price, quantity, line_total | minor units |

### order_status_histories
| Column | Notes |
|---|---|
| order_id | FK |
| from_status (null for placed), to_status | |
| note | nullable |
| changed_by | user id, null = customer/system |
| created_at | |

### order_number_sequences
| Column | Notes |
|---|---|
| day | primary key, `YYMMDD` |
| last_number | last sequence handed out that day |

One row per day. It is the only source of order numbers (see §5).

---

## 4. Status rules (`OrderStatus` enum owns them)

```
pending ──► confirmed ──► processing ──► shipped ──► delivered ──► returned
   │            │              │            │                         ▲
   └────────────┴──────────────┴──► cancelled└─────────────────────────┘
                                         (shipped → returned: refused / courier return)
```

`cancelled` is possible from `pending`, `confirmed` and `processing` only. Once shipped, the only ways out are `delivered` or `returned`. `cancelled` and `returned` are final.

| Transition | Side effect |
|---|---|
| → cancelled | stock restored (`order_cancelled` movements), `cancelled_at` |
| shipped → returned | customer refused at delivery / courier return: stock restored (`order_returned`), payment stays `unpaid` |
| delivered → returned | stock restored (`order_returned`), payment stays `paid` (refund is manual) |
| → delivered | `payment_status = paid`, `delivered_at`, customer `delivered_count++` |
| → confirmed / shipped | timestamp set |

Any other transition throws `InvalidOrderTransition`. Every change writes `order_status_histories`. All inside `DB::transaction`.

---

## 5. Placing an order (`CheckoutService::place`)

One `DB::transaction`:

1. Reject when the idempotency key already exists → return that order (double click safe).
2. Normalize phone; reject blocked phone.
3. Abuse checks: pending orders for this phone in 24h < `max_pending_per_phone`; same phone + same items within `duplicate_window_minutes` → return the existing order.
4. Load variants with `lockForUpdate()`; each must be active, its product active, quantity 1..`max_qty_per_item`, stock ≥ quantity. Otherwise a validation error naming the item.
5. Price from DB (`effective_price`); delivery charge from config zone; totals computed in minor units.
6. Upsert customer by phone.
7. Create order (number, token) + items (snapshot) + `placed` history row. The number comes from `OrderNumberGenerator::next()` (below).
8. Stock −qty per line via `StockService` (`order_placed`, reference = order).
9. After commit: clear cart (cart flow), dispatch `NotifyAdminOfNewOrder` job.

**Order numbers (`OrderNumberGenerator`).** `CK-YYMMDD-NNNN` comes from the day's row in `order_number_sequences`, taken inside the order transaction: on MySQL one atomic `INSERT ... ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1)` creates or increments the row and keeps its lock until commit. Concurrent orders therefore get different numbers, and a rolled-back order gives its number back (no gaps). The first number of a day starts after the highest existing `orders.number` of that day. Do not compute a number from `MAX(number)`: under MySQL's transaction snapshot parallel requests cannot see each other's orders and would all pick the same one. `createWithNumber()` still retries on a unique violation of `orders.number` (only possible for orders created outside the generator). Verified by `tests/Mysql/CheckoutConcurrencyTest.php`.

Rate limits (route level): order POST 5/min per IP.
Honeypot field `website` must be empty.

---

## 6. Cart

- Server-side, session based: `[variant_id => qty]`. No DB table for guests.
- Shared prop `cart.count` on every storefront page.
- Cart page always shows current DB prices; if a price changed since adding, show a notice.
- Lines whose variant became inactive / out of stock are flagged and excluded from checkout.
- Limits: `max_cart_lines`, `max_qty_per_item`.

---

## 7. Code layout

```
app/
├── Enums/{OrderStatus, PaymentStatus, PaymentMethod, OrderSource}.php
├── Models/{Customer, Order, OrderItem, OrderStatusHistory}.php
├── Services/Order/
│   ├── CheckoutService.php       # place()
│   ├── OrderStatusService.php    # transition(), addNote()
│   ├── OrderNumberGenerator.php
│   ├── DeliveryZones.php         # reads config
│   └── CartService.php
├── Exceptions/InvalidOrderTransition.php
├── Jobs/NotifyAdminOfNewOrder.php
├── Http/Controllers/Storefront/{ProductController, CartController, OrderController}.php
├── Http/Controllers/Admin/OrderController.php
└── Http/Requests/{Storefront/PlaceOrderRequest, Storefront/CartItemRequest, Admin/Order/*}.php

resources/js/storefront/
├── pages/{Products/Show, Cart, Orders/Success}.tsx
└── features/
    ├── product/  VariantPicker, ProductGallery, PriceTag, QuantityInput
    ├── order/    OrderForm, OrderSummary, DeliveryZonePicker
    └── cart/     CartLine, AddToCartButton, useCart

resources/js/admin/
├── pages/Orders/{Index, Show}.tsx
└── features/orders/  OrderTable, OrderFilters, StatusTabs, OrderItems, CustomerCard, CustomerHistory, StatusTimeline, StatusActions
```

---

## 8. Mockups (`mockups/`)

| File | Step |
|---|---|
| `01-product-page.png`, `02-product-page-mobile.png` | 03 |
| `03-order-success.png` | 03 |
| `04-cart.png` | 04 |
| `05-admin-orders.png`, `06-admin-order-detail.png` | 05 |

Names and phone numbers in the mockups are sample data.

---

## 9. Steps

| File | Scope | Commit |
|---|---|---|
| `01-data-model.md` | config, enums, migrations, models, status rules, unit tests | `feat: order data model` |
| `02-checkout-service.md` | CheckoutService, OrderStatusService, number generator, abuse checks, tests | `feat: checkout service` |
| `03-product-page.md` | storefront product page, Order Now form, success page | `feat: product page with order now` |
| `04-cart.md` | session cart, add to cart, cart page checkout | `feat: cart checkout` |
| `05-admin-orders.md` | admin order list, detail, status actions, block phone | `feat: admin orders` |
| `06-notifications.md` | admin email job, dashboard widgets with real data | `feat: order notifications` |

After all six: update `CLAUDE.md` Build Order and Current Codebase State.
