<?php
namespace Modules\MercadoPago\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class MercadoPagoController
{
    public function pay(Request $request)
    {
        $order = $request->input('order');
        $payload = [
            'type' => 'point',
            'external_reference' => $order['reference'] ?? 'order_'.time(),
            'transactions' => [
                'payments' => [
                    [ 'amount' => (string) ($order['total'] ?? 0) ]
                ]
            ],
            'config' => [
                'point' => [
                    'terminal_id' => config('mercadopago.terminal_id'),
                    'print_on_terminal' => 'no_ticket',
                    'ticket_number' => Str::random(8)
                ]
            ],
            'description' => 'POS Payment'
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Idempotency-Key' => (string) Str::uuid(),
            'Authorization' => 'Bearer '.config('mercadopago.access_token')
        ])->post('https://api.mercadopago.com/v1/orders', $payload);

        return response()->json($response->json());
    }
}
