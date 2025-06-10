<?php
use Illuminate\Support\Facades\Route;

use Modules\MercadoPago\Http\Controllers\MercadoPagoController;
use Modules\MercadoPago\Http\Controllers\SettingsController;
use Modules\MercadoPago\Http\Controllers\TransactionController;
use Modules\MercadoPago\Http\Controllers\PaymentController;



Route::get('dashboard/mercadopago', [MercadoPagoController::class, 'config'])->name('mercadopago.settings');
Route::post('dashboard/mercadopago/settings/save', [SettingsController::class, 'save'])->name('mercadopago.settings.save');
Route::get('dashboard/mercadopago/transactions', [TransactionController::class, 'index'])->name('mercadopago.transactions.index');
Route::get('dashboard/mercadopago/transactions/{id}/payload', [TransactionController::class, 'payload'])->name('mercadopago.transactions.payload');
Route::post('dashboard/mercadopago/payment-intent', [PaymentController::class, 'createIntent'])->name('mercadopago.payment.intent');
