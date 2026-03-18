@extends('Autoatendimento::layouts.kiosk')

@section('content')
<div x-data="kiosk()" x-cloak class="h-screen overflow-hidden">

    {{-- ══════════════════════════════════════════════════════
         STEP: SPLASH
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'splash'" class="relative h-screen w-full overflow-hidden">

        {{-- Fundo: vídeo (se configurado) ou gradiente --}}
        @if($setting->video_url)
        <video autoplay muted loop playsinline
               class="absolute inset-0 w-full h-full object-cover">
            <source src="{{ $setting->video_url }}" type="video/mp4">
        </video>
        @else
        <div class="absolute inset-0" style="background: linear-gradient(135deg, {{ $setting->cor_primaria }} 0%, #2d1f18 100%);"></div>
        @endif

        {{-- Overlay escuro --}}
        <div class="absolute inset-0 bg-black/50"></div>

        {{-- Overlay de transição (fade marrom) --}}
        <template x-if="transitioning">
            <div class="absolute inset-0 z-50" style="background-color: {{ $setting->cor_primaria }};"></div>
        </template>

        {{-- Conteúdo --}}
        <div class="relative z-10 h-full flex flex-col items-center justify-center gap-8 px-6">

            {{-- Logo --}}
            @if($setting->logo_url)
            <img src="{{ $setting->logo_url }}" alt="Logo" class="h-40 drop-shadow-2xl object-contain">
            @endif

            {{-- Texto de boas-vindas --}}
            <div class="text-center">
                <h1 class="text-white text-5xl font-black tracking-tight drop-shadow">
                    {{ $setting->titulo }}
                </h1>
                <p class="text-white/70 text-2xl mt-2">{{ $setting->subtitulo }}</p>
            </div>

            {{-- Botões de modo --}}
            <div class="flex gap-6">
                {{-- Comer no local --}}
                <button @click="selectMode('eat_in')"
                        class="bg-white rounded-3xl p-8 flex flex-col items-center gap-4 w-52 shadow-2xl active:scale-95 transition-transform duration-150">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-5xl"
                         style="background-color: {{ $setting->cor_primaria }}1a;">
                        🍽️
                    </div>
                    <div class="text-center">
                        <p class="font-black text-xl" style="color: {{ $setting->cor_primaria }};">Comer Aqui</p>
                        <p class="text-gray-400 text-sm mt-0.5">Mesa no local</p>
                    </div>
                </button>

                {{-- Para levar --}}
                <button @click="selectMode('takeaway')"
                        class="bg-white rounded-3xl p-8 flex flex-col items-center gap-4 w-52 shadow-2xl active:scale-95 transition-transform duration-150">
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-5xl"
                         style="background-color: {{ $setting->cor_primaria }}1a;">
                        🥡
                    </div>
                    <div class="text-center">
                        <p class="font-black text-xl" style="color: {{ $setting->cor_primaria }};">Para Levar</p>
                        <p class="text-gray-400 text-sm mt-0.5">Embalagem viagem</p>
                    </div>
                </button>
            </div>

            <p class="text-white/40 text-sm animate-pulse">Toque para começar</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP: MENU
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'menu'" class="flex flex-col h-screen bg-gray-50">

        {{-- Top bar --}}
        <header class="text-white flex-shrink-0 shadow-lg z-40"
                style="background-color: {{ $setting->cor_primaria }};">
            <div class="flex items-center gap-4 px-5 py-3">
                @if($setting->logo_url)
                <img src="{{ $setting->logo_url }}" alt="" class="h-10 object-contain">
                @endif
                <div class="flex-1">
                    <h1 class="font-bold text-lg leading-none">{{ $setting->titulo }}</h1>
                    <p class="text-white/60 text-xs mt-0.5">
                        <span x-show="mode === 'eat_in'">🍽️ Comer no local</span>
                        <span x-show="mode === 'takeaway'">🥡 Para levar</span>
                    </p>
                </div>
                <button @click="step = 'splash'; resetQuantidades()"
                        class="border border-white/25 rounded-xl px-4 py-2 text-sm text-white/80 active:bg-white/10 transition-colors">
                    ✕ Cancelar
                </button>
            </div>
        </header>

        {{-- Abas de categorias --}}
        <nav class="bg-white flex-shrink-0 border-b border-gray-100">
            <div class="flex gap-2 px-4 py-3 overflow-x-auto no-scrollbar">
                <button @click="selectedCategory = null"
                        :class="selectedCategory === null
                            ? 'text-white'
                            : 'bg-gray-100 text-gray-600'"
                        :style="selectedCategory === null ? 'background-color: {{ $setting->cor_primaria }};' : ''"
                        class="flex-shrink-0 px-5 py-2.5 rounded-full text-sm font-semibold transition-colors">
                    Todos
                </button>
                <template x-for="cat in categories" :key="cat.id">
                    <button @click="selectedCategory = cat.id"
                            :class="selectedCategory === cat.id
                                ? 'text-white'
                                : 'bg-gray-100 text-gray-600'"
                            :style="selectedCategory === cat.id ? 'background-color: {{ $setting->cor_primaria }};' : ''"
                            class="flex-shrink-0 px-5 py-2.5 rounded-full text-sm font-semibold transition-colors"
                            x-text="cat.name">
                    </button>
                </template>
            </div>
        </nav>

        {{-- Grid de produtos --}}
        <main class="flex-1 overflow-y-auto p-4 pb-32 no-scrollbar">
            {{-- Loading --}}
            <div x-show="loading" class="flex flex-col items-center justify-center h-48 gap-3">
                <div class="w-10 h-10 border-4 border-gray-200 border-t-gray-500 rounded-full animate-spin"></div>
                <p class="text-gray-400 text-sm">Carregando cardápio...</p>
            </div>

            {{-- Sem produtos --}}
            <div x-show="!loading && filteredProducts.length === 0"
                 class="flex flex-col items-center justify-center h-48 text-gray-400">
                <span class="text-4xl mb-2">🍽️</span>
                <p>Nenhum produto disponível.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <template x-for="prod in filteredProducts" :key="prod.id">
                    <div @click="openProductModal(prod)"
                         class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col border border-gray-100 cursor-pointer active:scale-[0.97] transition-transform duration-150">
                        {{-- Imagem + badge de quantidade --}}
                        <div class="relative">
                            <img :src="prod.image || '/images/placeholder.png'"
                                 :alt="prod.name"
                                 class="w-full h-44 object-cover bg-gray-100"
                                 x-on:error="$event.target.src='/images/placeholder.png'">
                            <template x-if="quantities[prod.id] > 0">
                                <div class="absolute top-2 right-2 text-white text-xs font-bold w-7 h-7 rounded-full flex items-center justify-center shadow-md"
                                     style="background-color: {{ $setting->cor_primaria }};"
                                     x-text="quantities[prod.id]"></div>
                            </template>
                        </div>

                        {{-- Informações --}}
                        <div class="p-4 flex flex-col flex-1 gap-2">
                            <h3 class="text-sm font-semibold text-gray-800 leading-tight line-clamp-2 flex-1"
                                x-text="prod.name"></h3>
                            <p class="font-bold text-xl" style="color: {{ $setting->cor_primaria }};"
                               x-text="'R$ ' + prod.price.toFixed(2).replace('.', ',')"></p>

                            {{-- Botão de adicionar / indicador de quantidade --}}
                            <div class="flex mt-1" @click.stop="openProductModal(prod)">
                                <template x-if="quantities[prod.id] > 0">
                                    <div class="flex-1 flex items-center justify-between rounded-2xl px-4 py-2.5 font-bold text-sm"
                                         :style="'background-color: {{ $setting->cor_primaria }}1a; color: {{ $setting->cor_primaria }};'">
                                        <span x-text="quantities[prod.id] + ' no pedido'"></span>
                                        <span class="text-base leading-none">✎</span>
                                    </div>
                                </template>
                                <template x-if="!quantities[prod.id]">
                                    <div class="flex-1 flex items-center justify-center gap-2 rounded-2xl py-2.5 font-bold text-sm text-white"
                                         style="background-color: {{ $setting->cor_primaria }};">
                                        <span class="text-xl leading-none font-black">+</span>
                                        <span>Adicionar</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </main>

        {{-- Barra de carrinho fixa --}}
        <footer class="fixed bottom-0 left-0 w-full bg-white z-40 shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
            <div class="flex items-center gap-4 px-5 py-4">
                <div class="flex-1">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Seu pedido</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-black text-gray-900"
                              x-text="'R$ ' + totalPrice.toFixed(2).replace('.', ',')"></span>
                        <span class="text-sm text-gray-400"
                              x-text="'· ' + totalItems + (totalItems === 1 ? ' item' : ' itens')"></span>
                    </div>
                </div>
                <button @click="openCart()"
                        :disabled="totalItems === 0"
                        class="px-8 py-4 rounded-2xl font-bold text-lg transition-all duration-150"
                        :style="totalItems > 0
                            ? 'background-color: {{ $setting->cor_primaria }}; color: white;'
                            : 'background-color: #f3f4f6; color: #9ca3af; cursor: not-allowed;'">
                    Ver Pedido
                </button>
            </div>
        </footer>

        {{-- ── Modal: Detalhe do Produto ──────────────────────────────── --}}
        <div x-show="showProductModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center"
             style="display: none;">

            {{-- Scrim --}}
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closeProductModal()"></div>

            {{-- Painel --}}
            <div x-show="showProductModal"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 translate-y-8"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-8"
                 class="relative w-full sm:max-w-md bg-white rounded-t-3xl sm:rounded-3xl overflow-hidden shadow-2xl max-h-[92vh] flex flex-col">

                <template x-if="modalProduct">
                    <div class="flex flex-col overflow-hidden">

                        {{-- Imagem --}}
                        <div class="relative flex-shrink-0">
                            <img :src="modalProduct.image || '/images/placeholder.png'"
                                 :alt="modalProduct.name"
                                 class="w-full h-64 object-cover bg-gray-100"
                                 x-on:error="$event.target.src='/images/placeholder.png'">
                            {{-- Botão fechar sobre a imagem --}}
                            <button @click="closeProductModal()"
                                    class="absolute top-3 right-3 w-10 h-10 rounded-full bg-black/40 text-white flex items-center justify-center text-lg backdrop-blur-sm active:bg-black/60">
                                ✕
                            </button>
                            {{-- Badge de quantidade no carrinho --}}
                            <template x-if="quantities[modalProduct.id] > 0">
                                <div class="absolute top-3 left-3 text-white text-sm font-bold px-3 py-1 rounded-full shadow"
                                     style="background-color: {{ $setting->cor_primaria }};"
                                     x-text="quantities[modalProduct.id] + ' no pedido'"></div>
                            </template>
                        </div>

                        {{-- Informações --}}
                        <div class="flex-1 overflow-y-auto px-6 pt-5 pb-4 no-scrollbar">
                            <h2 class="text-2xl font-black text-gray-900 leading-tight"
                                x-text="modalProduct.name"></h2>
                            <p class="text-3xl font-black mt-1"
                               style="color: {{ $setting->cor_primaria }};"
                               x-text="'R$ ' + modalProduct.price.toFixed(2).replace('.', ',')"></p>
                            <template x-if="modalProduct.description">
                                <p class="text-gray-500 text-sm mt-3 leading-relaxed"
                                   x-text="modalProduct.description"></p>
                            </template>
                        </div>

                        {{-- Controles + Botão --}}
                        <div class="flex-shrink-0 px-6 pb-8 pt-4 border-t border-gray-100 space-y-4">

                            {{-- Seletor de quantidade --}}
                            <div class="flex items-center justify-center gap-6">
                                <button @click="modalQty = Math.max(1, modalQty - 1)"
                                        class="w-14 h-14 rounded-full flex items-center justify-center text-3xl font-bold transition-all active:scale-90"
                                        :style="modalQty > 1
                                            ? 'background-color: {{ $setting->cor_primaria }}; color: white;'
                                            : 'background-color: #f3f4f6; color: #d1d5db;'">
                                    −
                                </button>
                                <span class="text-4xl font-black text-gray-900 w-12 text-center"
                                      x-text="modalQty"></span>
                                <button @click="modalQty++"
                                        class="w-14 h-14 rounded-full text-white flex items-center justify-center text-3xl font-bold active:scale-90 transition-transform"
                                        style="background-color: {{ $setting->cor_primaria }};">
                                    +
                                </button>
                            </div>

                            {{-- Subtotal da seleção --}}
                            <p class="text-center text-sm text-gray-400">
                                Subtotal:
                                <span class="font-bold" style="color: {{ $setting->cor_primaria }};"
                                      x-text="'R$ ' + (modalQty * modalProduct.price).toFixed(2).replace('.', ',')"></span>
                            </p>

                            {{-- Botões de ação --}}
                            <div class="grid gap-3" :class="quantities[modalProduct.id] > 0 ? 'grid-cols-2' : 'grid-cols-1'">
                                {{-- Remover do carrinho (só aparece se já tem) --}}
                                <template x-if="quantities[modalProduct.id] > 0">
                                    <button @click="removeFromModal()"
                                            class="py-4 rounded-2xl font-bold text-sm border-2 border-gray-200 text-gray-600 active:bg-gray-50 transition-colors">
                                        🗑 Remover
                                    </button>
                                </template>

                                {{-- Adicionar --}}
                                <button @click="addFromModal()"
                                        class="py-4 rounded-2xl font-black text-white text-base shadow-lg active:scale-95 transition-transform"
                                        style="background-color: {{ $setting->cor_primaria }};"
                                        x-text="quantities[modalProduct.id] > 0 ? 'Atualizar pedido' : 'Adicionar ao pedido'">
                                </button>
                            </div>

                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Bottom Sheet: carrinho --}}
        <div x-show="showCart" class="fixed inset-0 z-50 flex items-end">
            {{-- Scrim --}}
            <div class="absolute inset-0 bg-black/60" @click="closeCart()"></div>

            {{-- Painel --}}
            <div class="relative w-full bg-white rounded-t-3xl max-h-[85vh] flex flex-col shadow-2xl">
                {{-- Handle --}}
                <div class="flex justify-center pt-3 pb-1 flex-shrink-0">
                    <div class="w-12 h-1.5 bg-gray-200 rounded-full"></div>
                </div>

                {{-- Cabeçalho --}}
                <div class="flex items-center justify-between px-6 py-3 flex-shrink-0">
                    <h2 class="text-2xl font-black text-gray-900">Meu Pedido</h2>
                    <button @click="closeCart()"
                            class="w-9 h-9 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 text-xl">
                        ✕
                    </button>
                </div>

                {{-- Itens --}}
                <div class="flex-1 overflow-y-auto px-6 no-scrollbar">
                    <p x-show="cartItems.length === 0"
                       class="text-center text-gray-400 text-lg py-12">
                        Nenhum item adicionado
                    </p>
                    <template x-for="item in cartItems" :key="item.id">
                        <div class="flex items-center gap-3 py-4 border-b border-gray-50 last:border-0">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 leading-snug" x-text="item.name"></p>
                                <p class="text-sm text-gray-400 mt-0.5"
                                   x-text="item.quantity + '× R$ ' + item.price.toFixed(2).replace('.', ',')"></p>
                            </div>
                            <p class="font-bold text-lg flex-shrink-0"
                               style="color: {{ $setting->cor_primaria }};"
                               x-text="'R$ ' + item.total.toFixed(2).replace('.', ',')"></p>
                        </div>
                    </template>
                </div>

                {{-- Rodapé do carrinho --}}
                <div class="flex-shrink-0 px-6 pt-4 pb-6 border-t border-gray-100">
                    <div class="flex justify-between items-baseline mb-5">
                        <span class="text-gray-500 font-medium">Total do pedido</span>
                        <span class="text-3xl font-black text-gray-900"
                              x-text="'R$ ' + totalPrice.toFixed(2).replace('.', ',')"></span>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closeCart()"
                                class="flex-1 py-4 rounded-2xl font-bold text-base border-2 active:opacity-80"
                                :style="'border-color: {{ $setting->cor_primaria }}; color: {{ $setting->cor_primaria }};'">
                            + Adicionar
                        </button>
                        <button @click="irParaCheckout()"
                                class="flex-1 py-4 rounded-2xl font-bold text-base text-white shadow-md active:scale-95 transition-transform"
                                style="background-color: {{ $setting->cor_primaria }};">
                            Finalizar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP: CHECKOUT
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'checkout'" class="flex flex-col h-screen bg-gray-50">

        {{-- Header --}}
        <header class="text-white flex-shrink-0 shadow-md z-10"
                style="background-color: {{ $setting->cor_primaria }};">
            <div class="flex items-center px-5 py-4">
                <button @click="step = 'menu'; showCart = true"
                        class="mr-4 w-10 h-10 rounded-full bg-white/20 flex items-center justify-center active:bg-white/30">
                    ←
                </button>
                <h1 class="font-bold text-xl flex-1 text-center">Finalizar Pedido</h1>
                <div class="w-10"></div>
            </div>
        </header>

        {{-- Conteúdo --}}
        <div class="flex-1 overflow-y-auto p-4 pb-4 space-y-4 no-scrollbar">

            {{-- Resumo dos itens --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-50">
                    <h2 class="font-bold text-gray-800">📋 Resumo</h2>
                </div>
                <div class="px-4">
                    <template x-for="item in cartItems" :key="item.id">
                        <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                            <div>
                                <p class="font-medium text-gray-800 text-sm" x-text="item.name"></p>
                                <p class="text-xs text-gray-400"
                                   x-text="item.quantity + '× R$ ' + item.price.toFixed(2).replace('.', ',')"></p>
                            </div>
                            <p class="font-bold text-sm"
                               style="color: {{ $setting->cor_primaria }};"
                               x-text="'R$ ' + item.total.toFixed(2).replace('.', ',')"></p>
                        </div>
                    </template>
                </div>
                <div class="px-4 py-3 flex justify-between items-center" style="background-color: {{ $setting->cor_primaria }}1a;">
                    <span class="font-bold text-gray-700">Total</span>
                    <span class="text-2xl font-black" style="color: {{ $setting->cor_primaria }};"
                          x-text="'R$ ' + totalPrice.toFixed(2).replace('.', ',')"></span>
                </div>
            </div>

            {{-- WhatsApp --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <label class="block font-bold text-gray-800 mb-2">
                    📱 WhatsApp <span class="text-gray-400 text-sm font-normal">(opcional — para acompanhar o pedido)</span>
                </label>
                <input type="tel"
                       x-model="phone"
                       placeholder="(99) 99999-9999"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-lg focus:outline-none focus:ring-2"
                       :style="'focus-ring-color: {{ $setting->cor_primaria }};'"
                       inputmode="tel">
            </div>

            {{-- Nota Fiscal --}}
            <label class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center gap-4 cursor-pointer active:bg-gray-50">
                <input type="checkbox" x-model="nfe"
                       class="w-6 h-6 rounded-md cursor-pointer"
                       :style="'accent-color: {{ $setting->cor_primaria }};'">
                <div>
                    <p class="font-bold text-gray-800">🧾 Solicitar Nota Fiscal (NF-e)</p>
                    <p class="text-sm text-gray-400">Será emitida automaticamente após o pagamento</p>
                </div>
            </label>

            {{-- Forma de pagamento --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <h2 class="font-bold text-gray-800 mb-3">💳 Forma de pagamento</h2>
                <div class="grid grid-cols-2 gap-3">
                    <button @click="paymentType = 'credit_card'"
                            class="py-4 rounded-xl font-bold text-sm border-2 transition-all"
                            :style="paymentType === 'credit_card'
                                ? 'border-color: {{ $setting->cor_primaria }}; background-color: {{ $setting->cor_primaria }}1a; color: {{ $setting->cor_primaria }};'
                                : 'border-color: #e5e7eb; color: #6b7280;'">
                        💳 Crédito
                    </button>
                    <button @click="paymentType = 'debit_card'"
                            class="py-4 rounded-xl font-bold text-sm border-2 transition-all"
                            :style="paymentType === 'debit_card'
                                ? 'border-color: {{ $setting->cor_primaria }}; background-color: {{ $setting->cor_primaria }}1a; color: {{ $setting->cor_primaria }};'
                                : 'border-color: #e5e7eb; color: #6b7280;'">
                        💳 Débito
                    </button>
                </div>
            </div>
        </div>

        {{-- Botão confirmar --}}
        <div class="flex-shrink-0 p-4 bg-white border-t border-gray-100">
            <button @click="confirmarPedido()"
                    class="w-full py-5 rounded-2xl font-black text-xl text-white shadow-lg active:scale-95 transition-transform"
                    style="background-color: {{ $setting->cor_primaria }};">
                Confirmar e Pagar
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP: PAGANDO
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'pagando'"
         class="h-screen flex flex-col items-center justify-center gap-8 text-white px-8"
         style="background-color: {{ $setting->cor_primaria }};">

        {{-- Animação --}}
        <div class="relative">
            <div class="w-36 h-36 rounded-full border-4 border-white/20 flex items-center justify-center">
                <div class="w-36 h-36 rounded-full border-4 border-t-white border-r-transparent border-b-transparent border-l-transparent absolute animate-spin-slow"></div>
                <span class="text-6xl animate-pulse-scale">💳</span>
            </div>
        </div>

        <div class="text-center">
            <h2 class="text-3xl font-black mb-2">Aguardando pagamento</h2>
            <p class="text-white/70 text-lg">Aproxime ou insira o cartão na maquininha</p>
        </div>

        <div class="bg-white/20 rounded-2xl px-8 py-4 text-center">
            <p class="text-white/70 text-sm mb-1">Valor a pagar</p>
            <p class="text-4xl font-black"
               x-text="'R$ ' + totalPrice.toFixed(2).replace('.', ',')"></p>
        </div>

        <p class="text-white/50 text-sm text-center">Não feche ou recarregue esta tela</p>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP: SUCESSO
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'sucesso'"
         class="h-screen flex flex-col items-center justify-center gap-8 bg-green-50 px-8">

        {{-- Check animado --}}
        <div class="w-32 h-32 rounded-full bg-green-500 flex items-center justify-center shadow-xl">
            <svg viewBox="0 0 50 50" class="w-16 h-16">
                <polyline points="10,26 20,36 40,16"
                          fill="none" stroke="white" stroke-width="4"
                          stroke-linecap="round" stroke-linejoin="round"
                          class="draw-check"/>
            </svg>
        </div>

        <div class="text-center">
            <h2 class="text-4xl font-black text-green-700 mb-2">Pagamento Aprovado!</h2>
            <p class="text-gray-600 text-xl">Obrigado pelo seu pedido 😊</p>
            <p x-show="orderId" class="text-gray-400 text-sm mt-2"
               x-text="'Pedido #' + orderId"></p>
        </div>

        <div class="bg-white rounded-2xl shadow px-8 py-5 text-center border border-gray-100">
            <p class="text-gray-500 text-sm">Seu pedido está sendo preparado.</p>
            <p x-show="phone" class="text-gray-500 text-sm mt-1">
                Você receberá uma notificação no WhatsApp.
            </p>
        </div>

        <div class="text-center">
            <p class="text-gray-400 text-sm">Voltando em <span class="font-bold text-gray-600" x-text="resetCountdown"></span> segundos...</p>
            <button @click="resetKiosk()"
                    class="mt-3 text-sm text-gray-400 underline">
                Novo pedido agora
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP: ERRO
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'erro'"
         class="h-screen flex flex-col items-center justify-center gap-6 bg-red-50 px-8">

        <div class="w-24 h-24 rounded-full bg-red-500 flex items-center justify-center shadow-xl">
            <span class="text-5xl">✕</span>
        </div>

        <div class="text-center">
            <h2 class="text-3xl font-black text-red-700 mb-2">Ops! Algo deu errado</h2>
            <p class="text-gray-600 text-lg" x-text="errorMessage || 'Não foi possível processar o pagamento.'"></p>
        </div>

        <div class="flex flex-col gap-3 w-full max-w-sm">
            <button @click="step = 'checkout'"
                    class="w-full py-4 rounded-2xl font-bold text-white shadow active:scale-95"
                    style="background-color: {{ $setting->cor_primaria }};">
                Tentar novamente
            </button>
            <button @click="resetKiosk()"
                    class="w-full py-4 rounded-2xl font-bold text-gray-600 border-2 border-gray-200 bg-white active:bg-gray-50">
                Cancelar pedido
            </button>
        </div>
    </div>

</div>

<script>
function kiosk() {
    return {
        step: 'splash',
        mode: null,
        transitioning: false,

        // Catálogo
        loading: true,
        categories: [],
        products: [],
        selectedCategory: null,
        quantities: {},

        // UI
        showCart: false,
        showProductModal: false,
        modalProduct: null,
        modalQty: 1,

        // Checkout
        phone: '',
        nfe: false,
        paymentType: 'credit_card',

        // Pagamento
        orderId: null,
        transactionId: null,
        pollingInterval: null,
        errorMessage: '',

        // Reset
        resetCountdown: {{ $setting->reset_timeout ?? 10 }},
        resetInterval: null,

        // ── Computados ──────────────────────────────────────────────────

        get filteredProducts() {
            return this.selectedCategory
                ? this.products.filter(p => p.category_id === this.selectedCategory)
                : this.products;
        },

        get cartItems() {
            return this.products
                .filter(p => this.quantities[p.id] > 0)
                .map(p => ({
                    ...p,
                    quantity: this.quantities[p.id],
                    total: this.quantities[p.id] * p.price,
                }));
        },

        get totalItems() {
            return Object.values(this.quantities).reduce((a, b) => a + (b || 0), 0);
        },

        get totalPrice() {
            return this.products.reduce(
                (sum, p) => sum + (this.quantities[p.id] || 0) * p.price, 0
            );
        },

        // ── Init ────────────────────────────────────────────────────────

        async init() {
            await this.carregarProdutos();
        },

        async carregarProdutos() {
            this.loading = true;
            try {
                const data = await fetch('/api/kiosk/produtos', {
                    headers: { 'Accept': 'application/json' }
                }).then(r => r.json());

                this.categories = data.categories || [];
                this.products   = data.products   || [];

                // Inicializa quantidades
                const qtds = {};
                this.products.forEach(p => { qtds[p.id] = 0; });
                this.quantities = qtds;
            } catch (e) {
                console.error('[Kiosk] Erro ao carregar produtos', e);
            } finally {
                this.loading = false;
            }
        },

        // ── Splash ──────────────────────────────────────────────────────

        selectMode(value) {
            this.transitioning = true;
            this.mode = value;
            setTimeout(() => {
                this.step = 'menu';
                this.transitioning = false;
            }, 600);
        },

        // ── Carrinho ────────────────────────────────────────────────────

        increment(id) {
            this.quantities[id] = (this.quantities[id] || 0) + 1;
        },

        decrement(id) {
            if (this.quantities[id] > 0) this.quantities[id]--;
        },

        openCart() {
            if (this.totalItems > 0) this.showCart = true;
        },

        closeCart() {
            this.showCart = false;
        },

        // ── Modal de produto ─────────────────────────────────────────────

        openProductModal(prod) {
            this.modalProduct = prod;
            this.modalQty = this.quantities[prod.id] > 0 ? this.quantities[prod.id] : 1;
            this.showProductModal = true;
        },

        closeProductModal() {
            this.showProductModal = false;
            this.modalProduct = null;
            this.modalQty = 1;
        },

        addFromModal() {
            if (!this.modalProduct) return;
            this.quantities[this.modalProduct.id] = this.modalQty;
            this.closeProductModal();
        },

        removeFromModal() {
            if (!this.modalProduct) return;
            this.quantities[this.modalProduct.id] = 0;
            this.closeProductModal();
        },

        irParaCheckout() {
            this.showCart = false;
            this.step = 'checkout';
        },

        resetQuantidades() {
            const qtds = {};
            this.products.forEach(p => { qtds[p.id] = 0; });
            this.quantities = qtds;
        },

        // ── Helpers ──────────────────────────────────────────────────────

        csrfToken() {
            const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
            return match ? decodeURIComponent(match[1]) : '';
        },

        // ── Pedido / Pagamento ───────────────────────────────────────────

        async confirmarPedido() {
            this.step = 'pagando';

            const items = this.cartItems.map(item => ({
                product_id:       item.id,
                unit_quantity_id: item.unit_quantity_id,
                quantity:         item.quantity,
                unit_price:       item.price,
                name:             item.name,
            }));

            try {
                const response = await fetch('/api/kiosk/pedido', {
                    method:  'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-XSRF-TOKEN':  this.csrfToken(),
                    },
                    body: JSON.stringify({
                        items,
                        mode:         this.mode,
                        phone:        this.phone,
                        nfe:          this.nfe,
                        payment_type: this.paymentType,
                    }),
                });

                const data = await response.json();

                if (data.status === 'created' && data.transaction_id) {
                    this.orderId       = data.order_id;
                    this.transactionId = data.transaction_id;
                    this.iniciarPolling();
                } else {
                    this.errorMessage = data.message || 'Erro ao processar o pedido.';
                    this.step = 'erro';
                }
            } catch (e) {
                this.errorMessage = 'Erro de conexão. Tente novamente.';
                this.step = 'erro';
            }
        },

        iniciarPolling() {
            this.pollingInterval = setInterval(async () => {
                try {
                    const data = await fetch(`/api/kiosk/status/${this.transactionId}`, {
                        headers: { 'Accept': 'application/json' }
                    }).then(r => r.json());

                    if (data.status === 'success') {
                        clearInterval(this.pollingInterval);
                        this.step = 'sucesso';
                        this.iniciarContadorReset();
                    } else if (data.status === 'error') {
                        clearInterval(this.pollingInterval);
                        this.errorMessage = data.message || 'Pagamento não aprovado.';
                        this.step = 'erro';
                    }
                    // status 'pending' → continua polling
                } catch (e) {
                    // Erro de rede → continua tentando
                }
            }, 3000);
        },

        iniciarContadorReset() {
            this.resetCountdown = {{ $setting->reset_timeout ?? 10 }};
            this.resetInterval = setInterval(() => {
                this.resetCountdown--;
                if (this.resetCountdown <= 0) {
                    this.resetKiosk();
                }
            }, 1000);
        },

        resetKiosk() {
            clearInterval(this.pollingInterval);
            clearInterval(this.resetInterval);

            this.step          = 'splash';
            this.mode          = null;
            this.phone         = '';
            this.nfe           = false;
            this.paymentType   = 'credit_card';
            this.orderId       = null;
            this.transactionId = null;
            this.errorMessage  = '';
            this.showCart      = false;
            this.resetCountdown = {{ $setting->reset_timeout ?? 10 }};

            this.resetQuantidades();
        },
    };
}
</script>
@endsection
