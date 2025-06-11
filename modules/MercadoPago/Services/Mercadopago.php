<?php

namespace Modules\MercadoPago\Services;


class Mercadopago extends MercadoPagoGateway
{

    public function __construct()
    {
        \Log::info('Classe Mercadopago instanciada no PDV');
    }

    public function name()
    {
        return 'mercadopago';
    }

    public function label()
    {
        return 'Mercado Pago';
    }

    public function identifier()
    {
        return 'mercadopago';
    }

    // Caminho até o .vue no módulo
    public function renderComponent()
    {
        return 'Modules/MercadoPago/Resources/ts/payments/mercadopago-payment.vue';
    }

}
