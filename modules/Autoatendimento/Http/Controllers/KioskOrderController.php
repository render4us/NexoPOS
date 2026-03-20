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

        $setting  = KioskSetting::instance();
        $bypass   = (bool) $setting->teste_pagamento_ativo;
        $mpConfig = $bypass ? null : MercadoPagoSetting::first();

        if (! $bypass && (! $mpConfig || ! $mpConfig->access_token || ! $mpConfig->terminal_id)) {
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

        // ── 2a. Modo bypass — pula Mercado Pago e aprova imediatamente ────────
        if ($bypass) {
            try {
                $log = $this->processarPagamentoAprovado($order, $request->payment_type);
                Log::info('[Kiosk] Pagamento bypass processado', ['order_id' => $order->id, 'log' => $log]);

                return response()->json([
                    'status'   => 'success',
                    'order_id' => $order->id,
                    'message'  => 'Pagamento aprovado (modo bypass).',
                ]);
            } catch (\Throwable $e) {
                Log::error('[Kiosk] Erro no bypass de pagamento', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Erro ao processar pagamento (bypass).',
                ], 500);
            }
        }

        // ── 2b. Envia para a maquininha via Mercado Pago (Payment Intents) ────
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

        Log::info('[Kiosk] Consultando status do payment intent', [
            'transaction_id' => $transactionId,
        ]);

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $mpConfig->access_token,
                ])
                ->get("https://api.mercadopago.com/point/integration-api/payment-intents/{$transactionId}");

            Log::info('[Kiosk] Resposta da API de status', [
                'transaction_id' => $transactionId,
                'http_status'    => $response->status(),
                'body'           => $response->json(),
            ]);

            if (! $response->successful()) {
                Log::warning('[Kiosk] API retornou erro HTTP', [
                    'transaction_id' => $transactionId,
                    'http_status'    => $response->status(),
                    'body'           => $response->body(),
                ]);
                return response()->json(['status' => 'pending']);
            }

            $res          = $response->json();
            $mpStatus     = $res['state'] ?? 'OPEN';
            $paymentState = $res['payment']['state'] ?? null;

            Log::info('[Kiosk] Estado do payment intent', [
                'transaction_id' => $transactionId,
                'mp_state'       => $mpStatus,
                'payment_state'  => $paymentState,
            ]);

            // FINISHED = intent concluído; verificar se o pagamento foi aprovado
            if ($mpStatus === 'FINISHED') {
                if ($paymentState !== 'approved') {
                    Log::warning('[Kiosk] Pagamento finalizado mas não aprovado', [
                        'transaction_id' => $transactionId,
                        'payment_state'  => $paymentState,
                        'full_payment'   => $res['payment'] ?? null,
                    ]);
                    MercadoPagoTransaction::where('transaction_id', $transactionId)
                        ->update(['status' => 'rejected', 'payload' => $res]);
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Pagamento não aprovado na maquininha.',
                    ]);
                }

                // Atualiza transação
                MercadoPagoTransaction::where('transaction_id', $transactionId)
                    ->update(['status' => $mpStatus, 'payload' => $res]);

                $transaction = MercadoPagoTransaction::where('transaction_id', $transactionId)->first();

                Log::info('[Kiosk] Transação encontrada no banco', [
                    'transaction_id' => $transactionId,
                    'transaction'    => $transaction?->toArray(),
                ]);

                if ($transaction && $transaction->order_id) {
                    $order = Order::find($transaction->order_id);

                    Log::info('[Kiosk] Pedido encontrado', [
                        'order_id'       => $transaction->order_id,
                        'order_found'    => $order !== null,
                        'payment_status' => $order?->payment_status,
                    ]);

                    if ($order && $order->payment_status !== Order::PAYMENT_PAID) {
                        $log = $this->processarPagamentoAprovado(
                            $order,
                            $transaction->payment_type ?? 'credit_card'
                        );
                        Log::info('[Kiosk] processarPagamentoAprovado concluído', ['log' => $log]);
                    } else {
                        Log::info('[Kiosk] Pedido já estava pago, pulando processamento', [
                            'order_id' => $transaction->order_id,
                        ]);
                    }
                } else {
                    Log::error('[Kiosk] Transação sem order_id ou não encontrada', [
                        'transaction_id' => $transactionId,
                    ]);
                }

                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Pagamento aprovado!',
                    'order_id' => $transaction?->order_id,
                ]);
            }

            if (in_array($mpStatus, ['CANCELED', 'ERROR'])) {
                Log::warning('[Kiosk] Pagamento cancelado ou com erro', [
                    'transaction_id' => $transactionId,
                    'mp_state'       => $mpStatus,
                ]);
                MercadoPagoTransaction::where('transaction_id', $transactionId)
                    ->update(['status' => strtolower($mpStatus), 'payload' => $res]);

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pagamento cancelado ou recusado na maquininha.',
                ]);
            }

            // Ainda aguardando (OPEN ou outro estado)
            Log::debug('[Kiosk] Ainda aguardando pagamento', [
                'transaction_id' => $transactionId,
                'mp_state'       => $mpStatus,
            ]);

            return response()->json(['status' => 'pending']);

        } catch (\Throwable $e) {
            Log::error('[Kiosk] Erro ao consultar status', [
                'transaction_id' => $transactionId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'pending']);
        }
    }

    /**
     * Simula um pagamento aprovado para um pedido kiosk pendente.
     * Disponível apenas quando "teste_pagamento_ativo" estiver habilitado nas configurações.
     */
    public function simularPagamento(Request $request)
    {
        $setting = KioskSetting::instance();

        if (! $setting->teste_pagamento_ativo) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Simulação de pagamento está desativada nas configurações do Kiosk.',
            ], 403);
        }

        $request->validate([
            'order_id' => 'nullable|integer|exists:nexopos_orders,id',
        ]);

        // Se não informado, usa o último pedido kiosk não pago
        if ($request->filled('order_id')) {
            $order = Order::find($request->order_id);
        } else {
            $order = Order::where('payment_status', Order::PAYMENT_UNPAID)
                ->where('note', 'like', '%Pedido via Kiosk%')
                ->latest()
                ->first();
        }

        if (! $order) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Nenhum pedido kiosk pendente encontrado.',
            ], 404);
        }

        if ($order->payment_status === Order::PAYMENT_PAID) {
            return response()->json([
                'status'  => 'error',
                'message' => "Pedido #{$order->id} já está pago.",
            ], 422);
        }

        if (! str_contains($order->note ?? '', 'Pedido via Kiosk')) {
            return response()->json([
                'status'  => 'error',
                'message' => "Pedido #{$order->id} não é um pedido de kiosk.",
            ], 422);
        }

        try {
            $log = $this->processarPagamentoAprovado($order, 'credit_card');

            return response()->json([
                'status'   => 'success',
                'message'  => "✅ Pagamento simulado com sucesso para o pedido #{$order->id}!",
                'order_id' => $order->id,
                'detalhes' => $log,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Kiosk] Erro na simulação de pagamento', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Erro ao processar simulação: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Centraliza toda a lógica pós-aprovação de pagamento.
     * Chamado tanto pelo polling real do Mercado Pago quanto pela simulação de teste.
     *
     * @return array  Log de ações executadas
     */
    private function processarPagamentoAprovado(Order $order, string $paymentType): array
    {
        $setting = KioskSetting::instance();
        $log     = [];

        Auth::onceUsingId($setting->operator_user_id ?? 1);

        // Recarrega o pedido limpo para evitar que relações temporárias (ex: 'addresses'
        // setada via setRelations() no OrdersService::create) causem erro no refresh()
        $order = Order::find($order->id);

        /** @var OrdersService $ordersService */
        $ordersService = app(OrdersService::class);
        $ordersService->makeOrderSinglePayment([
            'identifier' => 'mercadopago',
            'value'      => $order->total,
        ], $order);

        $log[] = "Pagamento registrado (R$ {$order->total})";

        // ── Emite NFC-e somente se houver CPF informado ──────────────────
        if (preg_match('/CPF: (\d{11})/', $order->note ?? '', $m)) {
            try {
                $cpfNota = $m[1];
                if (class_exists(\Modules\NotaFiscal\Services\NfceService::class)) {
                    app(\Modules\NotaFiscal\Services\NfceService::class)->emitir($order, $cpfNota);
                    $log[] = "NFC-e emitida (CPF: {$cpfNota})";
                }
            } catch (\Throwable $e) {
                Log::warning('[Kiosk] Falha ao emitir NFC-e', ['error' => $e->getMessage()]);
                $log[] = 'NFC-e: falhou — ' . $e->getMessage();
            }
        }

        // ── Imprime cupom na impressora de rede ──────────────────────────
        try {
            $items = $order->products->map(fn ($p) => [
                'name'       => $p->name,
                'quantity'   => $p->quantity,
                'unit_price' => $p->unit_price,
            ])->toArray();

            // Extrai o WhatsApp da nota para exibir aviso no cupom
            $whatsapp = null;
            if (preg_match('/WhatsApp:\s*([\d\s\(\)\-\+]+)/i', $order->note ?? '', $wm)) {
                $whatsapp = trim($wm[1]);
            }

            $printed = app(EscPosPrinterService::class)->printReceipt(
                items:       $items,
                total:       (float) $order->total,
                mode:        $order->type ?? 'takeaway',
                phone:       $whatsapp,
                paymentType: $paymentType,
                orderId:     $order->id,
                setting:     $setting
            );

            $log[] = $printed ? 'Cupom impresso na impressora térmica' : 'Impressora desativada ou indisponível';
        } catch (\Throwable $e) {
            Log::warning('[Kiosk] Falha ao imprimir cupom', ['error' => $e->getMessage()]);
            $log[] = 'Impressão: falhou — ' . $e->getMessage();
        }

        return $log;
    }
}
