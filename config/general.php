<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Do not change settings below
     |--------------------------------------------------------------------------
     */

    // Email defaults
    'cname_domain' => null,
    'app_url' => env('APP_URL'),
    'app_timezone' => env('APP_TIMEZONE'),
    'app_headline' => env('APP_HEADLINE'),
    'mail_address_from' => env('MAIL_FROM_ADDRESS'),
    'mail_name_from' => env('MAIL_FROM_NAME'),
    'mail_contact' => env('MAIL_CONTACT'),
    'serverpilot_client_id' => env('SP_CLIENT_ID', null),
    'serverpilot_key' => env('SP_KEY', null),
    'serverpilot_app_id' => env('SP_APP_ID', null),
    'payment_provider' => env('PAYMENT_PROVIDER', ''),
    'payment_test_mode' => env('PAYMENT_TEST_MODE', false),
    'paddle_vendor_id' => env('PADDLE_VENDOR_ID'),
    '2checkout_vendor_id' => env('2CHECKOUT_VENDOR_ID'),
    '2checkout_key' => env('2CHECKOUT_KEY', null),
    'stripe_public_key' => env('STRIPE_PUBLIC_KEY', null),
    'stripe_secret_key' => env('STRIPE_SECRET_KEY', null)
];