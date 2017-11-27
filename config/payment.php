<?php
return [
    'driver' => env('PAYMENT_DRIVER', 'napas'),
    'napas' => [
        'merchant_id' => env('NAPAS_MERCHANT_ID'),
        'secure_hash' => env('NAPAS_SECURE_HASH'),
        'access_code' => env('NAPAS_ACCESS_CODE'),
        'env'         => env('NAPAS_ENV', 'production')
    ],

    'payoo' => [
        'key' => env('PAYOO_KEY'),
        'secret' => env('PAYOO_SECRET'),
        'env'    => env('PAYOO_ENV', 'production')
    ],

    'baokim' => [
        'merchant_id'    => env('BAOKIM_MERCHANT_ID'),
        'email_business' => env('BAOKIM_EMAIL_BUSINESS'),
        'secure_pass'    => env('BAOKIM_SECURE_PASS'),
        'api_user'       => env('BAOKIM_API_USER'),
        'api_pwd'        => env('BAOKIM_API_PWD'),
        'private_key'    => env('BAOKIM_PRIVATE_KEY'),
    ],
];