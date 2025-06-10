<?php

namespace Modules\MercadoPago\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Modules\MercadoPago\Models\MercadoPagoSetting;
use Modules\MercadoPago\Models\MercadoPagoTransaction;

class PaymentController extends Controller
{
    public function createIntent(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:nexopos_orders,id',
            'amount' => 'required|numeric|min:1',
            'payment_type' => 'required|string', // ex: credit_card, debit_card, etc
        ]);

        Log::info('MercadoPago createIntent request', $request->all());

        // Buscar configurações do Mercado Pago
        $settings = MercadoPagoSetting::first();

        if (!$settings || !$settings->access_token || !$settings->terminal_id) {
            return response()->json([
                'message' => 'Configurações do Mercado Pago ausentes ou incompletas.'
            ], 422);
        }

        $payload = [
            'amount' => $request->amount,
            'description' => 'Pagamento NexoPOS Pedido #' . $request->order_id,
            'payment' => [
                'installments' => 1,
                'type' => $request->payment_type,
                'installments_cost' => 'seller',
            ],
            'additional_info' => [
                'external_reference' => 'order_' . $request->order_id,
                'print_on_terminal' => false,
            ]
        ];

        Log::info('MercadoPago payload', $payload);

        // Enviar para o Mercado Pago
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-test-scope' => 'sandbox',
            'Authorization' => 'Bearer ' . $settings->access_token
        ])->post("https://api.mercadopago.com/point/integration-api/devices/{$settings->terminal_id}/payment-intents", $payload);

        Log::info('MercadoPago response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        // Armazenar resultado
        MercadoPagoTransaction::create([
            'order_id' => $request->order_id,
            'transaction_id' => $response['payment']['id'] ?? null,
            'status' => $response['payment']['status'] ?? 'unknown',
            'payment_type' => $request->payment_type,
            'payload' => $response->json(),
        ]);

        return response()->json([
            'message' => 'Transação enviada com sucesso.',
            'mercadopago_response' => $response->json()
        ], $response->status());
    }
}
