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

            Hook::addFilter('ns-registers-allowed-payment-type', function ($methods) {
                $methods['mercadopago'] = \Modules\MercadoPago\Services\Mercadopago::class;
                return $methods;
            });

            Hook::addFilter('ns-pos-payment-gateways', function ($gateways) {
                $gateways[] = [
                    'label' => 'Mercado Pago',
                    'identifier' => 'mercadopago',
                ];
                return $gateways;
            });
        }

        public function boot(){
            $this->loadMigrationsFrom(__DIR__.'/../Migrations');

        }
}
