<?php

namespace Modules\MercadoPago\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\MercadoPago\Models\MercadoPagoSetting;

class SettingsController extends Controller
{
    public function save(Request $request)
    {
        $request->validate([
            'mercadopago_access_token' => 'required|string|max:255',
            'mercadopago_terminal_id' => 'required|string|max:255',
        ]);

        // Atualiza o primeiro (ou cria)
        MercadoPagoSetting::updateOrCreate(
            ['id' => 1], // Apenas um registro
            [
                'access_token' => $request->mercadopago_access_token,
                'terminal_id' => $request->mercadopago_terminal_id,
            ]
        );

        return redirect()->route('mercadopago.settings')->with('success', 'Configurações salvas com sucesso!');
    }
}
