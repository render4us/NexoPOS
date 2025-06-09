<?php
namespace Modules\MercadoPago\Models;

use App\Models\NsModel;

class MercadoPagoTransaction extends NsModel
{
    protected $table = 'nexopos_mercadopago_transactions';

    protected $fillable = [
        'payment_id',
        'status',
        'payload',
    ];
}
