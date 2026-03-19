<?php

namespace Modules\Autoatendimento\Services;

use Illuminate\Support\Facades\Log;
use Modules\Autoatendimento\Models\KioskSetting;

/**
 * Envia cupom fiscal/pedido para impressora térmica via TCP (porta 9100).
 * Usa comandos ESC/POS nativos, sem dependências externas.
 */
class EscPosPrinterService
{
    // ── Constantes ESC/POS ───────────────────────────────────────────────
    const ESC = "\x1B";
    const GS  = "\x1D";
    const LF  = "\n";

    const INIT         = "\x1B\x40";          // Inicializa impressora
    const ALIGN_LEFT   = "\x1B\x61\x00";
    const ALIGN_CENTER = "\x1B\x61\x01";
    const ALIGN_RIGHT  = "\x1B\x61\x02";
    const BOLD_ON      = "\x1B\x45\x01";
    const BOLD_OFF     = "\x1B\x45\x00";
    const SIZE_NORMAL  = "\x1B\x21\x00";
    const SIZE_DOUBLE  = "\x1B\x21\x30";      // Altura + largura dobrada
    const SIZE_TALL    = "\x1B\x21\x10";      // Só altura dobrada
    const CUT_FULL     = "\x1D\x56\x00";
    const CUT_PARTIAL  = "\x1D\x56\x01";

    private int $cols;

    // ── Ponto de entrada público ─────────────────────────────────────────

    /**
     * @param array $items    [{name, quantity, unit_price}]
     * @param float $total
     * @param string $mode    'eat_in'|'takeaway'
     * @param string|null $phone
     * @param string $paymentType 'credit_card'|'debit_card'
     * @param int $orderId
     */
    public function printReceipt(
        array $items,
        float $total,
        string $mode,
        ?string $phone,
        string $paymentType,
        int $orderId,
        KioskSetting $setting
    ): bool {
        if (! $setting->printer_enabled || ! $setting->printer_ip) {
            return false;
        }

        $this->cols = (int) ($setting->printer_columns ?: 48);

        $data = $this->buildReceipt($items, $total, $mode, $phone, $paymentType, $orderId, $setting);

        return $this->send($data, $setting->printer_ip, $setting->printer_port ?: 9100);
    }

    // ── Construção do cupom ──────────────────────────────────────────────

    private function buildReceipt(
        array $items,
        float $total,
        string $mode,
        ?string $phone,
        string $paymentType,
        int $orderId,
        KioskSetting $setting
    ): string {
        $d = '';
        $sep = str_repeat('-', $this->cols);

        // Inicializa
        $d .= self::INIT;

        // ── Cabeçalho ────────────────────────────────────────────────────
        $d .= self::ALIGN_CENTER;
        $d .= self::BOLD_ON . self::SIZE_DOUBLE;
        $d .= $this->truncate($setting->titulo ?: 'KIOSK', $this->cols / 2) . self::LF;
        $d .= self::SIZE_NORMAL . self::BOLD_OFF;

        if ($setting->subtitulo) {
            $d .= $this->truncate($setting->subtitulo, $this->cols) . self::LF;
        }

        $d .= self::LF;
        $d .= self::ALIGN_LEFT;
        $d .= $sep . self::LF;

        // ── Info do pedido ───────────────────────────────────────────────
        $d .= self::BOLD_ON;
        $d .= $this->padLine('Pedido #' . $orderId, date('d/m/Y H:i')) . self::LF;
        $d .= self::BOLD_OFF;
        $d .= $this->padLine('Tipo:', $mode === 'eat_in' ? 'Comer no local' : 'Para levar') . self::LF;
        $d .= $sep . self::LF;

        // ── Itens ────────────────────────────────────────────────────────
        $d .= self::BOLD_ON;
        $d .= $this->padLine('ITEM', 'SUBTOTAL') . self::LF;
        $d .= self::BOLD_OFF;
        $d .= $sep . self::LF;

        foreach ($items as $item) {
            $name     = $item['name'] ?? '';
            $qty      = (int) ($item['quantity'] ?? 1);
            $price    = (float) ($item['unit_price'] ?? 0);
            $subtotal = $qty * $price;

            // Linha do nome (pode quebrar em 2 linhas se longo)
            $nameLine = $qty . 'x ' . $name;
            $subStr   = 'R$ ' . number_format($subtotal, 2, ',', '.');

            if (strlen($nameLine) + strlen($subStr) + 1 <= $this->cols) {
                $d .= $this->padLine($nameLine, $subStr) . self::LF;
            } else {
                // Nome na linha acima, subtotal alinhado à direita abaixo
                $d .= $this->truncate($nameLine, $this->cols) . self::LF;
                $d .= $this->padLine('  @ R$ ' . number_format($price, 2, ',', '.') . ' cada', $subStr) . self::LF;
            }
        }

        $d .= $sep . self::LF;

        // ── Total ────────────────────────────────────────────────────────
        $d .= self::BOLD_ON . self::SIZE_TALL;
        $d .= $this->padLine('TOTAL:', 'R$ ' . number_format($total, 2, ',', '.')) . self::LF;
        $d .= self::SIZE_NORMAL . self::BOLD_OFF;
        $d .= $sep . self::LF;

        // ── Pagamento ────────────────────────────────────────────────────
        $payLabel = $paymentType === 'credit_card' ? 'Cartao de Credito' : 'Cartao de Debito';
        $d .= $this->padLine('Pagamento:', $payLabel) . self::LF;

        if ($phone) {
            $d .= $this->padLine('WhatsApp:', $phone) . self::LF;
        }

        $d .= $sep . self::LF;

        // ── Rodapé ───────────────────────────────────────────────────────
        $d .= self::ALIGN_CENTER;
        $d .= 'Obrigado pela preferencia!' . self::LF;
        $d .= 'Volte sempre! :)' . self::LF;
        $d .= self::LF;
        $d .= self::LF;
        $d .= self::LF;

        // Corte
        $d .= self::CUT_PARTIAL;

        return $d;
    }

    // ── Helpers de formatação ────────────────────────────────────────────

    /** Linha com texto esquerdo e texto direito preenchendo a largura */
    private function padLine(string $left, string $right): string
    {
        $left  = $this->truncate($left,  $this->cols - strlen($right) - 1);
        $spaces = $this->cols - strlen($left) - strlen($right);

        return $left . str_repeat(' ', max(1, $spaces)) . $right;
    }

    private function truncate(string $text, int $max): string
    {
        return strlen($text) > $max ? substr($text, 0, $max) : $text;
    }

    // ── Envio: HTTP (kiosk server) ou TCP direto ─────────────────────────

    private function send(string $data, string $ip, int $port): bool
    {
        // Se houver um kiosk server configurado, usa HTTP em vez de TCP direto.
        // Isso resolve o caso em que a impressora está em outra sub-rede.
        $httpUrl = config('kiosk.printer_http_url')
            ?: ns()->option->get('kiosk_printer_http_url');

        if ($httpUrl) {
            return $this->sendViaHttp($data, rtrim($httpUrl, '/') . '/imprimir');
        }

        return $this->sendViaTcp($data, $ip, $port);
    }

    private function sendViaHttp(string $data, string $url): bool
    {
        $payload = json_encode(['dados' => base64_encode($data)]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::warning("[Kiosk] Kiosk Server indisponível: {$error}");
            return false;
        }

        if ($httpCode !== 200) {
            Log::warning("[Kiosk] Kiosk Server retornou HTTP {$httpCode}: {$response}");
            return false;
        }

        Log::debug("[Kiosk] Cupom enviado via Kiosk Server ({$url})");
        return true;
    }

    private function sendViaTcp(string $data, string $ip, int $port): bool
    {
        $socket = @fsockopen($ip, $port, $errno, $errstr, 3);

        if (! $socket) {
            Log::warning("[Kiosk] Impressora indisponível em {$ip}:{$port} — {$errstr} ({$errno})");
            return false;
        }

        $written = fwrite($socket, $data);
        fclose($socket);

        if ($written === false) {
            Log::warning("[Kiosk] Falha ao escrever na impressora {$ip}:{$port}");
            return false;
        }

        Log::debug("[Kiosk] Cupom impresso em {$ip}:{$port} ({$written} bytes)");
        return true;
    }
}
