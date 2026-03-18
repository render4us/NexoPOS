<?php

use Illuminate\Support\Facades\Route;
use Modules\Autoatendimento\Http\Controllers\KioskController;
use Modules\Autoatendimento\Http\Controllers\KioskOrderController;

/*
|--------------------------------------------------------------------------
| API pública do kiosk (sem CSRF / autenticação)
| Prefixo /api aplicado automaticamente pelo ModuleRouting
|--------------------------------------------------------------------------
*/

// Catálogo de produtos
Route::get('kiosk/produtos', [KioskController::class, 'produtos'])->name('kiosk.produtos');

// Criação de pedido + envio para maquininha
Route::post('kiosk/pedido', [KioskOrderController::class, 'criar'])->name('kiosk.pedido.criar');

// Consulta de status do pagamento (polling)
Route::get('kiosk/status/{transactionId}', [KioskOrderController::class, 'status'])->name('kiosk.pedido.status');
