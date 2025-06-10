<?php

namespace Modules\MercadoPago\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\MercadoPago\Models\MercadoPagoTransaction;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = MercadoPagoTransaction::with('order')
            ->latest()
            ->paginate(15);

        return view('MercadoPago::transactions.index',  [
            'title' => 'Transações Mercado Pago',
            'description' => 'Acompanhe todas as vendas pelo mercado pago.',
            'transactions' => $transactions,
        ]);
    }

    public function payload($id)
    {
        $txn = MercadoPagoTransaction::findOrFail($id);

        return response()->view('MercadoPago::transactions.payload', [
            'transaction' => $txn,
        ]);
    }

    public function payloadJson($id)
    {
        $txn = MercadoPagoTransaction::findOrFail($id);
        return response()->json($txn->payload);
    }
}
