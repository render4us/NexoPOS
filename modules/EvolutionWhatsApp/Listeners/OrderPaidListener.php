<?php

namespace Modules\EvolutionWhatsApp\Listeners;

use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Models\Order;
use Modules\EvolutionWhatsApp\Services\EvolutionApiService;

class OrderPaidListener
{
    public function handle(OrderAfterPaymentStatusChangedEvent $event): void
    {
        // Dispara apenas quando o pedido muda para "pago"
        if ($event->new !== Order::PAYMENT_PAID) {
            return;
        }

        app(EvolutionApiService::class)->enviarConfirmacaoPedido($event->order);
    }
}
