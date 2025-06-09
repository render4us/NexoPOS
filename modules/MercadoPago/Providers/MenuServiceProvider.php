<?php
namespace Modules\MercadoPago\Providers;

use Illuminate\Support\ServiceProvider;
use TorMorten\Eventy\Facades\Events as Hook;

class MenuServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Hook::addFilter('ns-dashboard-menus', function ($menus) {
            $menus['mercadopago'] = [
                'label' => __('Mercado Pago'),
                'icon'  => 'la-credit-card',
                'href'  => ns()->route('mercadopago.settings'),
            ];
            return $menus;
        });
    }
}
