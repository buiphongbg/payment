<?php
return [
    'driver' => env('PAYMENT_DRIVER', 'napas'),
    'napas' => [
        'merchant_id' => env('NAPAS_MERCHANT_ID'),
        'secure_hash' => env('NAPAS_SECURE_HASH'),
        'access_code' => env('NAPAS_ACCESS_CODE')
    ],

    'payoo' => [
        'key' => env('PAYOO_KEY'),
        'secret' => env('PAYOO_SECRET'),
    ]
];