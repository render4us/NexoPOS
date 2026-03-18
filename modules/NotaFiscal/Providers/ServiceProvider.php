<?php

namespace Modules\NotaFiscal\Providers;

use App\Classes\Hook;
use App\Events\OrderAfterCreatedEvent;
use App\Providers\AppServiceProvider;
use Modules\NotaFiscal\Listeners\EmitirNfceListener;
use Modules\NotaFiscal\Services\NfceService;

class ServiceProvider extends AppServiceProvider
{
    public function register(): void
    {
        // ── Bind do serviço ────────────────────────────────────────────────────
        $this->app->singleton(NfceService::class, fn () => new NfceService());

        // ── Menu no painel ─────────────────────────────────────────────────────
        Hook::addFilter('ns-dashboard-menus', function (array $menus) {
            $menus = array_insert_before($menus, 'modules', [
                'nota-fiscal' => [
                    'icon'      => 'la-file-invoice',
                    'label'     => __('Nota Fiscal'),
                    'childrens' => [
                        [
                            'label' => __('Configurações'),
                            'href'  => ns()->url('dashboard/nota-fiscal/configuracoes'),
                        ],
                        [
                            'label' => __('Emissões'),
                            'href'  => ns()->url('dashboard/nota-fiscal/emissoes'),
                        ],
                    ],
                ],
            ]);

            return $menus;
        });
    }

    public function boot(): void
    {
        // ── Migrations ────────────────────────────────────────────────────────
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');

        // ── Listener de pedidos ───────────────────────────────────────────────
        $this->app['events']->listen(
            OrderAfterCreatedEvent::class,
            EmitirNfceListener::class
        );
    }
}
