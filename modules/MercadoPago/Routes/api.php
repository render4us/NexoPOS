<?php
use Illuminate\Support\Facades\Route;
use Modules\MercadoPago\Http\Controllers\MercadoPagoController;

Route::prefix('mercadopago')->group(function () {
    Route::post('/pay', [MercadoPagoController::class, 'pay']);
    Route::post('/status', [MercadoPagoController::class, 'checkStatus']);
});
