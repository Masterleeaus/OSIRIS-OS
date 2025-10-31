<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TON Network Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration for the TON blockchain integration.
    | You can specify different network endpoints and API keys here.
    |
    */

    'network' => env('TON_NETWORK', 'mainnet'),
    
    'networks' => [
        'mainnet' => [
            'endpoint' => env('TON_MAINNET_ENDPOINT', 'https://toncenter.com/api/v2/jsonRPC'),
            'api_key' => env('TON_MAINNET_API_KEY'),
        ],
        'testnet' => [
            'endpoint' => env('TON_TESTNET_ENDPOINT', 'https://testnet.toncenter.com/api/v2/jsonRPC'),
            'api_key' => env('TON_TESTNET_API_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Wallet Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the default wallet settings for the application.
    |
    */
    'wallet' => [
        'version' => 'v4r2', // Most recent wallet version
        'workchain' => 0,    // Base workchain ID
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Telegram bot integration.
    |
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cocoon Integration (if applicable)
    |--------------------------------------------------------------------------
    |
    | Configuration for Cocoon integration.
    |
    */
    'cocoon' => [
        'api_key' => env('COCOON_API_KEY'),
        'api_url' => env('COCOON_API_URL', 'https://api.cocoon.com'),
    ],
];
