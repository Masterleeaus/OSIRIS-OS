<?php

use App\Http\Controllers\Finance\PaymentProcessController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')
    ->name('webhooks.')
    ->group(function () {
        Route::match(['get', 'post'], '/{gateway}', [PaymentProcessController::class, 'handleWebhook']);

        Route::any('stripe/{subscription}/success', [PaymentProcessController::class, 'stripeSuccess'])->name('stripe.success');
        Route::any('stripe/{subscription}/cancel', [PaymentProcessController::class, 'stripeCancel'])->name('stripe.cancel');

        Route::any('stripe/{plan}/{user}/success/prepaid', [PaymentProcessController::class, 'prepaidStripeSuccess'])->name('stripe.success');

        // Git system webhooks
        Route::post('/github', [WebhookController::class, 'github'])->name('github');
        Route::post('/gitlab', [WebhookController::class, 'gitlab'])->name('gitlab');
        Route::post('/gitflic', [WebhookController::class, 'gitflic'])->name('gitflic');
        Route::post('/gitverse', [WebhookController::class, 'gitverse'])->name('gitverse');
        Route::post('/sourcecraft', [WebhookController::class, 'sourcecraft'])->name('sourcecraft');
        Route::post('/canadian-git', [WebhookController::class, 'canadianGit'])->name('canadian.git');
        Route::post('/israeli-git', [WebhookController::class, 'israeliGit'])->name('israeli.git');
        Route::post('/arabic-git', [WebhookController::class, 'arabicGit'])->name('arabic.git');
        Route::post('/australian-git', [WebhookController::class, 'australianGit'])->name('australian.git');
        Route::post('/chinese-git', [WebhookController::class, 'chineseGit'])->name('chinese.git');
    });
