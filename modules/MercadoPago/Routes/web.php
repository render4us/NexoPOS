<?php
use Illuminate\Support\Facades\Route;
use Modules\MercadoPago\Http\Controllers\SettingsController;

Route::middleware('auth')
    ->prefix('mercadopago')
    ->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])
            ->name('mercadopago.settings');
        Route::post('settings', [SettingsController::class, 'save'])
            ->name('mercadopago.settings.save');
    });
