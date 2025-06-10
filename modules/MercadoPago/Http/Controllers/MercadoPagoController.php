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

}
