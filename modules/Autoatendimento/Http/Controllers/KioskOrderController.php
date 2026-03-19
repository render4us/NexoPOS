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
use Modules\Autoatendimento\Services\EscPosPrinterService;
use Modules\MercadoPago\Models\MercadoPagoSetting;
use Modules\MercadoPago\Models\MercadoPagoTransaction;

class KioskOrderController extends Controller
{
    /**
     * Cria o pedido no SnowSYS e envia o valor para a maquininha via Mercado Pago.
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
            'cpf'                        => 'nullable|string|max:14',
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

        // ── 1. Cria o pedido no SnowSYS ────────────────────────────────────
        $notes = ['Pedido via Kiosk'];
        if ($request->phone) {
            $notes[] = 'WhatsApp: ' . $request->phone;
        }
        if ($request->boolean('nfe')) {
            $notes[] = 'Solicita NF-e';
            $cpfLimpo = preg_replace('/\D/', '', $request->cpf ?? '');
            if (strlen($cpfLimpo) === 11) {
                $notes[] = 'CPF: ' . $cpfLimpo;
            }
        }

        // Autentica como o operador configurado para o serviço de pedidos
        Auth::onceUsingId($setting->operator_user_id ?? 1);

        // Usa o cliente padrão definido nas configurações do SnowSYS
        $defaultCustomerId = (int) ns()->option->get('ns_customers_default', 0);

        if ($defaultCustomerId === 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cliente padrão não configurado. Acesse Configurações → Clientes e defina um cliente padrão.',
            ], 500);
        }

        try {
            /** @var OrdersService $ordersService */
            $ordersService = app(OrdersService::class);

            $orderPayload = [
                'author'         => $setting->operator_user_id ?? 1,
                'customer_id'    => $defaultCustomerId,
                'type'           => ['identifier' => $request->mode === 'eat_in' ? 'eat_in' : 'takeaway'],
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

            $result = $ordersService->create($orderPayload);
            $order  = $result['data']['order'];
        } catch (\Throwable $e) {
            Log::error('[Kiosk] Erro ao criar pedido', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Erro ao registrar o pedido. Tente novamente.',
            ], 500);
        }

        // ── 2. Envia para a maquininha via Mercado Pago (Payment Intents) ─────
        try {
            // Payment Intents API exige o valor em centavos (inteiro)
            $amountCentavos = (int) round($total * 100);
            $externalRef    = 'kiosk_' . $order->id . '_' . now()->format('YmdHis');

            $payload = [
                'amount'      => $amountCentavos,
                'description' => 'Kiosk — Pedido #' . $order->id,
                'payment'     => [
                    'installments'      => 1,
                    'type'              => $request->payment_type, // 'credit_card' | 'debit_card'
                    'installments_cost' => 'seller',
                ],
                'additional_info' => [
                    'external_reference' => $externalRef,
                    'print_on_terminal'  => false,
                ],
            ];

            $response = Http::withHeaders([
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $mpConfig->access_token,
                ])
                ->timeout(30)
                ->post("https://api.mercadopago.com/point/integration-api/devices/{$mpConfig->terminal_id}/payment-intents", $payload);

            $res = $response->json();

            Log::debug('[Kiosk] Resposta Mercado Pago', [
                'status' => $response->status(),
                'body'   => $res,
            ]);

            if ($response->successful() && isset($res['id'])) {
                MercadoPagoTransaction::create([
                    'order_id'       => $order->id,
                    'transaction_id' => $res['id'],
                    'status'         => $res['state'] ?? 'OPEN',
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
     * Quando aprovado, registra o pagamento no pedido SnowSYS.
     */
    public function status(string $transactionId)
    {
        $mpConfig = MercadoPagoSetting::first();

        if (! $mpConfig) {
            return response()->json(['status' => 'error', 'message' => 'Configuração ausente.'], 500);
        }

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $mpConfig->access_token,
                ])
                ->get("https://api.mercadopago.com/point/integration-api/payment-intents/{$transactionId}");

            if (! $response->successful()) {
                return response()->json(['status' => 'pending']);
            }

            $res      = $response->json();
            $mpStatus = $res['state'] ?? 'OPEN';

            // FINISHED = intent concluído; verificar se o pagamento foi aprovado
            if ($mpStatus === 'FINISHED') {
                $paymentState = $res['payment']['state'] ?? '';
                if ($paymentState !== 'approved') {
                    MercadoPagoTransaction::where('transaction_id', $transactionId)
                        ->update(['status' => 'rejected', 'payload' => $res]);
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Pagamento não aprovado na maquininha.',
                    ]);
                }
            }

            if ($mpStatus === 'FINISHED') {
                // Atualiza transação
                MercadoPagoTransaction::where('transaction_id', $transactionId)
                    ->update(['status' => $mpStatus, 'payload' => $res]);

                // Registra pagamento no pedido SnowSYS
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

                        // ── Emite NFC-e se o cliente solicitou ───────────────
                        if (str_contains($order->note ?? '', 'Solicita NF-e')) {
                            try {
                                $cpfNota = null;
                                if (preg_match('/CPF: (\d{11})/', $order->note ?? '', $m)) {
                                    $cpfNota = $m[1];
                                }
                                if (class_exists(\Modules\NotaFiscal\Services\NfceService::class)) {
                                    app(\Modules\NotaFiscal\Services\NfceService::class)->emitir($order, $cpfNota);
                                }
                            } catch (\Throwable $e) {
                                Log::warning('[Kiosk] Falha ao emitir NFC-e', ['error' => $e->getMessage()]);
                            }
                        }

                        // ── Imprime cupom na impressora de rede ──────────────
                        try {
                            $items = $order->products->map(fn ($p) => [
                                'name'       => $p->name,
                                'quantity'   => $p->quantity,
                                'unit_price' => $p->unit_price,
                            ])->toArray();

                            app(EscPosPrinterService::class)->printReceipt(
                                items:       $items,
                                total:       (float) $order->total,
                                mode:        $transaction->payload['description'] ?? 'takeaway',
                                phone:       null,
                                paymentType: $transaction->payment_type ?? 'credit_card',
                                orderId:     $order->id,
                                setting:     $setting
                            );
                        } catch (\Throwable $e) {
                            Log::warning('[Kiosk] Falha ao imprimir cupom', ['error' => $e->getMessage()]);
                        }
                    }
                }

                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Pagamento aprovado!',
                    'order_id' => $transaction?->order_id,
                ]);
            }

            if (in_array($mpStatus, ['CANCELED', 'ERROR'])) {
                MercadoPagoTransaction::where('transaction_id', $transactionId)
                    ->update(['status' => strtolower($mpStatus), 'payload' => $res]);

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pagamento cancelado ou recusado na maquininha.',
                ]);
            }

            return response()->json(['status' => 'pending']);

        } catch (\Throwable $e) {
            Log::error('[Kiosk] Erro ao consultar status', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'pending']);
        }
    }
}
