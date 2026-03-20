@extends('NotaFiscal::layouts.master')

@section('title', __('Configurações NFC-e'))

@section('content')
<div class="max-w-4xl">

    {{-- Cabeçalho da página --}}
    <div class="page-inner-header mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-3xl text-primary font-bold">{{ __('Configurações NFC-e') }}</h3>
            <p class="text-secondary">{{ __('Parâmetros do emissor de Nota Fiscal do Consumidor Eletrônica') }}</p>
        </div>
        <a href="{{ route('nota-fiscal.emissoes') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium
                  border border-input-edge bg-input-button hover:bg-input-button-hover text-primary transition-colors">
            <i class="fal fa-list text-base"></i>
            {{ __('Ver Emissões') }}
        </a>
    </div>

    {{-- Mensagem de sucesso --}}
    @if (session('success'))
        <div class="flex items-center gap-3 border border-success-secondary bg-success-primary rounded-lg mb-6 px-4 py-3">
            <i class="fal fa-circle-check text-2xl text-success-tertiary flex-shrink-0"></i>
            <p class="text-success-tertiary text-sm">{{ session('success') }}</p>
        </div>
    @endif

    <form method="POST"
          action="{{ route('nota-fiscal.configuracoes.salvar') }}"
          enctype="multipart/form-data"
          class="nf-config-form">
        @csrf

        {{-- ── Controle geral ─────────────────────────────────────────── --}}
        <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
            <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                <i class="fal fa-sliders text-secondary"></i>
                <h2 class="text-sm font-semibold text-primary">{{ __('Controle') }}</h2>
            </div>
            <div class="ns-box-body p-4 space-y-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="ativo" value="0">
                    <input type="checkbox" id="ativo" name="ativo" value="1"
                           {{ old('ativo', $settings->ativo) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-input-edge text-info-tertiary cursor-pointer">
                    <span class="text-sm font-medium text-primary">{{ __('Módulo NFC-e ativo') }}</span>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="emitir_automaticamente" value="0">
                    <input type="checkbox" id="emitir_automaticamente" name="emitir_automaticamente" value="1"
                           {{ old('emitir_automaticamente', $settings->emitir_automaticamente) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-input-edge text-info-tertiary cursor-pointer">
                    <span class="text-sm font-medium text-primary">{{ __('Emitir NFC-e automaticamente ao criar pedido pago') }}</span>
                </label>

                <div>
                    <label class="block text-sm font-medium text-primary mb-1">{{ __('Ambiente') }}</label>
                    <select name="ambiente"
                            class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                        <option value="2" {{ old('ambiente', $settings->ambiente) == 2 ? 'selected' : '' }}>{{ __('Homologação (Testes)') }}</option>
                        <option value="1" {{ old('ambiente', $settings->ambiente) == 1 ? 'selected' : '' }}>{{ __('Produção') }}</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- ── Dados da empresa ─────────────────────────────────────────── --}}
        <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
            <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                <i class="fal fa-building text-secondary"></i>
                <h2 class="text-sm font-semibold text-primary">{{ __('Dados da Empresa Emissora') }}</h2>
            </div>
            <div class="ns-box-body p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ([
                        ['cnpj',         __('CNPJ'), __('14 dígitos sem máscara'), 'text'],
                        ['razao_social', __('Razão Social'), '', 'text'],
                        ['nome_fantasia',__('Nome Fantasia'), '', 'text'],
                        ['ie',           __('Inscrição Estadual'), '', 'text'],
                        ['cnae',         __('CNAE Principal'), __('7 dígitos'), 'text'],
                    ] as [$field, $label, $hint, $type])
                    <div>
                        <label for="{{ $field }}" class="block text-sm font-medium text-primary mb-1">
                            {{ $label }}
                            @if($hint)<span class="text-xs text-secondary ml-1">({{ $hint }})</span>@endif
                        </label>
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}"
                               value="{{ old($field, $settings->$field ?? '') }}"
                               class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                        @error($field)<p class="text-error-tertiary text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    @endforeach

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-primary mb-1">{{ __('CRT — Regime Tributário') }}</label>
                        <select name="crt"
                                class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                            <option value="1" {{ old('crt', $settings->crt) == '1' ? 'selected' : '' }}>1 — Simples Nacional</option>
                            <option value="2" {{ old('crt', $settings->crt) == '2' ? 'selected' : '' }}>2 — Simples Nacional — Excesso de Sublimite</option>
                            <option value="3" {{ old('crt', $settings->crt) == '3' ? 'selected' : '' }}>3 — Regime Normal (Lucro Presumido / Real)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Endereço ─────────────────────────────────────────────────── --}}
        <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
            <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                <i class="fal fa-location-dot text-secondary"></i>
                <h2 class="text-sm font-semibold text-primary">{{ __('Endereço') }}</h2>
            </div>
            <div class="ns-box-body p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ([
                        ['uf',           __('UF'), __('ex: SP')],
                        ['cod_municipio',__('Código IBGE'), __('Município')],
                        ['municipio',    __('Município'), ''],
                        ['cep',          __('CEP'), __('8 dígitos')],
                        ['logradouro',   __('Logradouro'), ''],
                        ['numero',       __('Número'), ''],
                        ['complemento',  __('Complemento'), ''],
                        ['bairro',       __('Bairro'), ''],
                        ['telefone',     __('Telefone'), __('DDD + número')],
                    ] as [$field, $label, $hint])
                    <div>
                        <label for="{{ $field }}" class="block text-sm font-medium text-primary mb-1">
                            {{ $label }}
                            @if($hint)<span class="text-xs text-secondary ml-1">({{ $hint }})</span>@endif
                        </label>
                        <input id="{{ $field }}" name="{{ $field }}" type="text"
                               value="{{ old($field, $settings->$field ?? '') }}"
                               class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Certificado A1 ────────────────────────────────────────────── --}}
        <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
            <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                <i class="fal fa-certificate text-secondary"></i>
                <h2 class="text-sm font-semibold text-primary">{{ __('Certificado Digital A1 (.pfx)') }}</h2>
            </div>
            <div class="ns-box-body p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-primary mb-1">{{ __('Arquivo .pfx') }}</label>
                    <input type="file" name="certificado_arquivo" accept=".pfx"
                           class="block w-full text-sm text-secondary file:mr-3 file:py-2 file:px-4
                                  file:rounded-lg file:border file:border-input-edge
                                  file:bg-input-button file:text-primary file:text-sm
                                  hover:file:bg-input-button-hover file:cursor-pointer">
                    @if ($settings->certificado_conteudo)
                        <p class="flex items-center gap-1 text-xs text-success-tertiary mt-2">
                            <i class="fal fa-circle-check"></i>
                            {{ __('Certificado já configurado. Envie um novo arquivo apenas para substituí-lo.') }}
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-primary mb-1">{{ __('Senha do Certificado') }}</label>
                    <input type="password" name="certificado_senha"
                           value="{{ old('certificado_senha', $settings->certificado_senha ?? '') }}"
                           class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                </div>
            </div>
        </div>

        {{-- ── CSC / QR Code ────────────────────────────────────────────── --}}
        <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
            <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                <i class="fal fa-qrcode text-secondary"></i>
                <h2 class="text-sm font-semibold text-primary">{{ __('CSC — Código de Segurança do Contribuinte') }}</h2>
            </div>
            <div class="ns-box-body p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">{{ __('CSC') }}</label>
                        <input type="text" name="csc" value="{{ old('csc', $settings->csc ?? '') }}"
                               class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">
                            {{ __('ID do CSC') }}
                            <span class="text-xs text-secondary ml-1">(ex: 000001)</span>
                        </label>
                        <input type="text" name="csc_id" value="{{ old('csc_id', $settings->csc_id ?? '') }}"
                               class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Numeração ─────────────────────────────────────────────────── --}}
        <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
            <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                <i class="fal fa-hashtag text-secondary"></i>
                <h2 class="text-sm font-semibold text-primary">{{ __('Série e Numeração') }}</h2>
            </div>
            <div class="ns-box-body p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">{{ __('Série') }}</label>
                        <input type="number" name="serie" value="{{ old('serie', $settings->serie ?? 1) }}" min="1"
                               class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">{{ __('Próximo Número (nNF)') }}</label>
                        <input type="number" name="proximo_numero" value="{{ old('proximo_numero', $settings->proximo_numero ?? 1) }}" min="1"
                               class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Defaults fiscais ─────────────────────────────────────────── --}}
        <div class="ns-box rounded-lg border border-box-edge overflow-hidden">
            <div class="ns-box-header px-4 py-3 border-b border-box-edge flex items-center gap-2">
                <i class="fal fa-percent text-secondary"></i>
                <h2 class="text-sm font-semibold text-primary">{{ __('Defaults Fiscais') }}</h2>
                <span class="text-xs text-secondary">({{ __('para produtos sem configuração própria') }})</span>
            </div>
            <div class="ns-box-body p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">
                            {{ __('NCM Padrão') }}
                            <span class="text-xs text-secondary ml-1">(8 dígitos)</span>
                        </label>
                        <input type="text" name="ncm_padrao" maxlength="8"
                               value="{{ old('ncm_padrao', $settings->ncm_padrao ?? '') }}"
                               placeholder="ex: 21050000"
                               class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">{{ __('CFOP Padrão') }}</label>
                        <input type="text" name="cfop_padrao" maxlength="4"
                               value="{{ old('cfop_padrao', $settings->cfop_padrao ?? '5102') }}"
                               placeholder="5102"
                               class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                        <p class="text-xs text-secondary mt-1">5102 = Venda a consumidor final</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">
                            {{ __('CSOSN Padrão') }}
                            <span class="text-xs text-secondary ml-1">(Simples Nacional)</span>
                        </label>
                        <input type="text" name="csosn_padrao" maxlength="3"
                               value="{{ old('csosn_padrao', $settings->csosn_padrao ?? '400') }}"
                               placeholder="400"
                               class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                        <p class="text-xs text-secondary mt-1">400 = Sem ST &nbsp;|&nbsp; 500 = ST cobrado anteriormente</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">
                            {{ __('CST-ICMS Padrão') }}
                            <span class="text-xs text-secondary ml-1">(Regime Normal)</span>
                        </label>
                        <input type="text" name="cst_icms_padrao" maxlength="3"
                               value="{{ old('cst_icms_padrao', $settings->cst_icms_padrao ?? '') }}"
                               placeholder="00"
                               class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary mb-1">
                            {{ __('Alíquota ICMS Padrão (%)') }}
                            <span class="text-xs text-secondary ml-1">(Regime Normal)</span>
                        </label>
                        <input type="number" name="aliquota_icms_padrao" step="0.01" min="0" max="100"
                               value="{{ old('aliquota_icms_padrao', $settings->aliquota_icms_padrao ?? 0) }}"
                               class="block w-full border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Submit ───────────────────────────────────────────────────── --}}
        <div class="flex justify-end pb-6">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-info-tertiary text-white rounded-lg
                           font-semibold text-sm hover:opacity-90 focus:outline-none focus:ring-2
                           focus:ring-info-secondary transition-opacity">
                <i class="fal fa-floppy-disk text-base"></i>
                {{ __('Salvar Configurações') }}
            </button>
        </div>

    </form>
</div>
@endsection

@section('layout.dashboard.header')
<style>
    .nf-config-form > .ns-box + .ns-box,
    .nf-config-form > .ns-box + div,
    .nf-config-form > div + .ns-box,
    .nf-config-form > div + div {
        margin-top: 2rem;
    }
</style>
@endsection
