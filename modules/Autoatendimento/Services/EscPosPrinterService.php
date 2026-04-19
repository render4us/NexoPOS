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

    // ── DANFCE (real ou mock) ────────────────────────────────────────────

    /**
     * Imprime um DANFCE na térmica. Funciona tanto para NFC-e real (autorizada pela
     * SEFAZ) quanto para mock de homologação — basta passar os valores corretos de
     * chave/protocolo/qrUrl e setar $homologacao=true no ambiente de teste.
     */
    public function printDanfce(
        array $items,
        float $total,
        string $paymentType,
        int $nNF,
        int $serie,
        string $chaveAcesso,
        string $nProt,
        string $qrUrl,
        ?string $cpfConsumidor,
        KioskSetting $setting,
        bool $homologacao = false
    ): bool {
        if (! $setting->printer_enabled || ! $setting->printer_ip) {
            return false;
        }

        $this->cols = (int) ($setting->printer_columns ?: 48);

        $data = $this->buildDanfceReceipt(
            $items, $total, $paymentType, $nNF, $serie,
            $cpfConsumidor, $setting, $chaveAcesso, $nProt, $qrUrl, $homologacao
        );

        $label = $homologacao ? 'danfce-homolog' : 'danfce';
        return $this->send($data, $setting->printer_ip, $setting->printer_port ?: 9100, $label);
    }

    /**
     * Atalho para teste de layout — gera chave/protocolo/qrUrl fictícios e imprime em homologação.
     */
    public function printMockDanfce(
        array $items,
        float $total,
        string $paymentType,
        int $orderId,
        ?string $cpfConsumidor,
        KioskSetting $setting
    ): bool {
        $chaveAcesso = $this->buildMockChave($setting, $orderId);
        $nProt       = '135' . str_pad(date('dmY') . str_pad($orderId, 8, '0', STR_PAD_LEFT), 12, '0', STR_PAD_LEFT);
        $qrUrl       = 'https://homologacao.nfce.fazenda.sp.gov.br/qrcode'
                    . '?p=' . $chaveAcesso . '|2|2|1|MOCK' . md5($chaveAcesso);

        return $this->printDanfce(
            $items, $total, $paymentType,
            $orderId, 1,
            $chaveAcesso, $nProt, $qrUrl,
            $cpfConsumidor, $setting,
            homologacao: true
        );
    }

    private function buildDanfceReceipt(
        array $items, float $total, string $paymentType, int $nNF, int $serie,
        ?string $cpfConsumidor, KioskSetting $setting,
        string $chaveAcesso, string $nProt, string $qrUrl,
        bool $homologacao
    ): string {
        $sep = str_repeat('-', $this->cols);

        $storeName    = ns()->option->get('ns_store_name', $setting->titulo ?: 'LOJA');
        $storeCnpj    = ns()->option->get('ns_store_additional', '');
        $storeAddress = ns()->option->get('ns_store_address', '');
        $storeCity    = ns()->option->get('ns_store_city', '');

        $d = self::INIT;

        // ── Cabeçalho ────────────────────────────────────────────────────
        $d .= self::ALIGN_CENTER . self::BOLD_ON;
        $d .= $this->truncate(strtoupper($storeName), $this->cols) . self::LF;
        $d .= self::BOLD_OFF;
        if ($storeCnpj) {
            $d .= 'CNPJ: ' . $storeCnpj . self::LF;
        }
        if ($storeAddress) {
            $d .= $this->truncate($storeAddress, $this->cols) . self::LF;
        }
        if ($storeCity) {
            $d .= $this->truncate($storeCity, $this->cols) . self::LF;
        }

        // ── Título DANFCE ────────────────────────────────────────────────
        $d .= self::ALIGN_LEFT . $sep . self::LF;
        $d .= self::ALIGN_CENTER . self::BOLD_ON;
        $d .= 'DANFE NFC-e' . self::LF;
        $d .= self::BOLD_OFF;
        $d .= 'Documento Auxiliar da Nota Fiscal' . self::LF;
        $d .= 'de Consumidor Eletronica' . self::LF;
        $d .= self::ALIGN_LEFT . $sep . self::LF;

        // ── Itens (formato DANFCE) ───────────────────────────────────────
        $d .= self::BOLD_ON;
        $d .= $this->padLine('#   DESCRICAO                   QTD', 'VL TOTAL') . self::LF;
        $d .= self::BOLD_OFF;
        $d .= $sep . self::LF;

        $nItem     = 1;
        $totalQtd  = 0;
        foreach ($items as $item) {
            $name  = $item['name'] ?? '';
            $qty   = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $sub   = $qty * $price;
            $totalQtd += $qty;

            $line1 = sprintf('%03d %s', $nItem, $this->truncate($name, $this->cols - 4));
            $d .= $line1 . self::LF;

            $right = 'R$ ' . number_format($sub, 2, ',', '.');
            $left  = sprintf('    %dx R$ %s /UN', $qty, number_format($price, 2, ',', '.'));
            $d .= $this->padLine($left, $right) . self::LF;

            $nItem++;
        }

        $d .= $sep . self::LF;

        // ── Totais ───────────────────────────────────────────────────────
        $d .= $this->padLine('QTD. TOTAL DE ITENS', (string) $totalQtd) . self::LF;
        $d .= self::BOLD_ON . self::SIZE_TALL;
        $d .= $this->padLine('VALOR A PAGAR R$', number_format($total, 2, ',', '.')) . self::LF;
        $d .= self::SIZE_NORMAL . self::BOLD_OFF;

        $payLabel = match ($paymentType) {
            'credit_card' => 'Cartao de Credito',
            'debit_card'  => 'Cartao de Debito',
            'pix'         => 'PIX',
            default       => 'Outros',
        };
        $d .= $this->padLine('FORMA PAGTO.', $payLabel) . self::LF;
        $d .= $this->padLine('VALOR PAGO R$', number_format($total, 2, ',', '.')) . self::LF;
        $d .= $this->padLine('TROCO R$', '0,00') . self::LF;

        // ── Tarja homologação (apenas em ambiente 2) ────────────────────
        if ($homologacao) {
            $d .= $sep . self::LF;
            $d .= self::ALIGN_CENTER . self::BOLD_ON . self::SIZE_TALL;
            $d .= 'EMITIDA EM HOMOLOGACAO' . self::LF;
            $d .= 'SEM VALOR FISCAL' . self::LF;
            $d .= self::SIZE_NORMAL . self::BOLD_OFF;
            $d .= self::ALIGN_LEFT . $sep . self::LF;
        } else {
            $d .= $sep . self::LF;
        }

        // ── Consumidor ───────────────────────────────────────────────────
        $d .= self::BOLD_ON . 'CONSUMIDOR' . self::BOLD_OFF . self::LF;
        if ($cpfConsumidor) {
            $cpf = preg_replace('/\D/', '', $cpfConsumidor);
            if (strlen($cpf) === 11) {
                $cpfFmt = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                $d .= 'CPF: ' . $cpfFmt . self::LF;
            } else {
                $d .= 'CPF: ' . $cpf . self::LF;
            }
        } else {
            $d .= 'CONSUMIDOR NAO IDENTIFICADO' . self::LF;
        }
        $d .= $sep . self::LF;

        // ── Identificação da NFC-e ───────────────────────────────────────
        $d .= self::ALIGN_CENTER;
        $d .= 'NFC-e no. ' . str_pad((string) $nNF, 9, '0', STR_PAD_LEFT)
            . ' Serie ' . str_pad((string) $serie, 3, '0', STR_PAD_LEFT) . self::LF;
        $d .= 'Emissao: ' . date('d/m/Y H:i:s') . self::LF;
        $d .= $this->truncate('Protocolo: ' . $nProt, $this->cols) . self::LF;
        $d .= self::ALIGN_LEFT . $sep . self::LF;

        // ── Chave de acesso ──────────────────────────────────────────────
        $d .= self::ALIGN_CENTER . self::BOLD_ON . 'CHAVE DE ACESSO' . self::BOLD_OFF . self::LF;
        $d .= $this->formatChave($chaveAcesso) . self::LF;
        $d .= self::LF;
        $d .= 'Consulte pela Chave de Acesso em:' . self::LF;
        $d .= 'www.nfce.fazenda.sp.gov.br/consulta' . self::LF;
        $d .= self::ALIGN_LEFT . $sep . self::LF;

        // ── QR Code (ESC/POS nativo) ─────────────────────────────────────
        $d .= self::ALIGN_CENTER;
        $d .= $this->qrCodeCommand($qrUrl);
        $d .= self::LF;
        $d .= self::ALIGN_LEFT . $sep . self::LF;

        // ── Rodapé ───────────────────────────────────────────────────────
        $d .= self::LF . self::LF . self::LF;
        $d .= self::CUT_PARTIAL;

        return $d;
    }

    /**
     * Gera uma chave de acesso NFC-e fake de 44 dígitos seguindo o layout oficial.
     * cUF(2) + AAMM(4) + CNPJ(14) + mod(2=65) + serie(3) + nNF(9) + tpEmis(1) + cNF(8) + cDV(1)
     */
    private function buildMockChave(KioskSetting $setting, int $orderId): string
    {
        $cUF     = '35';                              // SP fixo para mock
        $aammm   = date('ym');
        $cnpj    = str_pad(preg_replace('/\D/', '', ns()->option->get('ns_store_additional', '46926665000121')), 14, '0', STR_PAD_LEFT);
        $mod     = '65';
        $serie   = '001';
        $nNF     = str_pad((string) $orderId, 9, '0', STR_PAD_LEFT);
        $tpEmis  = '1';
        $cNF     = str_pad((string) mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);

        $base = $cUF . $aammm . $cnpj . $mod . $serie . $nNF . $tpEmis . $cNF;

        // DV por módulo 11 (pesos 2..9 cíclicos da direita para a esquerda)
        $pesos = [2, 3, 4, 5, 6, 7, 8, 9];
        $soma  = 0;
        $len   = strlen($base);
        for ($i = $len - 1, $p = 0; $i >= 0; $i--, $p++) {
            $soma += (int) $base[$i] * $pesos[$p % 8];
        }
        $resto = $soma % 11;
        $dv    = ($resto < 2) ? 0 : 11 - $resto;

        return $base . $dv;
    }

    private function formatChave(string $chave): string
    {
        // Agrupa de 4 em 4 com espaços (layout oficial do DANFCE)
        return trim(chunk_split($chave, 4, ' '));
    }

    /**
     * Comando ESC/POS nativo para imprimir QR Code (Epson GS ( k).
     * Compatível com Epson, Bematech MP-4200 TH, Elgin i9, e maioria das térmicas modernas.
     */
    private function qrCodeCommand(string $data, int $size = 7, string $ecc = 'M'): string
    {
        $eccMap = ['L' => 0x30, 'M' => 0x31, 'Q' => 0x32, 'H' => 0x33];
        $eccVal = $eccMap[$ecc] ?? 0x31;

        $cmd  = "\x1D(k\x04\x00\x31\x41\x32\x00";                             // select model 2
        $cmd .= "\x1D(k\x03\x00\x31\x43" . chr($size);                         // module size
        $cmd .= "\x1D(k\x03\x00\x31\x45" . chr($eccVal);                       // error correction
        $len  = strlen($data) + 3;
        $cmd .= "\x1D(k" . chr($len & 0xFF) . chr(($len >> 8) & 0xFF) . "\x31\x50\x30" . $data;  // store
        $cmd .= "\x1D(k\x03\x00\x31\x51\x30";                                  // print

        return $cmd;
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
        // 'impressora' permite que o KioskServer roteie para a impressora correta
        $destino = $label === 'cozinha' ? 'cozinha' : 'totem';
        $payload = json_encode(['dados' => base64_encode($data), 'impressora' => $destino]);

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
