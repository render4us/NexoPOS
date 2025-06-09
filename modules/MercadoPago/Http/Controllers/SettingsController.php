<?php
namespace Modules\MercadoPago\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SettingsController extends Controller
{
    public function index()
    {
        return view('MercadoPago::settings', [
            'token'       => ns()->option->get('mercadopago_access_token'),
            'terminal_id' => ns()->option->get('mercadopago_terminal_id'),
        ]);
    }

    public function save(Request $request)
    {
        ns()->option->set('mercadopago_access_token', $request->input('mercadopago_access_token'));
        ns()->option->set('mercadopago_terminal_id', $request->input('mercadopago_terminal_id'));

        return redirect()->back()->with('success', __('Settings saved'));
    }
}
