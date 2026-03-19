@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">

        {{-- Cabeçalho da página --}}
        <div class="page-inner-header mb-4 flex justify-between items-center">
            <div>
                <h3 class="text-3xl text-primary font-bold">{{ __('Autoatendimento (Kiosk)') }}</h3>
                <p class="text-secondary">{{ __('Configure a landing page de autoatendimento') }}</p>
            </div>
            <a href="{{ route('kiosk.index') }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium
                      border border-input-edge bg-input-button hover:bg-input-button-hover text-primary transition-colors">
                <i class="las la-external-link-alt text-base"></i>
                {{ __('Abrir Kiosk') }}
            </a>
        </div>

        {{-- Mensagem de sucesso --}}
        @if (session('success'))
            <div class="flex items-center gap-3 border border-success-secondary bg-success-primary rounded-lg mb-6 px-4 py-3">
                <i class="las la-check-circle text-2xl text-success-tertiary flex-shrink-0"></i>
                <p class="text-success-tertiary text-sm">{{ session('success') }}</p>
            </div>
        @endif

        <form method="POST"
              action="{{ route('autoatendimento.configuracoes.salvar') }}"
              enctype="multipart/form-data"
              class="space-y-6 max-w-3xl pb-10">
            @csrf

            {{-- ── Controle ─────────────────────────────────────────────── --}}
            <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
                <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                    <i class="las la-sliders-h text-secondary"></i>
                    <h2 class="text-sm font-semibold text-primary">{{ __('Controle') }}</h2>
                </div>
                <div class="ns-box-body p-4 space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="ativo" value="0">
                        <input type="checkbox" name="ativo" value="1"
                               {{ old('ativo', $setting->ativo) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-input-edge text-info-tertiary">
                        <span class="text-sm font-medium text-primary">{{ __('Kiosk ativo (acessível pelo público)') }}</span>
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-primary mb-1">{{ __('Usuário operador') }}</label>
                            <select name="operator_user_id"
                                    class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                                @foreach($usuarios as $u)
                                    <option value="{{ $u->id }}"
                                            {{ old('operator_user_id', $setting->operator_user_id) == $u->id ? 'selected' : '' }}>
                                        {{ $u->username }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-secondary mt-1">Os pedidos do kiosk serão criados com este usuário.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary mb-1">
                                {{ __('Tempo de reset (segundos)') }}
                            </label>
                            <input type="number" name="reset_timeout" min="5" max="120"
                                   value="{{ old('reset_timeout', $setting->reset_timeout) }}"
                                   class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                            <p class="text-xs text-secondary mt-1">Tempo após pagamento aprovado para voltar à tela inicial.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Aparência ────────────────────────────────────────────── --}}
            <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
                <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                    <i class="las la-palette text-secondary"></i>
                    <h2 class="text-sm font-semibold text-primary">{{ __('Aparência') }}</h2>
                </div>
                <div class="ns-box-body p-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-primary mb-1">{{ __('Título de boas-vindas') }}</label>
                            <input type="text" name="titulo"
                                   value="{{ old('titulo', $setting->titulo) }}"
                                   placeholder="Bem-vindo!"
                                   class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary mb-1">{{ __('Subtítulo') }}</label>
                            <input type="text" name="subtitulo"
                                   value="{{ old('subtitulo', $setting->subtitulo) }}"
                                   placeholder="Como prefere seu pedido?"
                                   class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary mb-1">{{ __('Cor primária') }}</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="cor_primaria"
                                       value="{{ old('cor_primaria', $setting->cor_primaria) }}"
                                       class="h-10 w-16 rounded-lg border border-input-edge cursor-pointer"
                                       data-sync-text="text_cor_primaria">
                                <input type="text" id="text_cor_primaria"
                                       value="{{ old('cor_primaria', $setting->cor_primaria) }}"
                                       class="flex-1 border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none"
                                       readonly>
                            </div>
                            <p class="text-xs text-secondary mt-1">Usada nos botões, destaques e cabeçalhos do kiosk.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary mb-1">{{ __('Cor da sidebar de categorias') }}</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="cor_sidebar"
                                       value="{{ old('cor_sidebar', $setting->cor_sidebar ?? '#111116') }}"
                                       class="h-10 w-16 rounded-lg border border-input-edge cursor-pointer"
                                       data-sync-text="text_cor_sidebar">
                                <input type="text" id="text_cor_sidebar"
                                       value="{{ old('cor_sidebar', $setting->cor_sidebar ?? '#111116') }}"
                                       class="flex-1 border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none"
                                       readonly>
                            </div>
                            <p class="text-xs text-secondary mt-1">Fundo da barra lateral de categorias no menu do kiosk.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        {{-- Logo --}}
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-primary">
                                {{ __('Logotipo') }}
                                <span class="text-xs text-secondary ml-1">(PNG, JPG, SVG, WebP — máx. 2 MB)</span>
                            </label>

                            {{-- Preview atual --}}
                            @if($setting->logo_url)
                            <div class="flex items-center gap-3 p-2 bg-box-elevation-background border border-box-edge rounded-lg">
                                <img src="{{ $setting->logo_url }}" alt="Logo atual"
                                     class="h-12 w-auto object-contain rounded">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-primary truncate">Logo atual</p>
                                    <p class="text-xs text-secondary truncate">{{ $setting->logo_url }}</p>
                                </div>
                            </div>
                            @endif

                            {{-- Upload --}}
                            <div>
                                <label class="block text-xs font-medium text-secondary mb-1">{{ __('Enviar novo arquivo') }}</label>
                                <input type="file" name="logo_arquivo" accept=".png,.jpg,.jpeg,.svg,.webp"
                                       class="block w-full text-sm text-secondary
                                              file:mr-3 file:py-2 file:px-4 file:rounded-lg
                                              file:border file:border-input-edge file:bg-input-button
                                              file:text-primary file:text-sm file:cursor-pointer
                                              hover:file:bg-input-button-hover">
                            </div>

                            {{-- URL manual --}}
                            <div>
                                <label class="block text-xs font-medium text-secondary mb-1">{{ __('Ou informe uma URL externa') }}</label>
                                <input type="text" name="logo_url"
                                       value="{{ old('logo_url') }}"
                                       placeholder="https://..."
                                       class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                                <p class="text-xs text-secondary mt-1">O upload tem prioridade sobre a URL.</p>
                            </div>
                        </div>

                        {{-- Vídeo de fundo --}}
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-primary">
                                {{ __('Vídeo de fundo (tela splash)') }}
                                <span class="text-xs text-secondary ml-1">(MP4, WebM — máx. 50 MB)</span>
                            </label>

                            {{-- Preview atual --}}
                            @if($setting->video_url)
                            <div class="flex items-center gap-3 p-2 bg-box-elevation-background border border-box-edge rounded-lg">
                                <div class="w-12 h-12 rounded bg-gray-200 flex items-center justify-center flex-shrink-0">
                                    <i class="las la-film text-2xl text-secondary"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-primary truncate">Vídeo atual</p>
                                    <p class="text-xs text-secondary truncate">{{ $setting->video_url }}</p>
                                </div>
                            </div>
                            @endif

                            {{-- Upload --}}
                            <div>
                                <label class="block text-xs font-medium text-secondary mb-1">{{ __('Enviar novo arquivo') }}</label>
                                <input type="file" name="video_arquivo" accept=".mp4,.webm"
                                       class="block w-full text-sm text-secondary
                                              file:mr-3 file:py-2 file:px-4 file:rounded-lg
                                              file:border file:border-input-edge file:bg-input-button
                                              file:text-primary file:text-sm file:cursor-pointer
                                              hover:file:bg-input-button-hover">
                            </div>

                            {{-- URL manual --}}
                            <div>
                                <label class="block text-xs font-medium text-secondary mb-1">{{ __('Ou informe uma URL externa') }}</label>
                                <input type="text" name="video_url"
                                       value="{{ old('video_url') }}"
                                       placeholder="https://..."
                                       class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                                <p class="text-xs text-secondary mt-1">Deixe ambos em branco para usar o gradiente da cor primária.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── Preview ──────────────────────────────────────────────── --}}
            <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
                <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                    <i class="las la-eye text-secondary"></i>
                    <h2 class="text-sm font-semibold text-primary">{{ __('Pré-visualização') }}</h2>
                </div>
                <div class="ns-box-body p-4">
                    <div class="rounded-xl overflow-hidden shadow-inner"
                         style="background: {{ $setting->cor_primaria }}; aspect-ratio: 9/5; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px;">
                        @if($setting->logo_url)
                            <img src="{{ $setting->logo_url }}" alt="Logo" class="h-12 object-contain">
                        @endif
                        <div class="text-center text-white">
                            <p class="font-black text-2xl">{{ $setting->titulo }}</p>
                            <p class="text-white/70">{{ $setting->subtitulo }}</p>
                        </div>
                        <div class="flex gap-3">
                            <div class="bg-white rounded-xl px-5 py-3 text-center">
                                <p class="font-bold text-sm" style="color: {{ $setting->cor_primaria }};">🍽️ Comer Aqui</p>
                            </div>
                            <div class="bg-white rounded-xl px-5 py-3 text-center">
                                <p class="font-bold text-sm" style="color: {{ $setting->cor_primaria }};">🥡 Para Levar</p>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-secondary mt-2 text-center">Salve as configurações para atualizar o preview.</p>
                </div>
            </div>

            {{-- ── Impressora Térmica ────────────────────────────────────── --}}
            <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
                <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                    <i class="las la-print text-secondary"></i>
                    <h2 class="text-sm font-semibold text-primary">{{ __('Impressora Térmica (Rede)') }}</h2>
                </div>
                <div class="ns-box-body p-4 space-y-4">

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="printer_enabled" value="0">
                        <input type="checkbox" name="printer_enabled" value="1"
                               {{ old('printer_enabled', $setting->printer_enabled) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-input-edge text-info-tertiary">
                        <span class="text-sm font-medium text-primary">{{ __('Imprimir cupom automaticamente após pagamento aprovado') }}</span>
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-primary mb-1">{{ __('IP da Impressora') }}</label>
                            <input type="text" name="printer_ip"
                                   value="{{ old('printer_ip', $setting->printer_ip) }}"
                                   placeholder="192.168.1.100"
                                   class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary font-mono">
                            <p class="text-xs text-secondary mt-1">Endereço IP da impressora na rede local.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary mb-1">{{ __('Porta TCP') }}</label>
                            <input type="number" name="printer_port" min="1" max="65535"
                                   value="{{ old('printer_port', $setting->printer_port ?: 9100) }}"
                                   class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary font-mono">
                            <p class="text-xs text-secondary mt-1">Padrão: 9100 (RAW printing).</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary mb-1">{{ __('Colunas do papel') }}</label>
                            <select name="printer_columns"
                                    class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                                @foreach([32 => '32 (57mm)', 40 => '40 (76mm)', 48 => '48 (80mm)'] as $val => $label)
                                    <option value="{{ $val }}"
                                            {{ old('printer_columns', $setting->printer_columns ?: 48) == $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-secondary mt-1">Largura do papel da sua impressora.</p>
                        </div>
                    </div>

                    {{-- Dica de compatibilidade --}}
                    <div class="flex items-start gap-2 text-xs text-secondary">
                        <i class="las la-info-circle mt-0.5 flex-shrink-0"></i>
                        <span>
                            Compatível com impressoras térmicas ESC/POS (Epson, Bematech, Elgin, Daruma, etc.)
                            conectadas à rede local. Certifique-se de que o servidor PHP consegue acessar o IP da impressora
                            na porta configurada.
                        </span>
                    </div>
                </div>
            </div>

            {{-- ── Teste de Pagamento ────────────────────────────────────── --}}
            <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
                <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                    <i class="las la-flask text-secondary"></i>
                    <h2 class="text-sm font-semibold text-primary">{{ __('Teste de Pagamento') }}</h2>
                    <span class="ml-auto text-xs px-2 py-0.5 rounded border border-error-secondary bg-error-primary text-error-tertiary">
                        Somente desenvolvimento
                    </span>
                </div>
                <div class="ns-box-body p-4 space-y-4">

                    {{-- Toggle para ativar/desativar --}}
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="teste_pagamento_ativo" value="0">
                        <input type="checkbox" name="teste_pagamento_ativo" value="1"
                               {{ old('teste_pagamento_ativo', $setting->teste_pagamento_ativo) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-input-edge text-info-tertiary">
                        <span class="text-sm font-medium text-primary">
                            {{ __('Habilitar simulação de pagamento aprovado') }}
                        </span>
                    </label>
                    <p class="text-xs text-secondary">
                        Quando ativo, libera o botão abaixo para simular um pagamento aprovado sem a maquininha.
                        <strong>Desative em produção.</strong>
                    </p>

                    {{-- Painel de simulação — sempre visível, botão desabilitado quando inativo --}}
                    <div class="border border-box-edge rounded-lg p-4 space-y-3 {{ $setting->teste_pagamento_ativo ? '' : 'opacity-50' }}">
                        <p class="text-xs text-secondary flex items-center gap-2">
                            <i class="las la-exclamation-triangle text-error-tertiary text-base"></i>
                            Simula o retorno de pagamento aprovado do Mercado Pago para um pedido kiosk pendente.
                            Registra o pagamento, imprime o cupom (se configurado) e dispara o WhatsApp.
                        </p>

                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-secondary mb-1">
                                    ID do Pedido
                                    <span class="opacity-60">(deixe vazio para usar o último pedido kiosk pendente)</span>
                                </label>
                                <input type="text" id="simular_order_id"
                                       placeholder="Ex: 42 — ou deixe vazio"
                                       {{ $setting->teste_pagamento_ativo ? '' : 'disabled' }}
                                       class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary font-mono">
                            </div>
                            <button type="button" id="btn_simular_pagamento"
                                    {{ $setting->teste_pagamento_ativo ? '' : 'disabled' }}
                                    class="inline-flex items-center gap-2 px-5 py-2 border border-input-edge bg-input-button
                                           hover:bg-input-button-hover text-primary rounded-lg font-semibold text-sm
                                           transition-colors whitespace-nowrap disabled:opacity-40 disabled:cursor-not-allowed">
                                <i class="las la-play-circle text-base"></i>
                                Simular Pagamento Aprovado
                            </button>
                        </div>

                        @if (! $setting->teste_pagamento_ativo)
                        <p class="text-xs text-secondary italic">
                            Habilite o toggle acima e salve as configurações para usar o simulador.
                        </p>
                        @endif

                        {{-- Resultado --}}
                        <div id="simular_resultado" class="hidden text-sm rounded-lg px-3 py-2 border"></div>
                    </div>

                </div>
            </div>

            {{-- ── Informações --}}
            <div class="flex items-start gap-3 border border-info-secondary bg-info-primary rounded-lg px-4 py-3">
                <i class="las la-info-circle text-xl text-info-tertiary flex-shrink-0 mt-0.5"></i>
                <div class="text-sm text-info-tertiary">
                    <p class="font-semibold mb-1">Pré-requisitos para o kiosk funcionar:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li>Módulo <strong>Mercado Pago</strong> configurado com Access Token e Terminal ID.</li>
                        <li>Produtos cadastrados no SnowSYS com status <strong>Disponível</strong> e preço de venda.</li>
                        <li>Para emissão automática de NF-e, o módulo <strong>Nota Fiscal</strong> deve estar ativo.</li>
                    </ul>
                </div>
            </div>

            {{-- ── Submit ────────────────────────────────────────────────── --}}
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

    </div>
</div>
@endsection

@section('layout.dashboard.header')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // Sync color pickers with their text display fields
        document.querySelectorAll('input[type="color"][data-sync-text]').forEach(function (picker) {
            const target = document.getElementById(picker.dataset.syncText);
            if (target) {
                picker.addEventListener('input', function () {
                    target.value = this.value;
                });
            }
        });

        // ── Simulação de pagamento ──────────────────────────────────────
        const btnSimular   = document.getElementById('btn_simular_pagamento');
        const inputOrderId = document.getElementById('simular_order_id');
        const resultado    = document.getElementById('simular_resultado');

        if (btnSimular) {
            btnSimular.addEventListener('click', async function () {
                btnSimular.disabled = true;
                btnSimular.innerHTML = '<i class="las la-spinner la-spin text-base"></i> Simulando...';
                resultado.className = 'hidden text-sm rounded-lg px-3 py-2 border';
                resultado.textContent = '';

                const body = {};
                const orderId = inputOrderId ? inputOrderId.value.trim() : '';
                if (orderId) body.order_id = parseInt(orderId);

                try {
                    const response = await fetch('/api/kiosk/simular-pagamento', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                         || '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(body),
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        resultado.className = 'text-sm rounded-lg px-3 py-2 border border-success-secondary bg-success-primary text-success-tertiary';
                        let html = '<strong>' + data.message + '</strong>';
                        if (data.detalhes && data.detalhes.length) {
                            html += '<ul class="list-disc list-inside mt-1 space-y-0.5">';
                            data.detalhes.forEach(d => { html += '<li>' + d + '</li>'; });
                            html += '</ul>';
                        }
                        resultado.innerHTML = html;
                    } else {
                        resultado.className = 'text-sm rounded-lg px-3 py-2 border border-error-secondary bg-error-primary text-error-tertiary';
                        resultado.textContent = '❌ ' + (data.message || 'Erro desconhecido.');
                    }
                } catch (err) {
                    resultado.className = 'text-sm rounded-lg px-3 py-2 border border-error-secondary bg-error-primary text-error-tertiary';
                    resultado.textContent = '❌ Erro de comunicação: ' + err.message;
                } finally {
                    btnSimular.disabled = false;
                    btnSimular.innerHTML = '<i class="las la-play-circle text-base"></i> Simular Pagamento Aprovado';
                }
            });
        }

    });
</script>
@endsection
