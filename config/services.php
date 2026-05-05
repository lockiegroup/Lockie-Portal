<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'unleashed' => [
        'id'  => env('UNLEASHED_API_ID'),
        'key' => env('UNLEASHED_API_KEY'),
    ],

    'amazon' => [
        'client_id'      => env('AMAZON_CLIENT_ID'),
        'client_secret'  => env('AMAZON_CLIENT_SECRET'),
        'refresh_token'  => env('AMAZON_REFRESH_TOKEN'),
        'marketplace_id' => env('AMAZON_MARKETPLACE_ID', 'A1F83G8C2ARO7P'),
        'seller_id'      => env('AMAZON_SELLER_ID'),
    ],

    'amazon_ads' => [
        'client_id'     => env('AMAZON_ADS_CLIENT_ID'),
        'client_secret' => env('AMAZON_ADS_CLIENT_SECRET'),
        'refresh_token' => env('AMAZON_ADS_REFRESH_TOKEN'),
        'profile_id'    => env('AMAZON_ADS_PROFILE_ID'),
    ],

    'xero' => [
        'client_id'             => env('XERO_CLIENT_ID'),
        'client_secret'         => env('XERO_CLIENT_SECRET'),
        'clearing_account_code' => env('XERO_CLEARING_ACCOUNT_CODE', 'AMAZON-CLEARING'),
    ],

];
