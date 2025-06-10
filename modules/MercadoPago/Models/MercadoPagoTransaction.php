<?php
namespace Modules\MercadoPago\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class MercadoPagoTransaction extends Model
{
    protected $table = 'mercadopago_transactions';

    protected $fillable = [
        'order_id',
        'transaction_id',
        'status',
        'payment_type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

