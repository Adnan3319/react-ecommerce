<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    | A variant with this many units or fewer counts as "low stock" in the admin.
    */
    'low_stock_threshold' => (int) env('SHOP_LOW_STOCK_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Variant Heads Per Product
    |--------------------------------------------------------------------------
    | How many option names (Size, Color, ...) one product may have. A business rule,
    | not a schema limit: the variant_heads table already supports any number.
    */
    'max_variant_heads' => 2,

    /*
    |--------------------------------------------------------------------------
    | Variants Per Product
    |--------------------------------------------------------------------------
    | Upper bound on the variant rows of one product, so a cartesian explosion
    | (3 heads x 20 values) cannot be submitted.
    */
    'max_variants_per_product' => 100,

    /*
    |--------------------------------------------------------------------------
    | Delivery Zones
    |--------------------------------------------------------------------------
    | key => label + charge (minor units). Orders keep the charge they were placed
    | with; after changing a charge run `php artisan config:cache` on deploy.
    */
    'delivery_zones' => [
        'inside_dhaka' => ['label' => 'Inside Dhaka', 'charge' => 7000],
        'outside_dhaka' => ['label' => 'Outside Dhaka', 'charge' => 13000],
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Limits
    |--------------------------------------------------------------------------
    */
    'max_qty_per_item' => (int) env('SHOP_MAX_QTY_PER_ITEM', 10),
    'max_cart_lines' => 20,

    // Pending orders one phone may have per 24 hours.
    'max_pending_per_phone' => 3,

    // Same phone + same items inside this window returns the existing order.
    'duplicate_window_minutes' => 10,

    'order_number_prefix' => 'CK',

    // Receives the "new order" email; no mail is sent when empty.
    'admin_order_email' => env('SHOP_ADMIN_ORDER_EMAIL'),
];
