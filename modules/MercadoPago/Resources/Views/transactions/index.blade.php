@extends('layout.dashboard')

@section('layout.dashboard.with-header')
    @include('common.dashboard.title')

    <div class="container mx-auto px-6 py-8">
        <h3 class="text-xl text-gray-700 dark:text-gray-200 mb-6">
            {{ __('Últimos pedidos') }}
        </h3>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-x-auto">
            <table class="min-w-full w-full table-auto">
                <thead>
                <tr class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-left">
                    <th class="px-4 py-2">Pedido</th>
                    <th class="px-4 py-2">Transação</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Tipo</th>
                    <th class="px-4 py-2">Data</th>
                    <th class="px-4 py-2">Ações</th>

                </tr>
                </thead>
                <tbody class="text-gray-700 dark:text-gray-200">
                @forelse($transactions as $txn)
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="px-4 py-2">#{{ $txn->order_id }}</td>
                        <td class="px-4 py-2">{{ $txn->transaction_id }}</td>
                        <td class="px-4 py-2">{{ ucfirst($txn->status) }}</td>
                        <td class="px-4 py-2">{{ strtoupper($txn->payment_type) }}</td>
                        <td class="px-4 py-2">{{ $txn->created_at?->format('d/m/Y H:i') ?? '-' }}</td>

                        <td class="px-4 py-2 space-x-2">
                            <a href="{{ route('mercadopago.transactions.payload', $txn->id) }}"
                               class="inline-flex items-center px-4 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-700">
                                Dados
                            </a>

                            @if($txn->order)
                                <a href="{{ ns()->url('dashboard/orders/receipt/' . $txn->order->id) }}"
                                   target="_blank"
                                   class="inline-flex items-center mx-2 px-3 py-1 text-sm bg-gray-700 text-white rounded hover:bg-gray-800">
                                    Pedido
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                            Nenhuma transação encontrada.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </div>
@endsection
