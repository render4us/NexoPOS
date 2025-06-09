<?php
namespace Modules\MercadoPago\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PaymentType;
use Illuminate\Support\Str;

class MercadoPagoServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/mercadopago.php', 'mercadopago');
    }

    public function boot()
    {
        if (!PaymentType::where('identifier', 'mercadopago')->exists()) {
            $payment = new PaymentType();
            $payment->label = 'Mercado Pago';
            $payment->identifier = 'mercadopago';
            $payment->author = 1;
            $payment->active = true;
            $payment->readonly = false;
            $payment->save();
        }
    }
}
