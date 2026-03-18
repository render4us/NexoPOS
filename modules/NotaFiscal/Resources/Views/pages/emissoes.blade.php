@extends('NotaFiscal::layouts.master')

@section('title', __('Emissões NFC-e'))

@section('content')
<div x-data="emissoes()">

    {{-- Cabeçalho da página --}}
    <div class="page-inner-header mb-4 flex justify-between items-center">
        <div>
            <h3 class="text-3xl text-primary font-bold">{{ __('Emissões NFC-e') }}</h3>
            <p class="text-secondary">{{ __('Histórico de notas fiscais eletrônicas emitidas') }}</p>
        </div>
        <a href="{{ route('nota-fiscal.configuracoes') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium
                  border border-input-edge bg-input-button hover:bg-input-button-hover text-primary transition-colors">
            <i class="las la-cog text-base"></i>
            {{ __('Configurações') }}
        </a>
    </div>

    {{-- Filtros --}}
    <div class="ns-box rounded-lg border border-box-edge p-4 mb-4 flex gap-3 flex-wrap items-end">
        <div>
            <label class="block text-xs font-medium text-secondary mb-1">{{ __('Status') }}</label>
            <select x-model="filtros.status" @change="buscar()"
                    class="border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
                <option value="">{{ __('Todos') }}</option>
                <option value="pendente">{{ __('Pendente') }}</option>
                <option value="autorizada">{{ __('Autorizada') }}</option>
                <option value="rejeitada">{{ __('Rejeitada') }}</option>
                <option value="erro">{{ __('Erro') }}</option>
                <option value="cancelada">{{ __('Cancelada') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-secondary mb-1">{{ __('Nº do Pedido') }}</label>
            <input type="number" x-model="filtros.order_id" @input.debounce.500ms="buscar()"
                   placeholder="{{ __('ex: 1042') }}"
                   class="border border-input-edge bg-box-background text-primary rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-info-secondary">
        </div>
        <button @click="buscar()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-info-tertiary text-white rounded-lg text-sm font-medium hover:opacity-90 transition-opacity">
            <i class="las la-search"></i>
            {{ __('Buscar') }}
        </button>
    </div>

    {{-- Tabela --}}
    <div class="ns-box rounded-lg border border-box-edge overflow-x-auto mb-4">
        <table class="w-full">
            <thead>
            <tr class="bg-table-th border-b border-table-th-edge">
                <th class="px-4 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">{{ __('Pedido') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">{{ __('Nº Nota / Série') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">{{ __('Status') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">{{ __('Chave de Acesso') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">{{ __('Ambiente') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">{{ __('Data') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-primary uppercase tracking-wider">{{ __('Ações') }}</th>
            </tr>
            </thead>
            <tbody>
            <template x-if="loading">
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-secondary">
                        <i class="las la-circle-notch la-spin text-2xl block mb-2"></i>
                        {{ __('Carregando...') }}
                    </td>
                </tr>
            </template>
            <template x-if="!loading && emissoes.length === 0">
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-secondary">
                        <i class="las la-file-invoice text-4xl block mb-2"></i>
                        {{ __('Nenhuma emissão encontrada.') }}
                    </td>
                </tr>
            </template>
            <template x-for="e in emissoes" :key="e.id">
                <tr class="border-b border-box-edge hover:bg-box-elevation-background transition-colors">
                    <td class="px-4 py-3 text-sm text-primary font-medium" x-text="'#' + e.order_id"></td>
                    <td class="px-4 py-3 text-sm text-primary" x-text="e.n_nf + ' / ' + e.serie"></td>
                    <td class="px-4 py-3">
                        <span :class="{
                            'bg-success-primary text-success-tertiary': e.status === 'autorizada',
                            'bg-error-primary text-error-tertiary':     e.status === 'rejeitada' || e.status === 'erro',
                            'bg-warning-primary text-warning-tertiary': e.status === 'pendente',
                            'bg-input-background text-secondary':       e.status === 'cancelada',
                        }" class="px-2 py-1 text-xs font-semibold rounded-full capitalize" x-text="e.status"></span>
                    </td>
                    <td class="px-4 py-3 text-xs text-secondary font-mono" x-text="e.chave || '—'"></td>
                    <td class="px-4 py-3">
                        <span :class="{
                            'bg-info-primary text-info-tertiary':       e.ambiente == 1,
                            'bg-warning-primary text-warning-tertiary': e.ambiente != 1,
                        }" class="px-2 py-1 text-xs font-semibold rounded-full"
                            x-text="e.ambiente == 1 ? 'Produção' : 'Homologação'"></span>
                    </td>
                    <td class="px-4 py-3 text-xs text-secondary" x-text="e.created_at"></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 flex-wrap">
                            <button @click="reprocessar(e.order_id)"
                                    class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium
                                           bg-warning-primary text-warning-tertiary rounded-lg
                                           hover:bg-warning-secondary hover:text-white transition-colors">
                                <i class="las la-redo-alt"></i>
                                {{ __('Reprocessar') }}
                            </button>
                            <a :href="'/dashboard/nota-fiscal/emissoes/' + e.id + '/xml'"
                               x-show="e.xml_retorno"
                               class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium
                                      bg-info-primary text-info-tertiary rounded-lg
                                      hover:bg-info-secondary hover:text-white transition-colors">
                                <i class="las la-file-code"></i>
                                XML
                            </a>
                            <a :href="'/dashboard/nota-fiscal/emissoes/' + e.id + '/danfce'"
                               x-show="e.danfce_path"
                               class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium
                                      bg-success-primary text-success-tertiary rounded-lg
                                      hover:bg-success-secondary hover:text-white transition-colors">
                                <i class="las la-file-pdf"></i>
                                DANFCE
                            </a>
                        </div>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    <div class="flex justify-between items-center text-sm text-secondary" x-show="meta">
        <span x-text="'Total: ' + (meta ? meta.total : 0) + ' emissões'"></span>
        <div class="flex items-center gap-2">
            <button @click="pagina--; buscar()" :disabled="pagina <= 1"
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg border border-input-edge
                           bg-input-button hover:bg-input-button-hover text-primary text-sm
                           disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                <i class="las la-angle-left"></i>
                {{ __('Anterior') }}
            </button>
            <span class="text-primary font-medium px-2"
                  x-text="'Página ' + pagina + ' de ' + (meta ? meta.last_page : 1)"></span>
            <button @click="pagina++; buscar()" :disabled="!meta || pagina >= meta.last_page"
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg border border-input-edge
                           bg-input-button hover:bg-input-button-hover text-primary text-sm
                           disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                {{ __('Próxima') }}
                <i class="las la-angle-right"></i>
            </button>
        </div>
    </div>

</div>

<script>
function emissoes() {
    return {
        emissoes: [],
        meta: null,
        loading: false,
        pagina: 1,
        filtros: { status: '', order_id: '' },

        init() { this.buscar(); },

        buscar() {
            this.loading = true;
            const params = new URLSearchParams({
                page: this.pagina,
                ...Object.fromEntries(Object.entries(this.filtros).filter(([, v]) => v !== ''))
            });
            fetch(`/dashboard/nota-fiscal/emissoes/json?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                this.emissoes = data.data;
                this.meta = { total: data.total, last_page: data.last_page };
            })
            .finally(() => { this.loading = false; });
        },

        reprocessar(orderId) {
            if (!confirm('{{ __("Confirma reprocessar a NFC-e deste pedido?") }}')) return;
            fetch(`/dashboard/nota-fiscal/reprocessar/${orderId}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                }
            })
            .then(r => r.json())
            .then(data => { alert(data.message || data.status); this.buscar(); })
            .catch(() => alert('{{ __("Erro ao reprocessar.") }}'));
        }
    }
}
</script>
@endsection
