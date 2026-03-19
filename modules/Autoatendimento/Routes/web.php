<?php

use Illuminate\Support\Facades\Route;
use Modules\Autoatendimento\Http\Controllers\KioskController;
use Modules\Autoatendimento\Http\Controllers\KioskOrderController;
use Modules\Autoatendimento\Http\Controllers\KioskSettingsController;

/*
|--------------------------------------------------------------------------
| Rota pública — kiosk de autoatendimento (sem autenticação)
|--------------------------------------------------------------------------
*/
Route::get('kiosk', [KioskController::class, 'index'])->name('kiosk.index');

/*
|--------------------------------------------------------------------------
| Rotas do painel de administração (requer autenticação)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('dashboard/autoatendimento', [KioskSettingsController::class, 'index'])
        ->name('autoatendimento.configuracoes');

    Route::post('dashboard/autoatendimento/salvar', [KioskSettingsController::class, 'salvar'])
        ->name('autoatendimento.configuracoes.salvar');

});
