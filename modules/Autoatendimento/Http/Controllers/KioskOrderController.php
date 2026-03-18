<?php

namespace Modules\Autoatendimento\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrdersService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Autoatendimento\Models\KioskSetting;
use Modules\MercadoPago\Models\MercadoPagoSetting;
use Modules\MercadoPago\Models\MercadoPagoTransaction;

class KioskOrderController extends Controller
{
    /**
     * Cria o pedido no NexoPOS e envia o valor para a maquininha via Mercado Pago.
     */
    public function criar(Request $request)
    {
        $request->validate([
            'items'                      => 'required|array|min:1',
            'items.*.product_id'         => 'required|integer',
            'items.*.unit_quantity_id'   => 'required|integer',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.unit_price'         => 'required|numeric|min:0.01',
            'items.*.name'               => 'required|string',
            'mode'                       => 'required|in:eat_in,takeaway',
            'phone'                      => 'nullable|string|max:30',
            'nfe'                        => 'boolean',
            'payment_type'               => 'required|in:credit_card,debit_card',
        ]);

        $setting = KioskSetting::instance();
        $mpConfig = MercadoPagoSetting::first();

        if (! $mpConfig || ! $mpConfig->access_token || ! $mpConfig->terminal_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Mercado Pago não configurado. Contate o atendente.',
            ], 500);
        }

        $total = collect($request->items)
            ->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

        // ── 1. Cria o pedido no NexoPOS ────────────────────────────────────
        $notes = ['Pedido via Kiosk'];
        if ($request->phone) {
            $notes[] = 'WhatsApp: ' . $request->phone;
        }
        if ($request->boolean('nfe')) {
            $notes[] = 'Solicita NF-e';
        }

        // Autentica como o operador configurado para o serviço de pedidos
        Auth::onceUsingId($setting->operator_user_id ?? 1);

        try {
            /** @var OrdersService $ordersService */
            $ordersService = app(OrdersService::class);

            $orderPayload = [
                'author'         => $setting->operator_user_id ?? 1,
                'customer_id'    => null,
                'type'           => $request->mode === 'eat_in' ? 'eat_in' : 'takeaway',
                'payment_status' => Order::PAYMENT_UNPAID,
                'process_status' => 'pending',
                'note'           => implode(' | ', $notes),
                'products'       => collect($request->items)->map(fn ($i) => [
                    'product_id'       => $i['product_id'],
                    'unit_quantity_id' => $i['unit_quantity_id'],
                    'quantity'         => $i['quantity'],
                    'unit_price'       => $i['unit_price'],
                    'name'             => $i['name'],
                    'tax_value'        => 0,
                ])->toArray(),
                'payments' => [],
            ];

            $order = $ordersService->create($orderPayload);
        } catch (\Throwable $e) {
            Log::error('[Kiosk] Erro ao criar pedido', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Erro ao registrar o pedido. Tente novamente.',
            ], 500);
        }

        // ── 2. Envia para a maquininha via Mercado Pago ────────────────────
        try {
            $amount       = number_format($total, 2, '.', '');
            $externalRef  = 'kiosk_' . $order->id . '_' . now()->format('YmdHis');

            $payload = [
                'type'               => 'point',
                'external_reference' => $externalRef,
                'transactions'       => [
                    'payments' => [['amount' => $amount]],
                ],
                'config' => [
                    'point' => [
                        'terminal_id'       => $mpConfig->terminal_id,
                        'print_on_terminal' => 'no_ticket',
                    ],
                    'payment_method' => [
                        'default_type'          => $request->payment_type,
                        'default_installments'  => 1,
                        'installments_cost'     => 'seller',
                    ],
                ],
                'description' => 'Kiosk — Pedido #' . $order->id,
            ];

            $response = Http::withToken($mpConfig->access_token)
                ->withHeaders([
                    'Content-Type'      => 'application/json',
                    'X-Idempotency-Key' => Str::uuid()->toString(),
                ])
                ->timeout(30)
                ->post('https://api.mercadopago.com/v1/orders', $payload);

            $res = $response->json();

            Log::debug('[Kiosk] Resposta Mercado Pago', [
                'status' => $response->status(),
                'body'   => $res,
            ]);

            if ($response->successful() && isset($res['id'])) {
                MercadoPagoTransaction::create([
                    'order_id'       => $order->id,
                    'transaction_id' => $res['id'],
                    'status'         => $res['status'] ?? 'pending',
                    'payment_type'   => $request->payment_type,
                    'payload'        => $res,
                ]);

                return response()->json([
                    'status'         => 'created',
                    'order_id'       => $order->id,
                    'transaction_id' => $res['id'],
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Erro ao enviar para a maquininha. Tente novamente.',
                'details' => $res,
            ], 500);

        } catch (\Throwable $e) {
            Log::error('[Kiosk] Erro Mercado Pago', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Erro de comunicação com o Mercado Pago.',
            ], 500);
        }
    }

    /**
     * Consulta o status do pagamento (polling pelo frontend).
     * Quando aprovado, registra o pagamento no pedido NexoPOS.
     */
    public function status(string $transactionId)
    {
        $mpConfig = MercadoPagoSetting::first();

        if (! $mpConfig) {
            return response()->json(['status' => 'error', 'message' => 'Configuração ausente.'], 500);
        }

        try {
            $response = Http::withToken($mpConfig->access_token)
                ->get("https://api.mercadopago.com/v1/orders/{$transactionId}");

            if (! $response->successful()) {
                return response()->json(['status' => 'pending']);
            }

            $res = $response->json();
            $mpStatus = $res['status'] ?? 'pending';

            if (in_array($mpStatus, ['paid', 'closed'])) {
                // Atualiza transação
                MercadoPagoTransaction::where('transaction_id', $transactionId)
                    ->update(['status' => $mpStatus, 'payload' => $res]);

                // Registra pagamento no pedido NexoPOS
                $transaction = MercadoPagoTransaction::where('transaction_id', $transactionId)->first();

                if ($transaction && $transaction->order_id) {
                    $order = Order::find($transaction->order_id);

                    if ($order && $order->payment_status !== Order::PAYMENT_PAID) {
                        $setting = KioskSetting::instance();
                        Auth::onceUsingId($setting->operator_user_id ?? 1);

                        /** @var OrdersService $ordersService */
                        $ordersService = app(OrdersService::class);
                        $ordersService->makeOrderSinglePayment([
                            'identifier' => 'mercadopago',
                            'value'      => $order->total,
                        ], $order);
                    }
                }

                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Pagamento aprovado!',
                    'order_id' => $transaction?->order_id,
                ]);
            }

            if (in_array($mpStatus, ['cancelled', 'expired'])) {
                MercadoPagoTransaction::where('transaction_id', $transactionId)
                    ->update(['status' => $mpStatus]);

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pagamento cancelado ou expirado.',
                ]);
            }

            return response()->json(['status' => 'pending']);

        } catch (\Throwable $e) {
            Log::error('[Kiosk] Erro ao consultar status', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'pending']);
        }
    }
}
