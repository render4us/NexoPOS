<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoController extends Controller
{
    public function pay(Request $request)
    {
        $order = $request->input('order');
        $paymentType = $request->input('payment_type');

        Log::error('[api mercado pago] - MercadoPagoController@pay chamado', [
            'order' => $order,
            'payment_type' => $paymentType,
        ]);

        return response()->json([
            'status' => 'paid',
            'message' => '[api mercado pago]Pagamento aprovado via ' . strtoupper($paymentType),
        ], 200);
    }
}
