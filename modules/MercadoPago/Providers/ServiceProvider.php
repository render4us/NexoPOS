<?php

namespace Modules\MercadoPago\Providers;

use App\Classes\Hook;
Use App\Providers\AppServiceProvider;

class ServiceProvider extends AppServiceProvider
{
        public function register()
        {
            Hook::addFilter('ns-dashboard-menus', function ($menus) {
                $menus = array_insert_before($menus, 'modules', [
                    'my-menus' =>[
                        'icon'  => 'la-credit-card',
                        'label' => __('Mercado Pago'),
                        'childrens' => [
                            [
                                'label' => __('Configurações'),
                                'href'  => ns()->url('dashboard/mercadopago'),
                            ],
                            [
                                'label' => __('Transações'),
                                'href'  => ns()->url('dashboard/mercadopago/transactions'),
                            ],
                        ]
                    ]
                ]);
                return $menus;
            });
        }
}
