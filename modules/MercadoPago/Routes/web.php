<?php
use Illuminate\Support\Facades\Route;
use Modules\MercadoPago\Http\Controllers\CallbackController;

Route::prefix('mercadopago')->group(function () {
    Route::post('callback', [CallbackController::class, 'handle'])
        ->name('mercadopago.callback');
});
