<?php

namespace Modules\Autoatendimento\Providers;

use App\Classes\Hook;
use App\Providers\AppServiceProvider;

class ServiceProvider extends AppServiceProvider
{
    public function register(): void
    {
        // Registra o tipo "eat_in" no sistema de tipos de pedido do NexoPOS
        Hook::addFilter('ns-orders-types', function (array $types) {
            $types['eat_in'] = [
                'identifier' => 'eat_in',
                'label'      => __('Comer no Local'),
                'icon'       => '/images/groceries.png',
                'selected'   => false,
            ];
            return $types;
        });

        // Adiciona item no menu do dashboard
        Hook::addFilter('ns-dashboard-menus', function (array $menus) {
            $menus = array_insert_before($menus, 'modules', [
                'autoatendimento' => [
                    'icon'      => 'la-tablet',
                    'label'     => __('Autoatendimento'),
                    'childrens' => [
                        [
                            'label' => __('Configurações'),
                            'href'  => ns()->url('dashboard/autoatendimento'),
                        ],
                        [
                            'label' => __('Abrir Kiosk'),
                            'href'  => ns()->url('kiosk'),
                            'target' => '_blank',
                        ],
                    ],
                ],
            ]);

            return $menus;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }
}
