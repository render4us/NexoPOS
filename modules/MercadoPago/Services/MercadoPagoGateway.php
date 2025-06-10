<?php

namespace Modules\MercadoPago\Services;

use Modules\MercadoPago\Models\MercadoPagoSetting;
use Modules\MercadoPago\Models\MercadoPagoTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoGateway
{
    public function getLabel()
    {
        return 'Mercado Pago';
    }

    public function pay($order, $amount, $payment, $request)
    {
        Log::info('MercadoPagoGateway@pay chamado', [
            'order_id' => $order->id ?? null,
            'amount' => $amount,
        ]);

        $settings = MercadoPagoSetting::first();

        if (! $settings || !$settings->access_token || !$settings->terminal_id) {
            return response()->json(['message' => 'Configurações do Mercado Pago inválidas.'], 422);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $settings->access_token,
            'Content-Type'  => 'application/json',
        ])->post("https://api.mercadopago.com/point/integration-api/devices/{$settings->terminal_id}/payment-intents", [
            'amount' => (int) ($amount * 100), // centavos
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
            Log::error('MercadoPagoGateway failed', ['body' => $response->body()]);
            return response()->json(['message' => 'Erro ao enviar pagamento.', 'details' => $response->body()], 403);
        }

        $data = $response->json();

        Log::info('MercadoPagoGateway success', ['data' => $data]);

        // Salvar transação no banco
        MercadoPagoTransaction::create([
            'order_id'       => $order->id,
            'transaction_id' => $data['id'] ?? null,
            'status'         => $data['status'] ?? 'pending',
            'payment_type'   => $data['payment']['type'] ?? 'unknown',
            'payload'        => $data,
        ]);

        return response()->json([
            'message' => 'Pagamento enviado com sucesso para o terminal.',
            'id' => $data['id'] ?? null,
        ]);
    }
}
