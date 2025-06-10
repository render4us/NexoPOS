<?php

/**
 * Mercado Pago Controller
 * @since 1.0.0
 * @package modules/MercadoPago
**/

namespace Modules\MercadoPago\Http\Controllers;

use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use Modules\MercadoPago\Models\MercadoPagoSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\MercadoPago\Services\MercadoPagoGateway;



class MercadoPagoController extends Controller
{
    /**
     * Main Page
     * @since 1.0.0
    **/
    public function index()
    {
        return view( 'MercadoPago::index' );
    }


    public function config()
    {
        $setting = MercadoPagoSetting::first();

        return view('MercadoPago::config', [
            'title' => 'Mercado Pago',
             'description' => 'Configurações do mecanismo de pagamento',
            'token' => $setting->access_token ?? '',
            'terminal_id' => $setting->terminal_id ?? '',
            ]);
    }

    public function pay(Request $request)
    {
        Log::info('MercadoPagoController pay request', $request->all());

        $order = $request->input('order');

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order data missing',
            ], 422);
        }

        $gateway = new MercadoPagoGateway();
        $result = $gateway->process((object) $order);

        Log::info('MercadoPagoController pay result', ['result' => $result]);

        return $result;
    }

}
