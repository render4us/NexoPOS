<?php

namespace Modules\EvolutionWhatsApp\Providers;

use App\Classes\Hook;
use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\EvolutionWhatsApp\Listeners\OrderCreatedListener;
use Modules\EvolutionWhatsApp\Listeners\OrderPaidListener;

class ServiceProvider extends AppServiceProvider
{
    public function register(): void
    {
        Hook::addFilter('ns-dashboard-menus', function (array $menus) {
            $menus = array_insert_before($menus, 'modules', [
                'evolution-whatsapp' => [
                    'icon'      => 'la-whatsapp',
                    'label'     => __('WhatsApp'),
                    'childrens' => [
                        [
                            'label' => __('Configurações'),
                            'href'  => ns()->url('dashboard/evolution-whatsapp'),
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

        Event::listen(
            OrderAfterPaymentStatusChangedEvent::class,
            OrderPaidListener::class
        );

        Event::listen(
            OrderAfterCreatedEvent::class,
            OrderCreatedListener::class
        );
    }
}
