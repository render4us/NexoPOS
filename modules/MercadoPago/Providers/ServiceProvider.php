<?php

namespace Modules\MercadoPago\Providers;

use App\Classes\Hook;
Use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\View;

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
            Hook::addFilter('ns-payment-methods', function ($methods) {
                $methods['mercadopago'] = [
                    'label' => 'Mercado Pago',
                    'description' => 'Pagamento via integração Mercado Pago POS/Point',
                    'namespace' => \Modules\MercadoPago\Services\MercadoPagoGateway::class,
                    'icon' => 'la la-credit-card',
                ];

                return $methods;
            });
        }
}
