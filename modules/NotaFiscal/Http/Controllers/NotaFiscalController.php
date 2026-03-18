<?php

namespace Modules\NotaFiscal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Modules\NotaFiscal\Models\NotaFiscalEmissao;
use Modules\NotaFiscal\Models\NotaFiscalSetting;
use Modules\NotaFiscal\Services\NfceService;

class NotaFiscalController extends Controller
{
    // ── Página de configurações ────────────────────────────────────────────────

    public function configuracoes()
    {
        $settings = NotaFiscalSetting::first() ?? new NotaFiscalSetting();

        return View::make('NotaFiscal::pages.configuracoes', compact('settings'));
    }

    public function salvarConfiguracoes(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ativo'                  => 'boolean',
            'ambiente'               => 'required|in:1,2',
            'cnpj'                   => 'required|size:14|regex:/^\d{14}$/',
            'razao_social'           => 'required|max:60',
            'nome_fantasia'          => 'nullable|max:60',
            'ie'                     => 'nullable|max:14',
            'cnae'                   => 'nullable|max:7',
            'uf'                     => 'required|size:2',
            'cod_municipio'          => 'required|max:7',
            'municipio'              => 'required|max:60',
            'cep'                    => 'nullable|max:8',
            'logradouro'             => 'nullable|max:60',
            'numero'                 => 'nullable|max:10',
            'complemento'            => 'nullable|max:60',
            'bairro'                 => 'nullable|max:60',
            'telefone'               => 'nullable|max:11',
            'crt'                    => 'required|in:1,2,3',
            'serie'                  => 'required|integer|min:1',
            'proximo_numero'         => 'required|integer|min:1',
            'certificado_senha'      => 'nullable|max:255',
            'csc'                    => 'nullable|max:36',
            'csc_id'                 => 'nullable|max:6',
            'ncm_padrao'             => 'nullable|max:8',
            'cfop_padrao'            => 'required|max:4',
            'csosn_padrao'           => 'nullable|max:3',
            'cst_icms_padrao'        => 'nullable|max:3',
            'aliquota_icms_padrao'   => 'nullable|numeric|min:0|max:100',
            'emitir_automaticamente' => 'boolean',
        ]);

        // Upload do certificado .pfx (base64)
        if ($request->hasFile('certificado_arquivo')) {
            $pfx = $request->file('certificado_arquivo');
            $data['certificado_conteudo'] = base64_encode(file_get_contents($pfx->getRealPath()));
        }

        $settings = NotaFiscalSetting::first();

        if ($settings) {
            $settings->update($data);
        } else {
            NotaFiscalSetting::create($data);
        }

        return response()->json([
            'status'  => 'success',
            'message' => __('Configurações salvas com sucesso.'),
        ]);
    }

    // ── Listagem de emissões ───────────────────────────────────────────────────

    public function emissoes()
    {
        return View::make('NotaFiscal::pages.emissoes');
    }

    public function listarEmissoes(Request $request): JsonResponse
    {
        $query = NotaFiscalEmissao::with('order')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        $emissoes = $query->paginate(20);

        return response()->json($emissoes);
    }

    // ── Reprocessar / emitir manualmente ─────────────────────────────────────

    public function reprocessar(int $orderId, NfceService $service): JsonResponse
    {
        $order = Order::with(['products.product', 'payments'])->findOrFail($orderId);
        $emissao = $service->emitir($order);

        return response()->json([
            'status'  => $emissao->status,
            'message' => $emissao->mensagem,
            'emissao' => $emissao,
        ]);
    }

    // ── Download do XML autorizado ────────────────────────────────────────────

    public function downloadXml(int $emissaoId): Response
    {
        $emissao = NotaFiscalEmissao::findOrFail($emissaoId);

        if (empty($emissao->xml_retorno)) {
            abort(404, 'XML não disponível.');
        }

        $filename = ($emissao->chave ?? $emissao->id) . '-nfe.xml';

        return response($emissao->xml_retorno, 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Download do DANFCE (PDF) ──────────────────────────────────────────────

    public function downloadDanfce(int $emissaoId): Response
    {
        $emissao = NotaFiscalEmissao::findOrFail($emissaoId);

        if (! $emissao->danfce_path || ! Storage::disk('local')->exists($emissao->danfce_path)) {
            abort(404, 'DANFCE não disponível.');
        }

        $pdf      = Storage::disk('local')->get($emissao->danfce_path);
        $filename = ($emissao->chave ?? $emissao->id) . '-danfce.pdf';

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
