<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upload Disk
    |--------------------------------------------------------------------------
    | Disk used by App\Services\Media\FileUploadService (see config/filesystems.php).
    */
    'disk' => 'uploads',

    /*
    |--------------------------------------------------------------------------
    | Image Resizing
    |--------------------------------------------------------------------------
    | When disabled, uploaded images are stored as-is without resizing
    | (and without extra size variants).
    */
    'resize_enabled' => (bool) env('MEDIA_RESIZE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Image Presets
    |--------------------------------------------------------------------------
    | fit: "cover" crops to the exact size, "contain" fits the whole image and
    |      pads to the exact size (transparent), "scale_down" only shrinks to fit.
    | format: jpg | jpeg | png | webp
    | variants: extra files produced by the same upload, keyed by the filename
    |      suffix (uuid.webp + uuid-sm.webp). Each value is another preset name.
    */
    'presets' => [
        'default' => ['width' => 1600, 'height' => 1600, 'fit' => 'scale_down', 'format' => 'webp', 'quality' => 80],
        'logo' => ['width' => 512, 'height' => 512, 'fit' => 'scale_down', 'format' => 'png', 'quality' => 80],
        'category' => ['width' => 300, 'height' => 300, 'fit' => 'contain', 'format' => 'webp', 'quality' => 80],
        'slider' => [
            'width' => 1920, 'height' => 800, 'fit' => 'cover', 'format' => 'webp', 'quality' => 72,
            'variants' => ['sm' => 'slider_sm'],
        ],
        'slider_sm' => ['width' => 960, 'height' => 400, 'fit' => 'cover', 'format' => 'webp', 'quality' => 70],
        'promo' => [
            'width' => 1200, 'height' => 800, 'fit' => 'cover', 'format' => 'webp', 'quality' => 75,
            'variants' => ['sm' => 'promo_sm'],
        ],
        'promo_sm' => ['width' => 600, 'height' => 400, 'fit' => 'cover', 'format' => 'webp', 'quality' => 73],
        'product' => [
            'width' => 1200, 'height' => 1200, 'fit' => 'contain', 'format' => 'webp', 'quality' => 78,
            'variants' => ['thumb' => 'product_thumb'],
        ],
        'product_thumb' => ['width' => 400, 'height' => 400, 'fit' => 'contain', 'format' => 'webp', 'quality' => 75],
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Gallery
    |--------------------------------------------------------------------------
    | Extra images allowed per product besides the main image.
    */
    'product_gallery_max' => (int) env('MEDIA_PRODUCT_GALLERY_MAX', 3),
];
