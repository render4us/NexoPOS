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
    private function validateSignature(Request $request): bool
    {
        $secret = config('mercadopago.webhook_secret');

        if (empty($secret)) {
            return true;
        }

        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id', '');

        if (! $xSignature) {
            return false;
        }

        // Extrai ts e v1 do header x-signature: "ts=...,v1=..."
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            [$key, $value] = explode('=', trim($part), 2);
            $parts[$key] = $value;
        }

        $ts = $parts['ts'] ?? '';
        $v1 = $parts['v1'] ?? '';

        if (! $ts || ! $v1) {
            return false;
        }

        $dataId = data_get($request->input('data'), 'id', '');
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $v1);
    }

    public function handle(Request $request)
    {
        if (! $this->validateSignature($request)) {
            Log::warning('MercadoPago callback: assinatura inválida', [
                'headers' => $request->headers->all(),
                'body'    => $request->all(),
            ]);

            return response()->json(['status' => 'unauthorized'], 401);
        }

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

        if ($payment->status === 'approved') {
            $order = Order::where('code', $payment->external_reference)->first();

            if ($order) {
                app()->make(OrdersService::class)->makeOrderSinglePayment([
                    'identifier' => 'mercadopago',
                    'value' => $payment->transaction_amount,
                ], $order);
            }
        }

        return response()->json(['status' => $payment->status]);
    }
}
