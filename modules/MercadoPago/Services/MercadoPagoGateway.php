<?php

namespace Modules\MercadoPago\Services;

use App\Abstracts\AbstractPaymentMethod;
use Modules\MercadoPago\Models\MercadoPagoSetting;
use Modules\MercadoPago\Models\MercadoPagoTransaction;
use Illuminate\Support\Facades\Http;

class MercadoPagoGateway extends AbstractPaymentMethod
{
    public function getLabel()
    {
        return 'Mercado Pago';
    }

    public function process($order)
    {
        $settings = MercadoPagoSetting::first();

        if (! $settings) {
            return ns()->error('Configurações do Mercado Pago não definidas.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $settings->access_token,
            'x-test-scope' => 'sandbox',
            'Content-Type' => 'application/json',
        ])->post("https://api.mercadopago.com/point/integration-api/devices/{$settings->terminal_id}/payment-intents", [
            'amount' => (int) ($order->total * 100),
            'description' => "Pedido N° {$order->code}",
            'payment' => [
                'installments' => 1,
                'type' => 'credit_card',
                'installments_cost' => 'seller'
            ],
            'additional_info' => [
                'external_reference' => $order->uuid,
                'print_on_terminal' => false
            ]
        ]);

        if ($response->failed()) {
            return ns()->error('Erro ao iniciar pagamento no Mercado Pago: ' . $response->body());
        }

        $data = $response->json();

        MercadoPagoTransaction::create([
            'order_id' => $order->id,
            'transaction_id' => $data['id'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'payment_type' => $data['payment']['type'] ?? 'unknown',
            'payload' => $data,
        ]);

        return ns()->success('Pagamento enviado para o Mercado Pago com sucesso.');
    }
}
