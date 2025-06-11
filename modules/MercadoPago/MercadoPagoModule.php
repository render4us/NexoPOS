<?php
namespace Modules\MercadoPago;

use App\Services\Module;
use Illuminate\Support\Facades\Event;


class MercadoPagoModule extends Module
{
    public function __construct()
    {
        parent::__construct(__FILE__);
    }

}
