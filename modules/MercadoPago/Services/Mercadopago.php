<?php

namespace Modules\MercadoPago\Services;


class Mercadopago extends MercadoPagoGateway
{
    public function __construct()
    {
        \Log::info('Classe Mercadopago instanciada no PDV');
    }

}
