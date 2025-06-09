<?php
namespace Modules\MercadoPago\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MercadoPago\Client\Point\PointClient;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoController
{
    public function pay(Request $request)
    {
        $order = $request->input('order');

        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));

        $client = new PointClient();

        $paymentRequest = [
            'amount' => (float) ($order['total'] ?? 0),
            'description' => 'POS Payment',
            'payment' => [
                'installments' => 1,
                'type' => 'credit_card',
                'installments_cost' => 'seller'
            ],
            'additional_info' => [
                'external_reference' => $order['reference'] ?? 'order_'.time(),
                'print_on_terminal' => true,
                'ticket_number' => Str::random(8)
            ]
        ];

        $options = new RequestOptions();
        $options->setCustomHeaders([
            'x-idempotency-key' => (string) Str::uuid(),
        ]);

        $intent = $client->createPaymentIntent(
            config('mercadopago.terminal_id'),
            $paymentRequest,
            $options
        );

        return response()->json(json_decode(json_encode($intent), true));
    }
}
