<?php

namespace Modules\NotaFiscal\Listeners;

use App\Events\OrderAfterCreatedEvent;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\NotaFiscal\Models\NotaFiscalSetting;
use Modules\NotaFiscal\Services\NfceService;

/**
 * Listener que emite a NFC-e automaticamente após um pedido ser criado com
 * payment_status = 'paid'. Implementa ShouldQueue para não bloquear a resposta
 * do PDV enquanto a SEFAZ é consultada.
 */
class EmitirNfceListener implements ShouldQueue
{
    public string $queue = 'default';

    public int $tries = 1;

    public function handle(OrderAfterCreatedEvent $event): void
    {
        /** @var Order $order */
        $order = $event->order;

        // Só emite para pedidos pagos
        if ($order->payment_status !== Order::PAYMENT_PAID) {
            return;
        }

        $settings = NotaFiscalSetting::first();

        if (! $settings || ! $settings->ativo || ! $settings->emitir_automaticamente) {
            return;
        }

        try {
            $service = app(NfceService::class);
            $emissao = $service->emitir($order);

            Log::info(
                '[NFC-e] Emissão automática concluída — Pedido #' . $order->id
                . ' | Status: ' . $emissao->status
            );
        } catch (\Exception $e) {
            Log::error('[NFC-e] Erro no listener — Pedido #' . $order->id . ': ' . $e->getMessage());
        }
    }
}
