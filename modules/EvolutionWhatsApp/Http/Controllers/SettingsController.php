<?php

namespace Modules\EvolutionWhatsApp\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\EvolutionWhatsApp\Models\EvolutionWhatsAppSetting;
use Modules\EvolutionWhatsApp\Services\EvolutionApiService;

class SettingsController extends Controller
{
    public function index()
    {
        $setting = EvolutionWhatsAppSetting::instance();

        return view('EvolutionWhatsApp::configuracoes', [
            'title'       => 'Evolution WhatsApp',
            'description' => 'Configurações do envio de mensagens WhatsApp',
            'setting'     => $setting,
        ]);
    }

    public function salvar(Request $request)
    {
        $request->validate([
            'ativo'                     => 'boolean',
            'disparar_ao_criar'         => 'boolean',
            'api_url'                   => 'nullable|url|max:500',
            'api_key'                   => 'nullable|string|max:500',
            'instance_name'             => 'nullable|string|max:200',
            'mensagem_template'         => 'required|string|max:2000',
            'mensagem_template_criacao' => 'nullable|string|max:2000',
        ]);

        EvolutionWhatsAppSetting::instance()->update([
            'ativo'                     => $request->boolean('ativo'),
            'disparar_ao_criar'         => $request->boolean('disparar_ao_criar'),
            'api_url'                   => $request->input('api_url') ?? '',
            'api_key'                   => $request->input('api_key') ?? '',
            'instance_name'             => $request->input('instance_name') ?? '',
            'mensagem_template'         => $request->input('mensagem_template'),
            'mensagem_template_criacao' => $request->input('mensagem_template_criacao') ?? '',
        ]);

        return redirect()->route('evolution-whatsapp.configuracoes')
            ->with('success', 'Configurações salvas com sucesso.');
    }

    /**
     * Envia uma mensagem de teste para verificar a conexão com a Evolution API.
     */
    public function testar(Request $request)
    {
        $request->validate([
            'telefone_teste' => 'required|string|max:30',
        ]);

        $setting = EvolutionWhatsAppSetting::instance();

        if (! $setting->api_url || ! $setting->api_key || ! $setting->instance_name) {
            return back()->withErrors(['telefone_teste' => 'Configure a URL, API Key e nome da instância antes de testar.']);
        }

        $digits = preg_replace('/\D/', '', $request->input('telefone_teste'));
        if (strlen($digits) <= 11) {
            $digits = '55' . $digits;
        }

        $ok = app(EvolutionApiService::class)->enviar(
            $setting,
            $digits,
            '✅ Teste de conexão SnowSYS → Evolution API. Se você recebeu esta mensagem, está tudo funcionando!'
        );

        if ($ok) {
            return back()->with('success', 'Mensagem de teste enviada com sucesso para ' . $digits . '!');
        }

        return back()->withErrors(['telefone_teste' => 'Falha ao enviar. Verifique os logs e as configurações da API.']);
    }
}
