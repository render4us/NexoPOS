@extends('layout.dashboard')

@section('layout.dashboard.with-header')
    @include('common.dashboard.title')
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-semibold text-gray-700 dark:text-gray-200 mb-6">
            {{ __('Configurações do Mercado Pago') }}
        </h1>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('mercadopago.settings.save') }}" class="space-y-6 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            @csrf

            <div class="p-4">
                <label for="mercadopago_access_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Access Token') }}
                </label>
                <input
                        id="mercadopago_access_token"
                        name="mercadopago_access_token"
                        type="text"
                        value="{{ old('mercadopago_access_token', $token ?? '') }}"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md dark:bg-gray-900 dark:border-gray-700 dark:text-white"
                        required
                >
            </div>

            <div class="p-4">
                <label for="mercadopago_terminal_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Terminal ID') }}
                </label>
                <input
                        id="mercadopago_terminal_id"
                        name="mercadopago_terminal_id"
                        type="text"
                        value="{{ old('mercadopago_terminal_id', $terminal_id ?? '') }}"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md dark:bg-gray-900 dark:border-gray-700 dark:text-white"
                        required
                >
            </div>

            <div class="p-4">
                <button type="submit"
                        class="outline-none px-4 h-10 text-white bg-blue-500 rounded">
                    {{ __('Salvar Configurações') }}
                </button>
            </div>
        </form>
    </div>
@endsection
