<?php
use Illuminate\Support\Facades\Route;
use Modules\MercadoPago\Http\Controllers\MercadoPagoController;
use Modules\MercadoPago\Http\Controllers\CallbackController;

Route::prefix('mercadopago')->group(function () {
    Route::post('/pay', [MercadoPagoController::class, 'pay']);
    Route::post('/status', [MercadoPagoController::class, 'checkStatus']);
    Route::post('/callback', [CallbackController::class, 'handle']);
});
