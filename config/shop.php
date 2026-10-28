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
    | Zone keys plus fallback label and charge (minor units), used only until the
    | company row exists. The live label ("Inside <city>") and charges are set in the
    | admin: Company settings -> Delivery Charge. Orders keep the charge they were
    | placed with.
    */
    'delivery_zones' => [
        'inside' => ['label' => 'Inside City', 'charge' => 7000],
        'outside' => ['label' => 'Outside City', 'charge' => 13000],
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
