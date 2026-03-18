<?php

use Illuminate\Support\Facades\Route;
use Modules\NotaFiscal\Http\Controllers\NotaFiscalController;

Route::middleware(['auth:sanctum'])->prefix('nota-fiscal')->group(function () {

    // Lista emissões com filtros
    Route::get('emissoes', [NotaFiscalController::class, 'listarEmissoes']);

    // Emite / reprocessa NFC-e de um pedido
    Route::post('reprocessar/{orderId}', [NotaFiscalController::class, 'reprocessar']);

    // Download do XML de uma emissão específica
    Route::get('emissoes/{id}/xml', [NotaFiscalController::class, 'downloadXml']);

    // Download do DANFCE (PDF) de uma emissão específica
    Route::get('emissoes/{id}/danfce', [NotaFiscalController::class, 'downloadDanfce']);
});
