<?php

namespace Modules\MercadoPago\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoPagoSetting extends Model
{
    protected $table = 'mercadopago_settings';

    protected $fillable = [
        'id',
        'access_token',
        'terminal_id',
    ];
}
