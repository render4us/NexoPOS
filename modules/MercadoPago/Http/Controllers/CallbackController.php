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

        // Webhook padrão (payments): data.id
        // Webhook Point Integration: data_id no root do body
        $dataId = data_get($request->input('data'), 'id')
            ?: $request->input('data_id', '');
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

        $webhookType = $request->input('type', '');

        // ── Webhook do Point Integration (maquininha) ─────────────────────────
        if ($webhookType === 'point_integration_wh') {
            return $this->handlePointWebhook($request);
        }

        // ── Webhook padrão de pagamentos ──────────────────────────────────────
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

    /**
     * Processa o webhook do Point Integration (maquininha).
     * Body: { id, payment: { id, state }, state: 'FINISHED', data_id, type: 'point_integration_wh' }
     */
    private function handlePointWebhook(Request $request)
    {
        $intentId    = $request->input('id') ?: $request->input('data_id');
        $intentState = $request->input('state');
        $paymentData = $request->input('payment', []);
        $paymentId   = $paymentData['id']    ?? null;
        $paymentState = $paymentData['state'] ?? null;

        Log::info('[MP Point Webhook] Recebido', [
            'intent_id'     => $intentId,
            'intent_state'  => $intentState,
            'payment_id'    => $paymentId,
            'payment_state' => $paymentState,
        ]);

        if ($intentState !== 'FINISHED' || $paymentState !== 'approved' || ! $paymentId) {
            Log::info('[MP Point Webhook] Ignorado (não aprovado ou incompleto)');
            return response()->json(['status' => 'ignored']);
        }

        // Localiza a transação Kiosk pelo transaction_id (= intent ID)
        $transaction = MercadoPagoTransaction::where('transaction_id', $intentId)->first();

        if (! $transaction) {
            Log::warning('[MP Point Webhook] Transação não encontrada para intent', ['intent_id' => $intentId]);
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($transaction->status === 'FINISHED') {
            Log::info('[MP Point Webhook] Transação já processada, ignorando');
            return response()->json(['status' => 'already_processed']);
        }

        $transaction->update(['status' => 'FINISHED', 'payload' => $request->all()]);

        $order = Order::find($transaction->order_id);

        if (! $order) {
            Log::error('[MP Point Webhook] Pedido não encontrado', ['order_id' => $transaction->order_id]);
            return response()->json(['status' => 'order_not_found'], 404);
        }

        if ($order->payment_status === Order::PAYMENT_PAID) {
            Log::info('[MP Point Webhook] Pedido já pago, ignorando', ['order_id' => $order->id]);
            return response()->json(['status' => 'already_paid']);
        }

        try {
            app(\Modules\Autoatendimento\Http\Controllers\KioskOrderController::class)
                ->processarPagamentoAprovadoPublic($order, $transaction->payment_type ?? 'credit_card');

            Log::info('[MP Point Webhook] Pagamento processado via webhook', ['order_id' => $order->id]);
        } catch (\Throwable $e) {
            Log::error('[MP Point Webhook] Erro ao processar pagamento', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'approved']);
    }
}
