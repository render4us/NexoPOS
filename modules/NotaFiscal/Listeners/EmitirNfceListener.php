<?php

namespace Modules\NotaFiscal\Listeners;

use App\Events\OrderAfterCreatedEvent;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\NotaFiscal\Models\NotaFiscalEmissao;
use Modules\NotaFiscal\Models\NotaFiscalSetting;
use Modules\NotaFiscal\Services\NfceService;

/**
 * Emite a NFC-e automaticamente somente quando:
 *   1. O pedido está com payment_status = 'paid' (pagamento aprovado)
 *   2. O cliente informou o CPF (gravado no campo note como "CPF: 11 dígitos")
 *
 * Sem CPF, o pedido recebe apenas o cupom simples de consumo pelo fluxo do kiosk.
 */
class EmitirNfceListener implements ShouldQueue
{
    public string $queue = 'default';

    public int $tries = 1;

    public function handle(OrderAfterCreatedEvent $event): void
    {
        /** @var Order $order */
        $order = $event->order;

        // (1) Só emite para pedidos pagos
        if ($order->payment_status !== Order::PAYMENT_PAID) {
            return;
        }

        // (2) Só emite se o cliente tiver informado CPF no totem
        if (! preg_match('/CPF:\s*(\d{11})/', $order->note ?? '', $m)) {
            return;
        }
        $cpf = $m[1];

        $settings = NotaFiscalSetting::first();
        if (! $settings || ! $settings->ativo || ! $settings->emitir_automaticamente) {
            return;
        }

        // Idempotência: se o pedido já tem emissão autorizada ou pendente, não refaz
        $jaEmitida = NotaFiscalEmissao::where('order_id', $order->id)
            ->whereIn('status', [
                NotaFiscalEmissao::STATUS_PENDENTE,
                NotaFiscalEmissao::STATUS_AUTORIZADA,
            ])
            ->exists();

        if ($jaEmitida) {
            return;
        }

        try {
            $emissao = app(NfceService::class)->emitir($order, $cpf);

            Log::info(
                '[NFC-e] Emissão automática concluída — Pedido #' . $order->id
                . ' | CPF: ' . $cpf
                . ' | Status: ' . $emissao->status
            );
        } catch (\Exception $e) {
            Log::error('[NFC-e] Erro no listener — Pedido #' . $order->id . ': ' . $e->getMessage());
        }
    }
}
