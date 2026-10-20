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
];
