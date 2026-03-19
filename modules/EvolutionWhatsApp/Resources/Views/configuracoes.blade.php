@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">

        {{-- Cabeçalho da página --}}
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-primary font-bold">{{ __('Evolution WhatsApp') }}</h3>
            <p class="text-secondary">{{ __('Envie mensagens automáticas de confirmação de pedido via WhatsApp') }}</p>
        </div>

        {{-- Mensagem de sucesso --}}
        @if (session('success'))
            <div class="flex items-center gap-3 border border-success-secondary bg-success-primary rounded-lg mb-6 px-4 py-3">
                <i class="las la-check-circle text-2xl text-success-tertiary flex-shrink-0"></i>
                <p class="text-success-tertiary text-sm">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Erros de validação --}}
        @if ($errors->any())
            <div class="flex items-start gap-3 border border-error-secondary bg-error-primary rounded-lg mb-6 px-4 py-3">
                <i class="las la-exclamation-circle text-xl text-error-tertiary flex-shrink-0 mt-0.5"></i>
                <ul class="text-sm text-error-tertiary list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-6 max-w-3xl pb-10">

            {{-- ── Formulário principal ──────────────────────────────────── --}}
            <form method="POST" action="{{ route('evolution-whatsapp.configuracoes.salvar') }}">
                @csrf

                {{-- ── Controle ─────────────────────────────────────────── --}}
                <div class="ns-box rounded-lg border border-box-edge overflow-hidden mb-6">
                    <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                        <i class="las la-toggle-on text-secondary"></i>
                        <h2 class="text-sm font-semibold text-primary">{{ __('Controle') }}</h2>
                    </div>
                    <div class="ns-box-body p-4 space-y-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="ativo" value="0">
                            <input type="checkbox" name="ativo" value="1"
                                   {{ old('ativo', $setting->ativo) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded border-input-edge text-info-tertiary">
                            <span class="text-sm font-medium text-primary">{{ __('Ativo — habilitar envio automático de mensagens WhatsApp') }}</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="disparar_ao_criar" value="0">
                            <input type="checkbox" name="disparar_ao_criar" value="1"
                                   {{ old('disparar_ao_criar', $setting->disparar_ao_criar) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded border-input-edge text-info-tertiary">
                            <span class="text-sm font-medium text-primary">
                                {{ __('Enviar mensagem ao criar o pedido no Kiosk') }}
                                <span class="text-xs text-secondary font-normal ml-1">(antes do pagamento — confirmação de recebimento)</span>
                            </span>
                        </label>
                        <p class="text-xs text-secondary ml-7">
                            A mensagem de confirmação de pagamento sempre é enviada após o pagamento aprovado (se o módulo estiver ativo).
                        </p>
                    </div>
                </div>

                {{-- ── Conexão com a Evolution API ──────────────────────── --}}
                <div class="ns-box rounded-lg border border-box-edge overflow-hidden mb-6">
                    <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                        <i class="las la-plug text-secondary"></i>
                        <h2 class="text-sm font-semibold text-primary">{{ __('Conexão — Evolution API') }}</h2>
                    </div>
                    <div class="ns-box-body p-4 space-y-4">

                        <div>
                            <label class="block text-sm font-medium text-primary mb-1">{{ __('URL da API') }}</label>
                            <input type="url" name="api_url"
                                   value="{{ old('api_url', $setting->api_url) }}"
                                   placeholder="https://evolution.seudominio.com.br"
                                   class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary font-mono">
                            <p class="text-xs text-secondary mt-1">URL base da sua instância da Evolution API (sem barra no final).</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-primary mb-1">{{ __('API Key (Global Key)') }}</label>
                                <input type="text" name="api_key"
                                       value="{{ old('api_key', $setting->api_key) }}"
                                       placeholder="sua-api-key-aqui"
                                       autocomplete="off"
                                       class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary font-mono">
                                <p class="text-xs text-secondary mt-1">Chave de autenticação da Evolution API.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-primary mb-1">{{ __('Nome da Instância') }}</label>
                                <input type="text" name="instance_name"
                                       value="{{ old('instance_name', $setting->instance_name) }}"
                                       placeholder="minha-instancia"
                                       class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary font-mono">
                                <p class="text-xs text-secondary mt-1">Nome da instância criada na Evolution API.</p>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ── Variáveis disponíveis (referência) ───────────────── --}}
                <div class="flex items-start gap-2 text-xs text-secondary border border-box-edge rounded-lg px-4 py-3 mb-6">
                    <i class="las la-info-circle mt-0.5 flex-shrink-0 text-base"></i>
                    <div>
                        <p class="font-semibold mb-2 text-primary">Variáveis disponíveis nos templates:</p>
                        <div class="grid grid-cols-2 gap-x-8 gap-y-1">
                            <span><code class="bg-box-elevation-background px-1 rounded">{pedido_id}</code> — número do pedido</span>
                            <span><code class="bg-box-elevation-background px-1 rounded">{total}</code> — valor total (ex: 38,00)</span>
                            <span><code class="bg-box-elevation-background px-1 rounded">{itens}</code> — lista de produtos com qtd e valor</span>
                            <span><code class="bg-box-elevation-background px-1 rounded">{loja}</code> — nome da loja</span>
                            <span><code class="bg-box-elevation-background px-1 rounded">{modo}</code> — Mesa ou Viagem</span>
                            <span><code class="bg-box-elevation-background px-1 rounded">{data}</code> — data e hora do pedido</span>
                            <span><code class="bg-box-elevation-background px-1 rounded">{forma_pagamento}</code> — forma de pagamento <span class="opacity-60">(pós-pagamento)</span></span>
                        </div>
                        <p class="mt-2">Use <code class="bg-box-elevation-background px-1 rounded">*texto*</code> para <strong>negrito</strong> e <code class="bg-box-elevation-background px-1 rounded">_texto_</code> para <em>itálico</em> no WhatsApp.</p>
                    </div>
                </div>

                {{-- ── Template: Pedido Criado (Kiosk) ──────────────────── --}}
                <div class="ns-box rounded-lg border border-box-edge overflow-hidden mb-6">
                    <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                        <i class="las la-shopping-cart text-secondary"></i>
                        <h2 class="text-sm font-semibold text-primary">{{ __('Mensagem — Pedido Recebido') }}</h2>
                        <span class="ml-auto text-xs text-secondary bg-box-elevation-background px-2 py-0.5 rounded">Ao criar o pedido no Kiosk</span>
                    </div>
                    <div class="ns-box-body p-4 space-y-2">
                        <label class="block text-sm font-medium text-primary mb-1">{{ __('Template da mensagem de recebimento de pedido') }}</label>
                        <textarea name="mensagem_template_criacao" rows="8"
                                  placeholder="Deixe em branco para usar o template de confirmação de pagamento."
                                  class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary font-mono">{{ old('mensagem_template_criacao', $setting->mensagem_template_criacao) }}</textarea>
                        <p class="text-xs text-secondary">Enviada imediatamente após o pedido ser registrado no Kiosk, antes do pagamento. Requer "Enviar mensagem ao criar" habilitado acima.</p>
                    </div>
                </div>

                {{-- ── Template: Pagamento Confirmado ───────────────────── --}}
                <div class="ns-box rounded-lg border border-box-edge overflow-hidden mb-6">
                    <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                        <i class="las la-check-circle text-secondary"></i>
                        <h2 class="text-sm font-semibold text-primary">{{ __('Mensagem — Pagamento Confirmado') }}</h2>
                        <span class="ml-auto text-xs text-secondary bg-box-elevation-background px-2 py-0.5 rounded">Após aprovação do pagamento</span>
                    </div>
                    <div class="ns-box-body p-4 space-y-2">
                        <label class="block text-sm font-medium text-primary mb-1">{{ __('Template do cupom / confirmação de pagamento') }}</label>
                        <textarea name="mensagem_template" rows="10"
                                  class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary font-mono">{{ old('mensagem_template', $setting->mensagem_template) }}</textarea>
                        <p class="text-xs text-secondary">Enviada automaticamente quando o pagamento é aprovado. Contém o cupom digital com todos os dados do pedido.</p>
                    </div>
                </div>

                {{-- ── Submit ───────────────────────────────────────────── --}}
                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-info-tertiary text-white rounded-lg
                                   font-semibold text-sm hover:opacity-90 focus:outline-none focus:ring-2
                                   focus:ring-info-secondary transition-opacity">
                        <i class="las la-save text-base"></i>
                        {{ __('Salvar Configurações') }}
                    </button>
                </div>

            </form>

            {{-- ── Envio de teste ───────────────────────────────────────── --}}
            <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
                <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                    <i class="las la-paper-plane text-secondary"></i>
                    <h2 class="text-sm font-semibold text-primary">{{ __('Testar Conexão') }}</h2>
                </div>
                <div class="ns-box-body p-4">
                    <form method="POST" action="{{ route('evolution-whatsapp.testar') }}" class="flex items-end gap-3">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-primary mb-1">{{ __('Número de telefone para teste') }}</label>
                            <input type="text" name="telefone_teste"
                                   value="{{ old('telefone_teste') }}"
                                   placeholder="(11) 99999-9999"
                                   class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                            <p class="text-xs text-secondary mt-1">O código do Brasil (55) é adicionado automaticamente se não informado.</p>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2 border border-input-edge bg-input-button
                                       hover:bg-input-button-hover text-primary rounded-lg font-medium text-sm transition-colors whitespace-nowrap">
                            <i class="las la-paper-plane text-base"></i>
                            {{ __('Enviar Teste') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- ── Como funciona ─────────────────────────────────────────── --}}
            <div class="flex items-start gap-3 border border-info-secondary bg-info-primary rounded-lg px-4 py-3">
                <i class="las la-info-circle text-xl text-info-tertiary flex-shrink-0 mt-0.5"></i>
                <div class="text-sm text-info-tertiary space-y-1">
                    <p class="font-semibold">Como funciona:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li>O cliente informa o telefone no Kiosk — ele é salvo na nota do pedido.</li>
                        <li><strong>Ao criar o pedido</strong> (opcional): mensagem de "pedido recebido" enviada antes do pagamento.</li>
                        <li><strong>Após pagamento aprovado</strong>: cupom digital com número do pedido, itens e total.</li>
                        <li>A instância da Evolution API precisa estar conectada ao WhatsApp para funcionar.</li>
                        <li>Os logs ficam em <code class="bg-info-secondary px-1 rounded">storage/logs/laravel.log</code>.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
