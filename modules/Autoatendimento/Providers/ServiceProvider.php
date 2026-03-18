<?php

namespace Modules\Autoatendimento\Providers;

use App\Classes\Hook;
use App\Providers\AppServiceProvider;

class ServiceProvider extends AppServiceProvider
{
    public function register(): void
    {
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
