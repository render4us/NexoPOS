<?php

namespace Modules\EvolutionWhatsApp\Listeners;

use App\Events\OrderAfterCreatedEvent;
use Modules\EvolutionWhatsApp\Models\EvolutionWhatsAppSetting;
use Modules\EvolutionWhatsApp\Services\EvolutionApiService;

class OrderCreatedListener
{
    /**
     * Dispara a mensagem de "pedido recebido" assim que o pedido é criado pelo Kiosk.
     * Só age se:
     *  - O módulo está ativo
     *  - A opção "disparar ao criar" está habilitada
     *  - O pedido foi gerado pelo Kiosk (nota contém "Pedido via Kiosk")
     *  - O cliente informou o número de WhatsApp
     */
    public function handle(OrderAfterCreatedEvent $event): void
    {
        $setting = EvolutionWhatsAppSetting::instance();

        if (! $setting->ativo || ! $setting->disparar_ao_criar) {
            return;
        }

        // Filtra apenas pedidos originados pelo Kiosk de autoatendimento
        if (! str_contains($event->order->note ?? '', 'Pedido via Kiosk')) {
            return;
        }

        app(EvolutionApiService::class)->enviarNotificacaoCriacao($event->order);
    }
}
