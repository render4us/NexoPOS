<?php

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\Autoatendimento\Http\Controllers\KioskController;
use Modules\Autoatendimento\Http\Controllers\KioskOrderController;

/*
|--------------------------------------------------------------------------
| API pública do kiosk — sem CSRF / autenticação / Sanctum
| Prefixo /api aplicado automaticamente pelo ModuleRouting
|--------------------------------------------------------------------------
*/

Route::withoutMiddleware(EnsureFrontendRequestsAreStateful::class)->group(function () {

    // Catálogo de produtos
    Route::get('kiosk/produtos', [KioskController::class, 'produtos'])
        ->name('kiosk.produtos');

    // Criação de pedido + envio para maquininha
    Route::post('kiosk/pedido', [KioskOrderController::class, 'criar'])
        ->name('kiosk.pedido.criar');

    // Consulta de status do pagamento (polling)
    Route::get('kiosk/status/{transactionId}', [KioskOrderController::class, 'status'])
        ->name('kiosk.pedido.status');

    // Consulta de status do pagamento Pix (polling)
    Route::get('kiosk/pix-status/{paymentId}', [KioskOrderController::class, 'pixStatus'])
        ->name('kiosk.pix.status');

    // Simulação de pagamento aprovado (apenas quando teste_pagamento_ativo = true)
    Route::post('kiosk/simular-pagamento', [KioskOrderController::class, 'simularPagamento'])
        ->name('kiosk.simular-pagamento');

});
