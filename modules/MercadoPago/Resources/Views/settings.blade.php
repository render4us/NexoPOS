@extends('mercadopago::layouts.master')

@section('content')
    <div class="container mx-auto px-6 py-8">
        <h1 class="text-2xl font-semibold text-gray-700 dark:text-gray-200 mb-6">
            {{ __('Configurações do Mercado Pago') }}
        </h1>

        <form method="POST" action="{{ route('mercadopago.settings.save') }}" class="space-y-6 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            @csrf

            <div>
                <label for="mercadopago_access_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Access Token') }}
                </label>
                <input
                        id="mercadopago_access_token"
                        name="mercadopago_access_token"
                        type="text"
                        value="{{ old('mercadopago_access_token', $token ?? '') }}"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-primary-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white"
                        required
                >
            </div>

            <div>
                <label for="mercadopago_terminal_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Terminal ID') }}
                </label>
                <input
                        id="mercadopago_terminal_id"
                        name="mercadopago_terminal_id"
                        type="text"
                        value="{{ old('mercadopago_terminal_id', $terminal_id ?? '') }}"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-primary-300 dark:bg-gray-900 dark:border-gray-700 dark:text-white"
                        required
                >
            </div>

            <div>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring focus:ring-primary-300">
                    {{ __('Salvar Configurações') }}
                </button>
            </div>
        </form>
    </div>
@endsection
