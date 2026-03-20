<?php

namespace Modules\EvolutionWhatsApp\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\EvolutionWhatsApp\Models\EvolutionWhatsAppSetting;

class EvolutionApiService
{
    /**
     * Envia mensagem de confirmação de pagamento (pedido PAGO).
     */
    public function enviarConfirmacaoPedido(Order $order): bool
    {
        $setting = EvolutionWhatsAppSetting::instance();

        if (! $setting->ativo) {
            return false;
        }

        $phone = $this->extrairTelefone($order->note ?? '');

        if (! $phone) {
            Log::debug('[EvolutionWhatsApp] Pedido #' . $order->id . ' sem telefone na nota — mensagem não enviada.');
            return false;
        }

        $mensagem = $this->montarMensagem($setting->mensagem_template, $order);

        return $this->enviar($setting, $phone, $mensagem);
    }

    /**
     * Envia mensagem de notificação de criação do pedido (kiosk).
     */
    public function enviarNotificacaoCriacao(Order $order): bool
    {
        $setting = EvolutionWhatsAppSetting::instance();

        if (! $setting->ativo || ! $setting->disparar_ao_criar) {
            return false;
        }

        $phone = $this->extrairTelefone($order->note ?? '');

        if (! $phone) {
            Log::debug('[EvolutionWhatsApp] Pedido #' . $order->id . ' sem telefone na nota — mensagem de criação não enviada.');
            return false;
        }

        $template = $setting->mensagem_template_criacao ?: $setting->mensagem_template;
        $mensagem = $this->montarMensagem($template, $order);

        return $this->enviar($setting, $phone, $mensagem);
    }

    /**
     * Envia uma mensagem de texto via Evolution API.
     */
    public function enviar(EvolutionWhatsAppSetting $setting, string $phone, string $mensagem): bool
    {
        $url = rtrim($setting->api_url, '/') . '/message/sendText/' . $setting->instance_name;

        try {
            $response = Http::withHeaders([
                'apikey'       => $setting->api_key,
                'Content-Type' => 'application/json',
            ])
                ->timeout(15)
                ->post($url, [
                    'number' => $phone,
                    'text'   => $mensagem,
                ]);

            if ($response->successful()) {
                Log::info('[EvolutionWhatsApp] Mensagem enviada para ' . $phone, [
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ]);
                return true;
            }

            Log::warning('[EvolutionWhatsApp] Falha ao enviar mensagem para ' . $phone, [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('[EvolutionWhatsApp] Erro de comunicação com a API', [
                'error' => $e->getMessage(),
                'phone' => $phone,
            ]);
            return false;
        }
    }

    /**
     * Extrai o número de telefone da nota do pedido.
     * Procura pelo padrão "WhatsApp: <numero>".
     */
    public function extrairTelefone(string $note): ?string
    {
        if (! preg_match('/WhatsApp:\s*([\d\s\(\)\-\+]+)/i', $note, $matches)) {
            return null;
        }

        // Remove tudo que não é dígito
        $digits = preg_replace('/\D/', '', $matches[1]);

        if (empty($digits)) {
            return null;
        }

        // Garante o código do Brasil (55) se não tiver DDI
        if (strlen($digits) <= 11) {
            $digits = '55' . $digits;
        }

        return $digits;
    }

    /**
     * Monta a mensagem substituindo as variáveis do template.
     *
     * Variáveis disponíveis:
     *  {pedido_id}       — número do pedido
     *  {total}           — total formatado (ex: 38,00)
     *  {itens}           — lista de produtos com qtd e valor
     *  {loja}            — nome da loja
     *  {modo}            — Mesa ou Viagem (eat_in / takeaway)
     *  {data}            — data e hora do pedido
     *  {forma_pagamento} — forma de pagamento usada (se disponível)
     */
    public function montarMensagem(string $template, Order $order): string
    {
        $itens = $order->products->map(function ($p) {
            $qtd   = (int) $p->quantity;
            $nome  = $p->name;
            $valor = 'R$ ' . number_format((float) $p->unit_price * $qtd, 2, ',', '.');
            return "• {$qtd}x {$nome} — {$valor}";
        })->implode("\n");

        $total = number_format((float) $order->total, 2, ',', '.');
        $loja  = ns()->option->get('ns_store_name', 'SnowSYS');

        // Modo: Mesa ou Viagem
        $modoRaw = $order->type ?? '';
        $modo    = match (true) {
            str_contains($modoRaw, 'eat_in') => 'Mesa',
            str_contains($modoRaw, 'takeaway') => 'Viagem',
            default => $modoRaw,
        };

        // Data/hora do pedido
        $data = $order->created_at
            ? \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i')
            : now()->format('d/m/Y H:i');

        // Forma de pagamento (primeiro pagamento registrado, se houver)
        $formaLabels = [
            'mercadopago' => 'Maquininha (Mercado Pago)',
            'credit_card' => 'Cartão de Crédito',
            'debit_card'  => 'Cartão de Débito',
            'cash'        => 'Dinheiro',
            'pix'         => 'PIX',
        ];

        $primeiroPagamento = $order->payments->first();
        $formaIdentifier   = $primeiroPagamento->identifier ?? '';
        $formaPagamento    = $formaLabels[$formaIdentifier] ?? ($formaIdentifier ?: 'Não informado');

        return str_replace(
            ['{pedido_id}', '{total}', '{itens}', '{loja}', '{modo}', '{data}', '{forma_pagamento}'],
            [$order->id,    $total,    $itens,    $loja,    $modo,    $data,    $formaPagamento],
            $template
        );
    }
}
