<?php
namespace Modules\MercadoPago\Http\Controllers;

use App\Models\Order;
use App\Services\OrdersService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;
use Modules\MercadoPago\Models\MercadoPagoTransaction;

class CallbackController
{
    public function handle(Request $request)
    {
        $paymentId = data_get($request->input('data'), 'id');

        if (! $paymentId) {
            return response()->json(['status' => 'ignored']);
        }

        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));
        $client = new PaymentClient();

        try {
            $payment = $client->get($paymentId);
        } catch (\Throwable $e) {
            Log::error('MercadoPago callback error: ' . $e->getMessage());

            return response()->json(['status' => 'error'], 400);
        }

        MercadoPagoTransaction::create([
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'payload' => json_encode($payment),
        ]);

        $order = Order::where('code', $payment->external_reference)->first();

        if ($order) {
            app()->make(OrdersService::class)->makeOrderSinglePayment([
                'identifier' => 'mercadopago',
                'value' => $payment->transaction_amount,
            ], $order);
        }

        return response()->json(['status' => $payment->status]);
    }
}
