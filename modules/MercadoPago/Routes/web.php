<?php
use Illuminate\Support\Facades\Route;

use Modules\MercadoPago\Http\Controllers\MercadoPagoController;
use Modules\MercadoPago\Http\Controllers\SettingsController;
use Modules\MercadoPago\Http\Controllers\TransactionController;
use Modules\MercadoPago\Http\Controllers\PaymentController;
use Modules\MercadoPago\Services\MercadoPagoGateway;




Route::get('dashboard/mercadopago', [MercadoPagoController::class, 'config'])->name('mercadopago.settings');
Route::post('dashboard/mercadopago/settings/save', [SettingsController::class, 'save'])->name('mercadopago.settings.save');
Route::get('dashboard/mercadopago/transactions', [TransactionController::class, 'index'])->name('mercadopago.transactions.index');
Route::get('dashboard/mercadopago/transactions/{id}/payload', [TransactionController::class, 'payload'])->name('mercadopago.transactions.payload');
Route::post('/mercadopago/payment-intent', [PaymentController::class, 'createIntent'])->name('mercadopago.payment.intent');
Route::post('/api/mercadopago/pay', [MercadoPagoController::class, 'pay']);


Route::get('/mercadopago/test-log', function () {
    $gateway = new MercadoPagoGateway();

    return $gateway->pay(
        (object)['id' => 1, 'uuid' => 'teste-uuid', 'code' => 'PED001'],
        150,
        null,
        request()
    );
});
