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
    | When disabled, uploaded images are stored as-is without resizing.
    */
    'resize_enabled' => (bool) env('MEDIA_RESIZE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Image Presets
    |--------------------------------------------------------------------------
    | fit: "cover" crops to the exact size, "scale_down" only shrinks to fit.
    | format: jpg | jpeg | png | webp
    */
    'presets' => [
        'default' => ['width' => 1600, 'height' => 1600, 'fit' => 'scale_down', 'format' => 'webp', 'quality' => 80],
        'category' => ['width' => 800, 'height' => 800, 'fit' => 'cover', 'format' => 'webp', 'quality' => 80],
    ],
];
