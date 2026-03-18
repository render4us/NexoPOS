<?php

use App\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Modules\NotaFiscal\Http\Controllers\NotaFiscalController;

Route::middleware([Authenticate::class])->prefix('dashboard/nota-fiscal')->group(function () {

    // ── Configurações ──────────────────────────────────────────────────────────
    Route::get('configuracoes', [NotaFiscalController::class, 'configuracoes'])
        ->name('nota-fiscal.configuracoes');

    Route::post('configuracoes', [NotaFiscalController::class, 'salvarConfiguracoes'])
        ->name('nota-fiscal.configuracoes.salvar');

    // ── Emissões ───────────────────────────────────────────────────────────────
    Route::get('emissoes', [NotaFiscalController::class, 'emissoes'])
        ->name('nota-fiscal.emissoes');

    Route::get('emissoes/json', [NotaFiscalController::class, 'listarEmissoes'])
        ->name('nota-fiscal.emissoes.json');

    Route::post('reprocessar/{orderId}', [NotaFiscalController::class, 'reprocessar'])
        ->name('nota-fiscal.reprocessar');

    // ── Downloads ─────────────────────────────────────────────────────────────
    Route::get('emissoes/{id}/xml', [NotaFiscalController::class, 'downloadXml'])
        ->name('nota-fiscal.download-xml');

    Route::get('emissoes/{id}/danfce', [NotaFiscalController::class, 'downloadDanfce'])
        ->name('nota-fiscal.download-danfce');
});
