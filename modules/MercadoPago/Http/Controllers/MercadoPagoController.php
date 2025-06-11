<?php

namespace Modules\MercadoPago\Http\Controllers;

use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\MercadoPago\Models\MercadoPagoSetting;
use Modules\MercadoPago\Models\MercadoPagoTransaction;

class MercadoPagoController extends Controller
{
    public function index()
    {
        return view('MercadoPago::index');
    }

    public function config()
    {
        $setting = MercadoPagoSetting::first();

        return view('MercadoPago::config', [
            'title' => 'Mercado Pago',
            'description' => 'Configurações do mecanismo de pagamento',
            'token' => $setting->access_token ?? '',
            'terminal_id' => $setting->terminal_id ?? '',
        ]);
    }

    public function pay(Request $request)
    {
        $order = $request->input('order');
        $paymentType = $request->input('payment_type', 'credit_card'); // Dinâmico via front

        $config = MercadoPagoSetting::first();

        if (!$config) {
            return response()->json(['status' => 'error', 'message' => 'Configuração do Mercado Pago não encontrada'], 500);
        }

        try {
            $externalRef = 'temp_' . now()->format('YmdHis') . '_' . ($order['customer_id'] ?? 'guest');
            $amount = number_format((float) ($order['total'] ?? 0), 2, '.', '');

            $payload = [
                'type' => 'point',
                'external_reference' => $externalRef,
                'transactions' => [
                    'payments' => [
                        [
                            'amount' => $amount,
                        ]
                    ]
                ],
                'config' => [
                    'point' => [
                        'terminal_id' => $config->terminal_id,
                        'print_on_terminal' => 'no_ticket',
                    ],
                    'payment_method' => [
                        'default_type' => $paymentType,
                        'default_installments' => $config->default_installments ?? 1,
                        'installments_cost' => $config->installments_cost ?? 'seller'
                    ]
                ],
                'description' => 'Pagamento via POS',
            ];

            // Dados opcionais
            $integrationData = [];
            if ($config->platform_id) {
                $integrationData['platform_id'] = $config->platform_id;
            }
            if ($config->integrator_id) {
                $integrationData['integrator_id'] = $config->integrator_id;
            }
            if ($config->sponsor_id) {
                $integrationData['sponsor'] = ['id' => $config->sponsor_id];
            }

            if (!empty($integrationData)) {
                $payload['integration_data'] = $integrationData;
            }

            $response = Http::withToken($config->access_token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Idempotency-Key' => Str::uuid()->toString(),
                ])
                ->timeout(30)
                ->post('https://api.mercadopago.com/v1/orders', $payload);

            Log::debug('[Mercado Pago API Response]', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            $res = $response->json();

            if ($response->successful()) {
                MercadoPagoTransaction::create([
                    'order_id' => $order['id'] ?? null,
                    'transaction_id' => $res['id'] ?? null,
                    'status' => $res['status'] ?? 'pending',
                    'payment_type' => $paymentType,
                    'payload' => $res,
                ]);

                return response()->json([
                    'status' => 'created',
                    'message' => 'Pagamento solicitado com sucesso',
                    'transaction_id' => $res['id'] ?? null,
                    'mercadopago' => $res,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao processar pagamento',
                'mercadopago' => $res,
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('[Mercado Pago] Erro ao enviar pagamento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Erro interno ao enviar para Mercado Pago'], 500);
        }
    }

    public function checkStatus(Request $request)
    {
        $transactionId = $request->input('transaction_id');

        if (!$transactionId) {
            return response()->json(['status' => 'error', 'message' => 'ID da transação não informado'], 400);
        }

        $config = MercadoPagoSetting::first();

        if (!$config) {
            return response()->json(['status' => 'error', 'message' => 'Configuração não encontrada'], 500);
        }

        try {
            $response = Http::withToken($config->access_token)
                ->get("https://api.mercadopago.com/v1/orders/{$transactionId}");

            if ($response->successful()) {
                $res = $response->json();

                // Você pode ajustar os status aceitos conforme sua regra
                if (in_array($res['status'], ['paid', 'closed'])) {
                    // Atualiza a transação no banco, se desejar
                    MercadoPagoTransaction::where('transaction_id', $transactionId)
                        ->update(['status' => $res['status'], 'payload' => $res]);

                    return response()->json(['status' => 'success', 'message' => 'Pagamento aprovado']);
                }

                return response()->json(['status' => 'pending', 'message' => 'Pagamento ainda não confirmado']);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao consultar status',
                'details' => $response->json(),
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('[Mercado Pago] Erro ao consultar status', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['status' => 'error', 'message' => 'Erro interno ao consultar status'], 500);
        }
    }
}
