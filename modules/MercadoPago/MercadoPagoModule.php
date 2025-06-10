<?php
namespace Modules\MercadoPago;

use App\Services\Module;

class MercadoPagoModule extends Module
{
    public function __construct()
    {
        parent::__construct(__FILE__);
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'dashboard');
    }

}
