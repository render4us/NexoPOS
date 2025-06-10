@extends('layout.dashboard')

@section('layout.dashboard.with-header')
    <div class="container mx-auto px-6 py-8">
        <h1 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-6">
            Payload da Transação #{{ $transaction->transaction_id }}
        </h1>

        <pre class="bg-gray-900 text-white p-4 rounded shadow overflow-auto text-sm whitespace-pre-wrap">
        {{ json_encode($transaction->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
    </pre>

        <div class="mt-4">
            <a href="{{ url()->previous() }}" class="inline-block px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                Voltar
            </a>
        </div>
    </div>
@endsection
