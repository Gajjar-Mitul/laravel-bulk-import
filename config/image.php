<?php

// config/image.php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    | Intervention Image supports "GD Library" and "Imagick" to process images
    | internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" implementation is used.
    */

    'driver' => 'gd',

    /*
    |--------------------------------------------------------------------------
    | Image Variants Configuration
    |--------------------------------------------------------------------------
    | Configure the image variants that should be generated
    */

    'variants' => [
        'original' => null,
        '256px' => 256,
        '512px' => 512,
        '1024px' => 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Configuration
    |--------------------------------------------------------------------------
    | Configure chunked upload settings
    */

    'upload' => [
        'chunk_size' => 1024 * 1024, // 1MB chunks
        'max_file_size' => 50 * 1024 * 1024, // 50MB max file size
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ],
        'temp_dir' => 'uploads/temp',
        'final_dir' => 'uploads/images',
    ],
];
