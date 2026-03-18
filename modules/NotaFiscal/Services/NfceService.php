<?php

namespace Modules\NotaFiscal\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\NotaFiscal\Models\NotaFiscalEmissao;
use Modules\NotaFiscal\Models\NotaFiscalSetting;
use NFePHP\Common\Certificate;
use NFePHP\DA\NFe\Danfce;
use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;

class NfceService
{
    // ── Mapeamento de formas de pagamento NexoPOS → tPag NFC-e ────────────────

    private const PAYMENT_MAP = [
        OrderPayment::PAYMENT_CASH    => '01', // Dinheiro
        OrderPayment::PAYMENT_BANK    => '15', // Boleto Bancário
        OrderPayment::PAYMENT_ACCOUNT => '99', // Outros (conta/crédito)
        'card-payment'                => '03', // Cartão de Crédito (genérico)
        'credit-card-payment'         => '03', // Cartão de Crédito
        'debit-card-payment'          => '04', // Cartão de Débito
        'pix-payment'                 => '17', // PIX
        'pix'                         => '17',
        'mercadopago'                 => '03', // Cartão via Mercado Pago
    ];

    // ── Ponto de entrada principal ─────────────────────────────────────────────

    /**
     * Emite uma NFC-e para o pedido informado.
     * Cria um registro em notafiscal_emissoes e atualiza-o com o resultado.
     *
     * @throws \Exception se as configurações fiscais estiverem incompletas
     */
    public function emitir(Order $order): NotaFiscalEmissao
    {
        /** @var NotaFiscalSetting $settings */
        $settings = NotaFiscalSetting::first();

        if (! $settings || ! $settings->ativo) {
            throw new \Exception('[NFC-e] Configurações fiscais não encontradas ou módulo inativo.');
        }

        if (! $settings->certificado_conteudo || ! $settings->certificado_senha) {
            throw new \Exception('[NFC-e] Certificado digital não configurado.');
        }

        // Garante que os produtos estão carregados
        $order->loadMissing(['products.product', 'payments']);

        // Reserva o número da nota (incrementa atomicamente)
        $nNF   = $settings->proximo_numero;
        $serie = $settings->serie;

        $settings->increment('proximo_numero');

        // Cria o registro de emissão como "pendente"
        $emissao = NotaFiscalEmissao::create([
            'order_id'  => $order->id,
            'n_nf'      => $nNF,
            'serie'     => $serie,
            'status'    => NotaFiscalEmissao::STATUS_PENDENTE,
            'ambiente'  => $settings->ambiente,
        ]);

        try {
            // 1. Monta o XML
            $xmlUnsigned = $this->buildXml($order, $settings, $nNF, $serie);

            // 2. Inicializa o Tools (assina + envia)
            $tools = $this->buildTools($settings);

            // 3. Assina o XML
            $xmlSigned = $tools->signNFe($xmlUnsigned);

            $emissao->update(['xml_enviado' => $xmlSigned]);

            // 4. Envia para a SEFAZ
            $idLote    = str_pad($order->id, 15, '0', STR_PAD_LEFT);
            $response  = $tools->sefazEnviaLote([$xmlSigned], $idLote);

            // 5. Interpreta o retorno
            $this->processResponse($emissao, $response, $xmlSigned, $settings);

        } catch (\Exception $e) {
            Log::error('[NFC-e] Falha na emissão — Pedido #' . $order->id . ': ' . $e->getMessage());

            $emissao->update([
                'status'   => NotaFiscalEmissao::STATUS_ERRO,
                'mensagem' => $e->getMessage(),
            ]);
        }

        return $emissao->fresh();
    }

    // ── Montagem do XML NFC-e ──────────────────────────────────────────────────

    private function buildXml(
        Order               $order,
        NotaFiscalSetting   $settings,
        int                 $nNF,
        int                 $serie
    ): string {
        $make = new Make();

        // ── infNFe ────────────────────────────────────────────────────────────
        $std          = new \stdClass();
        $std->versao  = '4.00';
        $make->taginfNFe($std);

        // ── ide ───────────────────────────────────────────────────────────────
        $std           = new \stdClass();
        $std->cUF      = $settings->getCodigoUf();
        $std->cNF      = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        $std->natOp    = 'VENDA AO CONSUMIDOR';
        $std->mod      = 65;                       // NFC-e
        $std->serie    = $serie;
        $std->nNF      = $nNF;
        $std->dhEmi    = now()->format('Y-m-d\TH:i:sP');
        $std->tpNF     = 1;                        // saída
        $std->idDest   = 1;                        // operação interna
        $std->cMunFG   = $settings->cod_municipio;
        $std->tpImp    = 4;                        // DANFCE
        $std->tpEmis   = 1;                        // emissão normal
        $std->cDV      = 0;                        // calculado pela biblioteca
        $std->tpAmb    = $settings->ambiente;
        $std->finNFe   = 1;                        // NF-e normal
        $std->indFinal = 1;                        // consumidor final
        $std->indPres  = 1;                        // operação presencial
        $std->procEmi  = 0;                        // aplicativo do emitente
        $std->verProc  = '1.0.0';
        $make->tagide($std);

        // ── emit ──────────────────────────────────────────────────────────────
        $std         = new \stdClass();
        $std->CNPJ   = $settings->cnpj;
        $std->xNome  = $settings->razao_social;
        $std->xFant  = $settings->nome_fantasia ?: $settings->razao_social;
        $std->IE     = $settings->ie ?? 'ISENTO';
        $std->CNAE   = $settings->cnae;
        $std->CRT    = $settings->crt;
        $make->tagemit($std);

        // ── enderEmit ─────────────────────────────────────────────────────────
        $std          = new \stdClass();
        $std->xLgr    = $settings->logradouro ?? 'N/I';
        $std->nro     = $settings->numero ?? 'S/N';
        $std->xCpl    = $settings->complemento ?? '';
        $std->xBairro = $settings->bairro ?? 'N/I';
        $std->cMun    = $settings->cod_municipio;
        $std->xMun    = $settings->municipio;
        $std->UF      = strtoupper($settings->uf);
        $std->CEP     = preg_replace('/\D/', '', $settings->cep ?? '');
        $std->cPais   = 1058;                      // Brasil
        $std->xPais   = 'Brasil';
        $std->fone    = preg_replace('/\D/', '', $settings->telefone ?? '');
        $make->tagenderEmit($std);

        // ── Itens ─────────────────────────────────────────────────────────────
        $nItem       = 1;
        $totalProd   = 0;
        $totalDesc   = 0;
        $totalIcms   = 0;

        foreach ($order->products as $item) {
            $product   = $item->product;
            $ncm       = $this->resolveProductField($product, 'fiscal_ncm', $settings->ncm_padrao ?? '00000000');
            $cfop      = $this->resolveProductField($product, 'fiscal_cfop', $settings->cfop_padrao);
            $orig      = $this->resolveProductField($product, 'fiscal_orig', '0');
            $unCom     = $this->resolveProductField($product, 'fiscal_un_com', 'UN');

            $vProdItem = round((float) $item->total_price_without_tax, 2);
            $vDescItem = round((float) ($item->discount ?? 0), 2);
            $totalProd += $vProdItem;
            $totalDesc += $vDescItem;

            // det
            $std       = new \stdClass();
            $std->item = $nItem;
            $make->tagdet($std);

            // prod
            $ean = $product?->barcode;
            // GTIN deve ter 8, 12, 13 ou 14 dígitos ou ser "SEM GTIN"
            if (! $ean || ! preg_match('/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', $ean)) {
                $ean = 'SEM GTIN';
            }

            $std           = new \stdClass();
            $std->item     = $nItem;
            $std->cProd    = str_pad((string) $item->product_id, 4, '0', STR_PAD_LEFT);
            $std->cEAN     = $ean;
            $std->xProd    = mb_substr($item->name, 0, 120);
            $std->NCM      = str_pad(preg_replace('/\D/', '', $ncm), 8, '0', STR_PAD_LEFT);
            $std->CFOP     = $cfop;
            $std->uCom     = strtoupper($unCom);
            $std->qCom     = (float) $item->quantity;
            $std->vUnCom   = round((float) $item->price_without_tax, 10);
            $std->vProd    = $vProdItem;
            $std->cEANTrib = $ean;
            $std->uTrib    = strtoupper($unCom);
            $std->qTrib    = (float) $item->quantity;
            $std->vUnTrib  = round((float) $item->price_without_tax, 10);
            $std->vDesc    = $vDescItem ?: null;
            $std->indTot   = 1;
            $make->tagprod($std);

            // imposto (container)
            $std           = new \stdClass();
            $std->item     = $nItem;
            $std->vTotTrib = 0;
            $make->tagimposto($std);

            // ── ICMS ──────────────────────────────────────────────────────────
            if ($settings->isSimlesNacional()) {
                $csosn         = $this->resolveProductField($product, 'fiscal_csosn', $settings->csosn_padrao);
                $std           = new \stdClass();
                $std->item     = $nItem;
                $std->orig     = $orig;
                $std->CSOSN    = $csosn;
                $make->tagICMSSN($std);
            } else {
                $cst           = $this->resolveProductField($product, 'fiscal_cst_icms', $settings->cst_icms_padrao ?? '00');
                $aliq          = (float) $settings->aliquota_icms_padrao;
                $vBC           = $vProdItem;
                $vICMS         = round($vBC * $aliq / 100, 2);
                $totalIcms    += $vICMS;

                $std           = new \stdClass();
                $std->item     = $nItem;
                $std->orig     = $orig;
                $std->CST      = str_pad($cst, 3, '0', STR_PAD_LEFT);
                $std->modBC    = 3;               // valor da operação
                $std->vBC      = $vBC;
                $std->pICMS    = $aliq;
                $std->vICMS    = $vICMS;
                $make->tagICMS($std);
            }

            // ── PIS ───────────────────────────────────────────────────────────
            if ($settings->isSimlesNacional()) {
                $std       = new \stdClass();
                $std->item = $nItem;
                $std->CST  = '07';                // isento / não tributado
                $make->tagPISNT($std);
            } else {
                $std        = new \stdClass();
                $std->item  = $nItem;
                $std->CST   = '01';
                $std->vBC   = $vProdItem;
                $std->pPIS  = 0.65;
                $std->vPIS  = round($vProdItem * 0.0065, 2);
                $make->tagPIS($std);
            }

            // ── COFINS ────────────────────────────────────────────────────────
            if ($settings->isSimlesNacional()) {
                $std       = new \stdClass();
                $std->item = $nItem;
                $std->CST  = '07';
                $make->tagCOFINSNT($std);
            } else {
                $std          = new \stdClass();
                $std->item    = $nItem;
                $std->CST     = '01';
                $std->vBC     = $vProdItem;
                $std->pCOFINS = 3.0;
                $std->vCOFINS = round($vProdItem * 0.03, 2);
                $make->tagCOFINS($std);
            }

            $nItem++;
        }

        // ── ICMSTot ───────────────────────────────────────────────────────────
        $vNF           = round((float) $order->total, 2);
        $vDisc         = round((float) $order->discount, 2);

        $std               = new \stdClass();
        $std->vBC          = 0;
        $std->vICMS        = round($totalIcms, 2);
        $std->vICMSDeson   = 0;
        $std->vFCP         = 0;
        $std->vBCST        = 0;
        $std->vST          = 0;
        $std->vFCPST       = 0;
        $std->vFCPSTRet    = 0;
        $std->vProd        = round($totalProd, 2);
        $std->vFrete       = 0;
        $std->vSeg         = 0;
        $std->vDesc        = $vDisc;
        $std->vII          = 0;
        $std->vIPI         = 0;
        $std->vIPIDevol    = 0;
        $std->vPIS         = 0;
        $std->vCOFINS      = 0;
        $std->vOutro       = 0;
        $std->vNF          = $vNF;
        $std->vTotTrib     = 0;
        $make->tagICMSTot($std);

        // ── pag ───────────────────────────────────────────────────────────────
        $std         = new \stdClass();
        $std->vTroco = max(0, round(((float)($order->tendered ?? $order->total)) - $vNF, 2));
        $make->tagpag($std);

        $paymentIndex = 1;
        foreach ($order->payments as $payment) {
            $tPag          = self::PAYMENT_MAP[$payment->identifier] ?? '99';
            $std           = new \stdClass();
            $std->item     = $paymentIndex;
            $std->tPag     = $tPag;
            $std->vPag     = round((float) $payment->value, 2);
            $make->tagdetPag($std);
            $paymentIndex++;
        }

        // Garante que há ao menos um pagamento (NFC-e exige)
        if ($order->payments->isEmpty()) {
            $std       = new \stdClass();
            $std->item = 1;
            $std->tPag = '99';
            $std->vPag = $vNF;
            $make->tagdetPag($std);
        }

        // ── infAdic ───────────────────────────────────────────────────────────
        $std           = new \stdClass();
        $std->infCpl   = 'Pedido NexoPOS: ' . $order->code;
        $std->infAdFisco = null;
        $make->taginfAdic($std);

        return $make->getXML();
    }

    // ── Inicializa o Tools da NFePHP ──────────────────────────────────────────

    private function buildTools(NotaFiscalSetting $settings): Tools
    {
        $config = json_encode([
            'atualizacao' => now()->format('Y-m-d H:i:s'),
            'tpAmb'       => $settings->ambiente,
            'razaosocial' => $settings->razao_social,
            'siglaUF'     => strtoupper($settings->uf),
            'cnpj'        => $settings->cnpj,
            'schemes'     => 'PL_009_V4',
            'versao'      => '4.00',
            'tokenIBPT'   => '',
            'CSC'         => $settings->csc ?? '',
            'CSCid'       => $settings->csc_id ?? '000001',
        ]);

        $certificate = Certificate::readPfx(
            base64_decode($settings->certificado_conteudo),
            $settings->certificado_senha
        );

        $tools = new Tools($config, $certificate);
        $tools->model('65'); // NFC-e

        return $tools;
    }

    // ── Processa a resposta da SEFAZ ──────────────────────────────────────────

    private function processResponse(
        NotaFiscalEmissao $emissao,
        string            $response,
        string            $xmlSigned,
        NotaFiscalSetting $settings
    ): void {
        $emissao->update(['xml_retorno' => $response]);

        // Tenta extrair protocolo e cStat do XML de retorno
        try {
            $xml   = new \SimpleXMLElement($response);
            $proto = $xml->xpath('//infProt');

            if (! empty($proto)) {
                $proto  = $proto[0];
                $cstat  = (string) $proto->cStat;
                $xMotivo = (string) $proto->xMotivo;
                $nProt  = (string) $proto->nProt;
                $chave  = (string) $proto->chNFe;

                if ($cstat === '100') {
                    // Autorizada
                    $danfcePath = $this->generateDanfce($response, $chave);

                    $emissao->update([
                        'status'    => NotaFiscalEmissao::STATUS_AUTORIZADA,
                        'cstat'     => $cstat,
                        'mensagem'  => $xMotivo,
                        'chave'     => $chave,
                        'protocolo' => $nProt,
                        'danfce_path' => $danfcePath,
                    ]);

                    Log::info('[NFC-e] Nota autorizada — Pedido #' . $emissao->order_id
                        . ' | Chave: ' . $chave . ' | Protocolo: ' . $nProt);
                } else {
                    $emissao->update([
                        'status'   => NotaFiscalEmissao::STATUS_REJEITADA,
                        'cstat'    => $cstat,
                        'mensagem' => "cStat {$cstat}: {$xMotivo}",
                    ]);

                    Log::warning('[NFC-e] Nota rejeitada — Pedido #' . $emissao->order_id
                        . " | cStat {$cstat}: {$xMotivo}");
                }
            } else {
                // Resposta inesperada, salva como erro
                $emissao->update([
                    'status'   => NotaFiscalEmissao::STATUS_ERRO,
                    'mensagem' => 'Resposta SEFAZ sem infProt: ' . substr($response, 0, 500),
                ]);
            }
        } catch (\Exception $e) {
            $emissao->update([
                'status'   => NotaFiscalEmissao::STATUS_ERRO,
                'mensagem' => 'Erro ao interpretar resposta: ' . $e->getMessage(),
            ]);
        }
    }

    // ── Gera o DANFCE em PDF ──────────────────────────────────────────────────

    private function generateDanfce(string $xmlAuthorized, string $chave): ?string
    {
        try {
            $danfce  = new Danfce($xmlAuthorized);
            $pdf     = $danfce->render();
            $path    = "danfce/{$chave}.pdf";

            Storage::disk('local')->put($path, $pdf);

            return $path;
        } catch (\Exception $e) {
            Log::warning('[NFC-e] Falha ao gerar DANFCE: ' . $e->getMessage());
            return null;
        }
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    /**
     * Retorna o valor fiscal do produto, ou o default das configurações.
     */
    private function resolveProductField(
        ?\App\Models\Product $product,
        string               $field,
        ?string              $default
    ): string {
        $value = $product?->$field;
        return (! empty($value)) ? $value : ($default ?? '');
    }
}
