<?php
namespace Modules\MercadoPago\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SettingsController extends Controller
{
    public function index()
    {
        return view('MercadoPago::settings', [
            'token'     => settings()->get('mercadopago_access_token'),
            'device_id' => settings()->get('mercadopago_device_id'),
        ]);
    }

    public function save(Request $request)
    {
        settings()->set('mercadopago_access_token', $request->input('mercadopago_access_token'));
        settings()->set('mercadopago_device_id', $request->input('mercadopago_device_id'));

        return redirect()->back()->with('success', __('Settings saved'));
    }
}
