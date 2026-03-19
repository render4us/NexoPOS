<?php

use Illuminate\Support\Facades\Route;
use Modules\EvolutionWhatsApp\Http\Controllers\SettingsController;

Route::middleware('auth')->group(function () {
    Route::get('dashboard/evolution-whatsapp', [SettingsController::class, 'index'])
        ->name('evolution-whatsapp.configuracoes');

    Route::post('dashboard/evolution-whatsapp/salvar', [SettingsController::class, 'salvar'])
        ->name('evolution-whatsapp.configuracoes.salvar');

    Route::post('dashboard/evolution-whatsapp/testar', [SettingsController::class, 'testar'])
        ->name('evolution-whatsapp.testar');
});
