<?php

namespace Modules\Autoatendimento\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Autoatendimento\Models\KioskSetting;

class KioskSettingsController extends Controller
{
    public function index()
    {
        $setting  = KioskSetting::instance();
        $usuarios = User::select('id', 'username')->orderBy('username')->get();

        return view('Autoatendimento::configuracoes', [
            'title'       => 'Autoatendimento',
            'description' => 'Configurações do kiosk de autoatendimento',
            'setting'     => $setting,
            'usuarios'    => $usuarios,
        ]);
    }

    public function salvar(Request $request)
    {
        $request->validate([
            'ativo'            => 'boolean',
            'titulo'           => 'required|string|max:100',
            'subtitulo'        => 'required|string|max:200',
            'cor_primaria'     => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_url'         => 'nullable|string|max:500',
            'logo_arquivo'     => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'video_url'        => 'nullable|string|max:500',
            'video_arquivo'    => 'nullable|file|mimes:mp4,webm|max:51200',
            'operator_user_id' => 'required|integer|exists:nexopos_users,id',
            'reset_timeout'    => 'required|integer|min:5|max:120',
            'printer_enabled'  => 'boolean',
            'printer_ip'       => 'nullable|ip',
            'printer_port'     => 'nullable|integer|min:1|max:65535',
            'printer_columns'  => 'nullable|integer|in:32,40,48',
        ]);

        $setting = KioskSetting::instance();

        // ── Logo ────────────────────────────────────────────────────────────
        if ($request->hasFile('logo_arquivo')) {
            // Remove arquivo anterior se era um upload local
            if ($setting->logo_url && str_starts_with($setting->logo_url, '/storage/kiosk/')) {
                Storage::disk('public')->delete('kiosk/' . basename($setting->logo_url));
            }
            $path = $request->file('logo_arquivo')->store('kiosk', 'public');
            $logoUrl = Storage::url($path);
        } else {
            $logoUrl = $request->input('logo_url') ?: $setting->logo_url;
        }

        // ── Vídeo ───────────────────────────────────────────────────────────
        if ($request->hasFile('video_arquivo')) {
            if ($setting->video_url && str_starts_with($setting->video_url, '/storage/kiosk/')) {
                Storage::disk('public')->delete('kiosk/' . basename($setting->video_url));
            }
            $path = $request->file('video_arquivo')->store('kiosk', 'public');
            $videoUrl = Storage::url($path);
        } else {
            $videoUrl = $request->input('video_url') ?: $setting->video_url;
        }

        $setting->update([
            'ativo'            => $request->boolean('ativo'),
            'titulo'           => $request->input('titulo'),
            'subtitulo'        => $request->input('subtitulo'),
            'cor_primaria'     => $request->input('cor_primaria'),
            'logo_url'         => $logoUrl,
            'video_url'        => $videoUrl,
            'operator_user_id' => $request->input('operator_user_id'),
            'reset_timeout'    => $request->input('reset_timeout'),
            'printer_enabled'  => $request->boolean('printer_enabled'),
            'printer_ip'       => $request->input('printer_ip'),
            'printer_port'     => $request->input('printer_port', 9100),
            'printer_columns'  => $request->input('printer_columns', 48),
        ]);

        return redirect()->route('autoatendimento.configuracoes')
            ->with('success', 'Configurações salvas com sucesso.');
    }
}
