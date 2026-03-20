<?php

namespace Modules\Autoatendimento\Services;

use Illuminate\Support\Facades\Log;
use Modules\Autoatendimento\Models\KioskSetting;

/**
 * Envia cupom fiscal/pedido para impressora(s) térmica(s) via TCP (porta 9100).
 * Usa comandos ESC/POS nativos, sem dependências externas.
 *
 * Suporta duas impressoras:
 *  - Totem/Cliente : cupom completo com info da loja, itens, total e aviso WhatsApp
 *  - Cozinha       : ticket simplificado com número do pedido e itens (sem preços)
 */
class EscPosPrinterService
{
    // ── Constantes ESC/POS ───────────────────────────────────────────────
    const INIT         = "\x1B\x40";
    const ALIGN_LEFT   = "\x1B\x61\x00";
    const ALIGN_CENTER = "\x1B\x61\x01";
    const ALIGN_RIGHT  = "\x1B\x61\x02";
    const BOLD_ON      = "\x1B\x45\x01";
    const BOLD_OFF     = "\x1B\x45\x00";
    const SIZE_NORMAL  = "\x1B\x21\x00";
    const SIZE_DOUBLE  = "\x1B\x21\x30";
    const SIZE_TALL    = "\x1B\x21\x10";
    const CUT_PARTIAL  = "\x1D\x56\x01";
    const LF           = "\n";

    private int $cols;

    // ── Ponto de entrada público ─────────────────────────────────────────

    /**
     * @param array       $items       [{name, quantity, unit_price}]
     * @param float       $total
     * @param string      $mode        'eat_in'|'takeaway'
     * @param string|null $phone       WhatsApp do cliente (extraído da nota)
     * @param string      $paymentType 'credit_card'|'debit_card'
     * @param int         $orderId
     * @param KioskSetting $setting
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
        $printed = false;

        // ── Cupom do totem/cliente ────────────────────────────────────────
        if ($setting->printer_enabled && $setting->printer_ip) {
            $this->cols = (int) ($setting->printer_columns ?: 48);
            $data = $this->buildCustomerReceipt($items, $total, $mode, $phone, $paymentType, $orderId, $setting);
            $printed = $this->send($data, $setting->printer_ip, $setting->printer_port ?: 9100, 'totem');
        }

        // ── Ticket da cozinha ─────────────────────────────────────────────
        if ($setting->kitchen_printer_enabled && $setting->kitchen_printer_ip) {
            $this->cols = (int) ($setting->kitchen_printer_columns ?: 48);
            $data = $this->buildKitchenTicket($items, $mode, $orderId, $setting);
            $this->send($data, $setting->kitchen_printer_ip, $setting->kitchen_printer_port ?: 9100, 'cozinha');
        }

        return $printed;
    }

    // ── Cupom completo (totem / cliente) ─────────────────────────────────

    private function buildCustomerReceipt(
        array $items,
        float $total,
        string $mode,
        ?string $phone,
        string $paymentType,
        int $orderId,
        KioskSetting $setting
    ): string {
        $sep = str_repeat('-', $this->cols);

        // Dados da loja
        $storeName    = ns()->option->get('ns_store_name', $setting->titulo ?: 'LOJA');
        $storeAddress = ns()->option->get('ns_store_address', '');
        $storeCity    = ns()->option->get('ns_store_city', '');
        $storePhone   = ns()->option->get('ns_store_phone', '');
        $storeFax     = ns()->option->get('ns_store_fax', '');       // WhatsApp
        $storeCnpj    = ns()->option->get('ns_store_additional', ''); // CNPJ

        $d = self::INIT;

        // ── Cabeçalho ────────────────────────────────────────────────────
        $d .= self::ALIGN_CENTER;
        $d .= self::BOLD_ON . self::SIZE_DOUBLE;
        $d .= $this->truncate(strtoupper($storeName), (int) ($this->cols / 2)) . self::LF;
        $d .= self::SIZE_NORMAL . self::BOLD_OFF;

        if ($storeFax) {
            $d .= $storeFax . self::LF;
        }

        $d .= self::LF;
        $d .= self::ALIGN_LEFT . $sep . self::LF;

        // ── Info do pedido ───────────────────────────────────────────────
        $d .= self::BOLD_ON;
        $d .= $this->padLine('Pedido #' . $orderId, date('d/m/Y H:i')) . self::LF;
        $d .= self::BOLD_OFF;
        $d .= $this->padLine('Tipo:', $mode === 'eat_in' ? 'Comer no local' : 'Para levar') . self::LF;
        $d .= $sep . self::LF;

        // ── Itens ────────────────────────────────────────────────────────
        $d .= self::BOLD_ON . $this->padLine('ITEM', 'SUBTOTAL') . self::LF . self::BOLD_OFF;
        $d .= $sep . self::LF;

        foreach ($items as $item) {
            $name     = $item['name'] ?? '';
            $qty      = (int) ($item['quantity'] ?? 1);
            $price    = (float) ($item['unit_price'] ?? 0);
            $subtotal = $qty * $price;
            $subStr   = 'R$ ' . number_format($subtotal, 2, ',', '.');
            $nameLine = $qty . 'x ' . $name;

            if (strlen($nameLine) + strlen($subStr) + 1 <= $this->cols) {
                $d .= $this->padLine($nameLine, $subStr) . self::LF;
            } else {
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
        $d .= $sep . self::LF;

        // ── Aviso WhatsApp ───────────────────────────────────────────────
        if ($phone) {
            $d .= self::ALIGN_CENTER;
            $d .= self::BOLD_ON;
            $d .= 'Notificacao WhatsApp' . self::LF;
            $d .= self::BOLD_OFF;
            $d .= 'Voce recebera uma mensagem' . self::LF;
            $d .= 'quando o pedido estiver pronto!' . self::LF;
            $d .= $this->truncate('No: ' . $phone, $this->cols) . self::LF;
            $d .= self::ALIGN_LEFT . $sep . self::LF;
        }

        // ── Rodapé ───────────────────────────────────────────────────────
        $d .= self::ALIGN_CENTER;
        $d .= 'Obrigado pela preferencia!' . self::LF;
        $d .= 'Volte sempre! :)' . self::LF;
        $d .= self::LF;
        $d .= $sep . self::LF;

        if ($storeAddress) {
            $d .= $this->truncate($storeAddress, $this->cols) . self::LF;
        }
        if ($storeCity) {
            $d .= $this->truncate($storeCity, $this->cols) . self::LF;
        }
        if ($storePhone) {
            $d .= 'Tel: ' . $storePhone . self::LF;
        }
        if ($storeCnpj) {
            $d .= 'CNPJ: ' . $storeCnpj . self::LF;
        }

        $d .= self::LF . self::LF . self::LF;
        $d .= self::CUT_PARTIAL;

        return $d;
    }

    // ── Ticket de cozinha ────────────────────────────────────────────────

    private function buildKitchenTicket(
        array $items,
        string $mode,
        int $orderId,
        KioskSetting $setting
    ): string {
        $cols      = $this->cols;
        $sep       = str_repeat('=', $cols);
        $storeName = ns()->option->get('ns_store_name', $setting->titulo ?: 'LOJA');

        $d = self::INIT;

        // ── Cabeçalho ────────────────────────────────────────────────────
        $d .= self::ALIGN_CENTER;
        $d .= self::BOLD_ON . self::SIZE_NORMAL;
        $d .= $this->truncate(strtoupper($storeName), $cols) . self::LF;
        $d .= self::BOLD_OFF;
        $d .= self::BOLD_ON . self::SIZE_DOUBLE;
        $d .= 'PEDIDO #' . $orderId . self::LF;
        $d .= self::SIZE_NORMAL . self::BOLD_OFF;
        $d .= date('d/m/Y H:i:s') . self::LF;
        $d .= self::LF;

        $d .= self::ALIGN_LEFT . $sep . self::LF;

        // ── Tipo ─────────────────────────────────────────────────────────
        $d .= self::BOLD_ON . self::SIZE_TALL;
        $modeLabel = $mode === 'eat_in' ? '>> COMER NO LOCAL <<' : '>>  PARA LEVAR  <<';
        $d .= self::ALIGN_CENTER . $modeLabel . self::LF;
        $d .= self::SIZE_NORMAL . self::BOLD_OFF;
        $d .= self::ALIGN_LEFT . $sep . self::LF;

        // ── Itens (sem preço) ────────────────────────────────────────────
        foreach ($items as $item) {
            $qty  = (int) ($item['quantity'] ?? 1);
            $name = $item['name'] ?? '';
            $d .= self::BOLD_ON;
            $d .= $this->truncate($qty . 'x  ' . strtoupper($name), $cols) . self::LF;
            $d .= self::BOLD_OFF;
        }

        $d .= $sep . self::LF;
        $d .= self::LF . self::LF . self::LF;
        $d .= self::CUT_PARTIAL;

        return $d;
    }

    // ── Helpers de formatação ────────────────────────────────────────────

    private function padLine(string $left, string $right): string
    {
        $left   = $this->truncate($left, $this->cols - strlen($right) - 1);
        $spaces = $this->cols - strlen($left) - strlen($right);

        return $left . str_repeat(' ', max(1, $spaces)) . $right;
    }

    private function truncate(string $text, int $max): string
    {
        return strlen($text) > $max ? substr($text, 0, $max) : $text;
    }

    // ── Envio TCP / HTTP ─────────────────────────────────────────────────

    private function send(string $data, string $ip, int $port, string $label): bool
    {
        $httpUrl = config('kiosk.printer_http_url')
            ?: ns()->option->get('kiosk_printer_http_url');

        if ($httpUrl) {
            return $this->sendViaHttp($data, rtrim($httpUrl, '/') . '/imprimir', $label);
        }

        return $this->sendViaTcp($data, $ip, $port, $label);
    }

    private function sendViaHttp(string $data, string $url, string $label): bool
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
            Log::warning("[Kiosk] Kiosk Server ({$label}) indisponível: {$error}");
            return false;
        }

        if ($httpCode !== 200) {
            Log::warning("[Kiosk] Kiosk Server ({$label}) retornou HTTP {$httpCode}: {$response}");
            return false;
        }

        Log::debug("[Kiosk] Cupom ({$label}) enviado via Kiosk Server");
        return true;
    }

    private function sendViaTcp(string $data, string $ip, int $port, string $label): bool
    {
        $socket = @fsockopen($ip, $port, $errno, $errstr, 3);

        if (! $socket) {
            Log::warning("[Kiosk] Impressora ({$label}) indisponível em {$ip}:{$port} — {$errstr} ({$errno})");
            return false;
        }

        $written = fwrite($socket, $data);
        fclose($socket);

        if ($written === false) {
            Log::warning("[Kiosk] Falha ao escrever na impressora ({$label}) {$ip}:{$port}");
            return false;
        }

        Log::debug("[Kiosk] Cupom ({$label}) impresso em {$ip}:{$port} ({$written} bytes)");
        return true;
    }
}
