<?php
namespace Modules\MercadoPago\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\PaymentMethodInterface;
use Modules\MercadoPago\Payments\MercadoPagoPayment;

class PaymentMethodServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PaymentMethodInterface::class, MercadoPagoPayment::class);
    }
}
