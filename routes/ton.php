<?php

use App\Http\Controllers\Api\TonIntegrationController;
use App\Http\Controllers\Api\CrossChainController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TON Integration Routes
|--------------------------------------------------------------------------
|
| Here is where you can register TON integration related routes for your application.
|
*/

Route::prefix('api/ton')->group(function () {
    // Webhook for Telegram bot
    Route::post('/webhook', [TonIntegrationController::class, 'handleWebhook']);
    
    // Set webhook URL (should be protected in production)
    Route::post('/set-webhook', [TonIntegrationController::class, 'setWebhook']);
    
    // TON blockchain operations
    Route::middleware('auth:sanctum')->group(function () {
        // Get account info
        Route::get('/account-info', [TonIntegrationController::class, 'getAccountInfo']);
        
        // Send TON
        Route::post('/send', [TonIntegrationController::class, 'sendTon']);
        
        // Generate wallet
        Route::post('/wallet/generate', [TonIntegrationController::class, 'generateWallet']);
    });
});

/*
|--------------------------------------------------------------------------
| Cross-Chain Integration Routes
|--------------------------------------------------------------------------
|
| Routes for cross-chain operations, following patterns from Polkadot and other chains.
|
*/

Route::prefix('api/chain')->middleware('auth:sanctum')->group(function () {
    // Account operations
    Route::get('/account', [CrossChainController::class, 'getAccountInfo']);
    
    // Transaction operations
    Route::get('/transactions', [CrossChainController::class, 'getTransactionHistory']);
    Route::get('/transactions/{tx_hash}/status', [CrossChainController::class, 'getTransactionStatus']);
    
    // Asset operations
    Route::get('/assets/balance', [CrossChainController::class, 'getAssetBalance']);
    
    // Fee estimation
    Route::get('/transactions/estimate-fee', [CrossChainController::class, 'estimateFee']);
});
