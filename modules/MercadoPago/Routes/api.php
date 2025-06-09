<?php
use Illuminate\Support\Facades\Route;
use Modules\MercadoPago\Http\Controllers\MercadoPagoController;

Route::post('mercadopago/pay', [MercadoPagoController::class, 'pay']);
