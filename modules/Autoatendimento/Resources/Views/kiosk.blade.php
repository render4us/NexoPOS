@extends('Autoatendimento::layouts.kiosk')

@section('content')
<style>
/* ── Utilitários ── */
.no-scrollbar { scrollbar-width: none; -ms-overflow-style: none; }
.no-scrollbar::-webkit-scrollbar { display: none; }

/* ── Sidebar de categorias ── */
.cat-btn {
    display: flex; flex-direction: column; align-items: center;
    gap: 8px; padding: 14px 6px; border-radius: 18px;
    width: calc(100% - 14px); margin: 0 7px;
    transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
    cursor: pointer; border: none; background: transparent;
}
.cat-btn:active { transform: scale(0.93); }
.cat-icon {
    width: 68px; height: 68px; border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; transition: all 0.2s ease;
    background: rgba(255,255,255,0.07);
}
.cat-btn.active .cat-icon { background: rgba(255,255,255,0.22); }
/* ícone com foto de produto */
.cat-icon-img {
    background-size: cover;
    background-position: center;
    border: 2px solid rgba(255,255,255,0.12);
    border-radius: 50%;
}
.cat-btn.active .cat-icon-img { border-color: rgba(255,255,255,0.45); }
.cat-label {
    font-size: 11px; font-weight: 700; text-align: center;
    line-height: 1.2; color: rgba(255,255,255,0.4);
    max-width: 100px; overflow: hidden;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    transition: color 0.2s ease;
}
.cat-btn.active .cat-label { color: #fff; }
.cat-btn.active { background: var(--cp); }
.cat-btn:not(.active):hover { background: rgba(255,255,255,0.08); }
.cat-btn:not(.active):hover .cat-label { color: rgba(255,255,255,0.75); }

/* ── Sidebar accent line ── */
.cat-btn.active::before {
    content: '';
    position: absolute; left: -7px; top: 50%; transform: translateY(-50%);
    width: 4px; height: 32px; border-radius: 0 4px 4px 0;
    background: #fff; opacity: 0.6;
}
.cat-btn { position: relative; }


/* ── Product card ── */
.prod-card {
    background: #fff; border-radius: 18px; overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.055), 0 0 0 1px rgba(0,0,0,0.04);
    cursor: pointer; display: flex; flex-direction: column;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.prod-card:active { transform: scale(0.965); box-shadow: 0 1px 4px rgba(0,0,0,0.06); }

/* ── Glass cart bar ── */
.cart-bar {
    background: rgba(255,255,255,0.96);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid rgba(0,0,0,0.07);
    box-shadow: 0 -6px 32px rgba(0,0,0,0.10);
}

/* ── Animações ── */
@keyframes spin-kiosk   { to { transform: rotate(360deg); } }
@keyframes pulse-card   { 0%,100%{transform:scale(1)} 50%{transform:scale(1.06)} }
@keyframes badge-pop    { 0%{transform:scale(0)} 60%{transform:scale(1.35)} 100%{transform:scale(1)} }
@keyframes draw-check   { from{stroke-dashoffset:60} to{stroke-dashoffset:0} }
@keyframes slide-up     { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
@keyframes fade-in      { from{opacity:0} to{opacity:1} }
@keyframes shimmer      {
    0%   { background-position: -400px 0; }
    100% { background-position:  400px 0; }
}
.anim-spin    { animation: spin-kiosk 1s linear infinite; }
.anim-pulse   { animation: pulse-card 2.2s ease-in-out infinite; }
.anim-badge   { animation: badge-pop 0.22s cubic-bezier(0.34,1.56,0.64,1); }
.anim-check   { stroke-dasharray:60; stroke-dashoffset:60; animation: draw-check 0.5s ease-out 0.25s forwards; }
.anim-slide   { animation: slide-up 0.35s ease-out; }
.anim-fade    { animation: fade-in 0.3s ease-out; }

/* ── Shimmer skeleton ── */
.shimmer {
    background: linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);
    background-size: 800px 100%; animation: shimmer 1.4s infinite;
}

/* ── Modal glassmorphism ── */
.modal-panel {
    background: rgba(255,255,255,0.97);
    backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
}
</style>

<div x-data="kiosk()" x-cloak class="h-screen w-screen overflow-hidden"
     style="--cp: {{ $setting->cor_primaria }};">

    {{-- ══════════════════════════════════════════════════════
         STEP: SPLASH
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'splash'" class="relative h-screen w-full overflow-hidden">

        {{-- Fundo --}}
        @if($setting->video_url)
        <video autoplay muted loop playsinline
               class="absolute inset-0 w-full h-full object-cover">
            <source src="{{ $setting->video_url }}" type="video/mp4">
        </video>
        @else
        <div class="absolute inset-0"
             style="background: linear-gradient(160deg, {{ $setting->cor_primaria }} 0%, #0c0c0c 70%);"></div>
        @endif

        {{-- Gradiente de profundidade (retrô → base) --}}
        <div class="absolute inset-0"
             style="background: linear-gradient(to bottom, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.4) 45%, rgba(0,0,0,0.82) 100%);"></div>

        {{-- Overlay de transição --}}
        <template x-if="transitioning">
            <div class="absolute inset-0 z-50 anim-fade"
                 style="background-color: {{ $setting->cor_primaria }};"></div>
        </template>

        {{-- Conteúdo em coluna para portrait --}}
        <div class="relative z-10 h-full flex flex-col items-center px-8" style="padding-top: 12vh; padding-bottom: 10vh;">

            {{-- Logo com halo --}}
            @if($setting->logo_url)
            <div class="relative flex items-center justify-center mb-6">
                <div class="absolute w-48 h-48 rounded-full opacity-20 blur-3xl"
                     style="background-color: {{ $setting->cor_primaria }};"></div>
                <img src="{{ $setting->logo_url }}" alt="Logo"
                     class="relative h-36 object-contain drop-shadow-2xl">
            </div>
            @endif

            {{-- Título --}}
            <div class="text-center mb-2">
                <h1 class="text-white font-black tracking-tight drop-shadow-xl leading-none"
                    style="font-size: clamp(2.2rem, 8vw, 3.5rem);">
                    {{ $setting->titulo }}
                </h1>
                <p class="text-white/55 mt-3" style="font-size: clamp(1rem, 3.5vw, 1.35rem);">
                    {{ $setting->subtitulo }}
                </p>
            </div>

            {{-- Divisor ornamental --}}
            <div class="flex items-center gap-3 my-8 w-full max-w-xs">
                <div class="flex-1 h-px bg-white/20"></div>
                <div class="w-2 h-2 rounded-full bg-white/30"></div>
                <div class="flex-1 h-px bg-white/20"></div>
            </div>

            {{-- Prompt --}}
            <p class="text-white/40 text-xs font-semibold uppercase tracking-[0.2em] mb-6">
                Como será seu pedido?
            </p>

            {{-- Botões de modo (portrait: empilhados) --}}
            <div class="w-full max-w-sm space-y-3 flex-1 flex flex-col justify-center">

                {{-- Comer aqui --}}
                <button type="button" @click="selectMode('eat_in')"
                        class="w-full bg-white flex items-center gap-5 px-6 py-5 rounded-2xl shadow-2xl
                               active:scale-[0.97] transition-all duration-150">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl flex-shrink-0"
                         style="background-color: {{ $setting->cor_primaria }}18;">
                        🍽️
                    </div>
                    <div class="text-left flex-1">
                        <p class="font-black text-lg leading-none"
                           style="color: {{ $setting->cor_primaria }};">Comer Aqui</p>
                        <p class="text-gray-400 text-sm mt-1">Acomodação no local</p>
                    </div>
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         style="color: {{ $setting->cor_primaria }}; opacity:0.5;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                {{-- Para levar --}}
                <button type="button" @click="selectMode('takeaway')"
                        class="w-full flex items-center gap-5 px-6 py-5 rounded-2xl border-2 border-white/25
                               active:bg-white/10 transition-colors duration-150">
                    <div class="w-14 h-14 rounded-xl bg-white/14 flex items-center justify-center text-3xl flex-shrink-0">
                        🥡
                    </div>
                    <div class="text-left flex-1">
                        <p class="font-black text-lg text-white leading-none">Para Levar</p>
                        <p class="text-white/45 text-sm mt-1">Embalagem de viagem</p>
                    </div>
                    <svg class="w-5 h-5 text-white/35 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            <p class="text-white/30 text-xs mt-8 animate-pulse tracking-widest uppercase">
                Toque para iniciar
            </p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP: MENU  — layout portrait: sidebar + grid
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'menu'" class="flex flex-col h-screen" style="background:#f4f4f6;">

        {{-- Header compacto --}}
        <header class="flex-shrink-0 z-40"
                style="background: linear-gradient(135deg, {{ $setting->cor_primaria }} 0%, {{ $setting->cor_primaria }}dd 100%);
                       box-shadow: 0 2px 16px rgba(0,0,0,0.18);">
            <div class="flex items-center gap-3 px-4 py-3">
                @if($setting->logo_url)
                <img src="{{ $setting->logo_url }}" alt="" class="h-9 object-contain flex-shrink-0">
                <div class="w-px h-7 bg-white/20 flex-shrink-0"></div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="font-black text-white text-base leading-none">{{ $setting->titulo }}</p>
                    <p class="text-white/55 text-[11px] mt-0.5 font-medium">
                        <span x-show="mode === 'eat_in'">🍽️ &nbsp;Mesa no local</span>
                        <span x-show="mode === 'takeaway'">🥡 &nbsp;Para levar</span>
                    </p>
                </div>
                <button type="button" @click="step = 'splash'; resetQuantidades()"
                        class="flex-shrink-0 h-9 px-4 rounded-full text-white/80 text-sm font-semibold
                               border border-white/25 active:bg-white/20 transition-colors">
                    ✕ Sair
                </button>
            </div>
        </header>

        {{-- Corpo: sidebar + produtos --}}
        <div class="flex flex-1 overflow-hidden">

            {{-- ── Sidebar de categorias ── --}}
            <aside id="cat-sidebar" class="flex-shrink-0 overflow-y-auto no-scrollbar"
                   style="width:120px; background:{{ $setting->cor_sidebar ?? '#111116' }};">

                {{-- Wrapper centraliza verticalmente quando cabe; expande quando transborda --}}
                <div class="flex flex-col gap-1 py-4 min-h-full justify-center">

                    {{-- "Todos" --}}
                    <button type="button" @click="selectedCategory = null"
                            :class="selectedCategory === null ? 'active' : ''"
                            class="cat-btn">
                        <div class="cat-icon">🏠</div>
                        <span class="cat-label">Todos</span>
                    </button>

                    {{-- Categorias dinâmicas --}}
                    <template x-for="cat in categories" :key="cat.id">
                        <button type="button" @click="selectedCategory = cat.id"
                                :class="selectedCategory === cat.id ? 'active' : ''"
                                class="cat-btn">
                            {{-- Usa foto do primeiro produto da categoria, se existir --}}
                            <template x-if="getCategoryImage(cat.id)">
                                <div class="cat-icon cat-icon-img"
                                     :style="`background-image: url('${getCategoryImage(cat.id)}');`"></div>
                            </template>
                            <template x-if="!getCategoryImage(cat.id)">
                                <div class="cat-icon" x-text="getCategoryIcon(cat.name)"></div>
                            </template>
                            <span class="cat-label" x-text="cat.name"></span>
                        </button>
                    </template>

                </div>
            </aside>

            {{-- ── Área de produtos ── --}}
            <main class="flex-1 overflow-y-auto no-scrollbar" style="padding-bottom: 90px;">

                {{-- Cabeçalho da categoria --}}
                <div class="px-3 pt-3 pb-2">
                    <h2 class="font-black text-gray-800 text-base leading-snug"
                        x-text="selectedCategory
                            ? (categories.find(c => c.id === selectedCategory)?.name ?? '')
                            : 'Cardápio Completo'"></h2>
                    <p class="text-[11px] text-gray-400 mt-0.5"
                       x-text="filteredProducts.length + (filteredProducts.length === 1 ? ' produto' : ' produtos')"></p>
                </div>

                {{-- Loading --}}
                <div x-show="loading" class="grid grid-cols-4 gap-2 px-3">
                    <template x-for="n in [1,2,3,4,5,6,7,8]" :key="n">
                        <div class="prod-card">
                            <div class="w-full shimmer" style="aspect-ratio:1/1;"></div>
                            <div class="p-3 space-y-2">
                                <div class="h-3 rounded shimmer w-4/5"></div>
                                <div class="h-3 rounded shimmer w-2/5"></div>
                                <div class="h-8 rounded-xl shimmer mt-1"></div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Sem produtos --}}
                <div x-show="!loading && filteredProducts.length === 0"
                     class="flex flex-col items-center justify-center h-52 text-gray-300 gap-2">
                    <span class="text-5xl">🍽️</span>
                    <p class="text-sm font-medium">Nenhum item disponível</p>
                </div>

                {{-- Grid --}}
                <div x-show="!loading && filteredProducts.length > 0"
                     class="grid grid-cols-4 gap-2 px-3">
                    <template x-for="prod in filteredProducts" :key="prod.id">
                        <div @click="openProductModal(prod)" class="prod-card">

                            {{-- Imagem 1:1 com overlay de preço --}}
                            <div class="relative" style="aspect-ratio:1/1; overflow:hidden;">
                                <img :src="prod.image || '/images/placeholder.png'"
                                     :alt="prod.name"
                                     class="absolute inset-0 w-full h-full object-cover bg-gray-100"
                                     x-on:error="$event.target.src='/images/placeholder.png'">

                                {{-- Gradiente + preço na imagem --}}
                                <div class="absolute inset-x-0 bottom-0 h-14"
                                     style="background:linear-gradient(to top,rgba(0,0,0,0.65) 0%,transparent 100%);"></div>
                                <p class="absolute bottom-2 left-2.5 text-white font-black text-sm drop-shadow"
                                   x-text="'R$ ' + prod.price.toFixed(2).replace('.', ',')"></p>

                                {{-- Badge de quantidade --}}
                                <template x-if="quantities[prod.id] > 0">
                                    <div class="absolute top-2 right-2 w-6 h-6 rounded-full text-white
                                                text-[11px] font-black flex items-center justify-center
                                                shadow-lg anim-badge"
                                         style="background-color: {{ $setting->cor_primaria }};"
                                         x-text="quantities[prod.id]"></div>
                                </template>
                            </div>

                            {{-- Info --}}
                            <div class="p-2.5 flex flex-col gap-2 flex-1">
                                <h3 class="text-[11px] font-bold text-gray-800 leading-snug line-clamp-2 flex-1"
                                    x-text="prod.name"></h3>

                                {{-- Botão Adicionar / Editando --}}
                                <template x-if="quantities[prod.id] > 0">
                                    <div class="flex items-center justify-between rounded-xl px-3 py-2
                                                text-[11px] font-bold"
                                         :style="'background-color: {{ $setting->cor_primaria }}18; color: {{ $setting->cor_primaria }};'">
                                        <span x-text="quantities[prod.id] + ' no pedido'"></span>
                                        <span>✎</span>
                                    </div>
                                </template>
                                <template x-if="!quantities[prod.id]">
                                    <div class="flex items-center justify-center gap-1.5 rounded-xl py-2
                                                text-[11px] font-black text-white"
                                         style="background-color: {{ $setting->cor_primaria }};">
                                        <span class="text-base leading-none font-black">+</span>
                                        <span>Adicionar</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

            </main>
        </div>

        {{-- ── Cart bar flutuante ── --}}
        <div class="fixed bottom-0 left-0 right-0 z-40 cart-bar">
            <div class="flex items-center gap-3 px-4 py-3"
                 style="padding-left: calc(120px + 1rem);">
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider leading-none">
                        Seu pedido
                    </p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-xl font-black text-gray-900"
                              x-text="'R$ ' + totalPrice.toFixed(2).replace('.', ',')"></span>
                        <span class="text-xs text-gray-400"
                              x-text="'· ' + totalItems + (totalItems === 1 ? ' item' : ' itens')"></span>
                    </div>
                </div>
                <button type="button" @click="openCart()"
                        :disabled="totalItems === 0"
                        class="flex items-center gap-2.5 px-6 py-3 rounded-2xl font-bold text-sm
                               transition-all duration-150 flex-shrink-0"
                        :style="totalItems > 0
                            ? 'background-color: #16a34a; color:white; box-shadow:0 4px 16px rgba(22,163,74,0.35);'
                            : 'background-color:#f0f0f2; color:#b0b0b8; cursor:not-allowed;'">
                    <span>Ver Pedido</span>
                    <template x-if="totalItems > 0">
                        <span class="w-5 h-5 rounded-full bg-white/25 text-[11px] font-black
                                     flex items-center justify-center"
                              x-text="totalItems"></span>
                    </template>
                </button>
            </div>
        </div>

        {{-- ── Modal: Detalhe do Produto ── --}}
        <div x-show="showProductModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[60] flex items-center justify-center p-6"
             style="display:none;">

            {{-- Scrim --}}
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                 @click="closeProductModal()"></div>

            {{-- Painel horizontal compacto --}}
            <div x-show="showProductModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="modal-panel relative w-full rounded-2xl overflow-hidden shadow-2xl flex"
                 style="max-width:560px; max-height:320px;">

                <template x-if="modalProduct">
                    <div class="flex w-full">

                        {{-- Imagem à esquerda — quadrada, completa --}}
                        <div class="flex-shrink-0 relative" style="width:260px;">
                            <img :src="modalProduct.image || '/images/placeholder.png'"
                                 :alt="modalProduct.name"
                                 class="w-full h-full object-contain bg-gray-50"
                                 x-on:error="$event.target.src='/images/placeholder.png'">

                            {{-- Badge qtd no carrinho --}}
                            <template x-if="quantities[modalProduct.id] > 0">
                                <div class="absolute top-2 left-2 text-white text-xs font-bold
                                            px-2.5 py-1 rounded-full shadow"
                                     style="background-color: {{ $setting->cor_primaria }};"
                                     x-text="quantities[modalProduct.id] + ' no pedido'"></div>
                            </template>
                        </div>

                        {{-- Detalhes + controles à direita --}}
                        <div class="flex-1 flex flex-col justify-between p-5 min-w-0">

                            {{-- Fechar --}}
                            <button type="button" @click="closeProductModal()"
                                    class="absolute top-3 right-3 w-8 h-8 rounded-full bg-gray-100
                                           text-gray-500 flex items-center justify-center text-sm
                                           active:bg-gray-200 transition-colors">
                                ✕
                            </button>

                            {{-- Nome + preço + descrição --}}
                            <div class="pr-8">
                                <h2 class="text-base font-black text-gray-900 leading-snug line-clamp-2"
                                    x-text="modalProduct.name"></h2>
                                <p class="text-xl font-black mt-1"
                                   style="color: {{ $setting->cor_primaria }};"
                                   x-text="'R$ ' + modalProduct.price.toFixed(2).replace('.', ',')"></p>
                                <template x-if="modalProduct.description">
                                    <p class="text-gray-400 text-xs mt-2 leading-relaxed line-clamp-3"
                                       x-text="modalProduct.description"></p>
                                </template>
                            </div>

                            {{-- Seletor de quantidade --}}
                            <div class="flex items-center gap-3 mt-3">
                                <button type="button" @click="modalQty = Math.max(1, modalQty - 1)"
                                        class="w-10 h-10 rounded-full flex items-center justify-center
                                               text-xl font-bold active:scale-90 transition-transform flex-shrink-0"
                                        :style="modalQty > 1
                                            ? 'background-color: {{ $setting->cor_primaria }}; color:white;'
                                            : 'background-color:#f3f4f6; color:#d1d5db;'">
                                    −
                                </button>
                                <span class="text-2xl font-black text-gray-900 w-8 text-center flex-shrink-0"
                                      x-text="modalQty"></span>
                                <button type="button" @click="modalQty++"
                                        class="w-10 h-10 rounded-full text-white flex items-center justify-center
                                               text-xl font-bold active:scale-90 transition-transform flex-shrink-0"
                                        style="background-color: {{ $setting->cor_primaria }};">
                                    +
                                </button>
                                <span class="text-xs text-gray-400 flex-1 text-right leading-tight">
                                    Subtotal:<br>
                                    <span class="font-bold" style="color: {{ $setting->cor_primaria }};"
                                          x-text="'R$ ' + (modalQty * modalProduct.price).toFixed(2).replace('.', ',')"></span>
                                </span>
                            </div>

                            {{-- Botões de ação --}}
                            <div class="flex gap-2 mt-3">
                                <template x-if="quantities[modalProduct.id] > 0">
                                    <button type="button" @click="removeFromModal()"
                                            class="flex-shrink-0 px-3 py-3 rounded-xl font-bold text-xs
                                                   border-2 border-gray-200 text-gray-500
                                                   active:bg-gray-50 transition-colors">
                                        🗑
                                    </button>
                                </template>
                                <button type="button" @click="addFromModal()"
                                        class="flex-1 py-3 rounded-xl font-black text-white text-sm
                                               active:scale-95 transition-transform"
                                        style="background-color: {{ $setting->cor_primaria }};"
                                        x-text="quantities[modalProduct.id] > 0 ? 'Atualizar pedido' : 'Adicionar ao pedido'">
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── Bottom Sheet: Carrinho ── --}}
        <div x-show="showCart" class="fixed inset-0 z-50 flex items-end">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeCart()"></div>
            <div class="relative w-full bg-white rounded-t-3xl max-h-[88vh] flex flex-col shadow-2xl">

                <div class="flex justify-center pt-3 pb-1 flex-shrink-0">
                    <div class="w-10 h-1.5 bg-gray-200 rounded-full"></div>
                </div>

                <div class="flex items-center justify-between px-6 py-3 flex-shrink-0">
                    <h2 class="text-2xl font-black text-gray-900">Meu Pedido</h2>
                    <button type="button" @click="closeCart()"
                            class="w-9 h-9 bg-gray-100 rounded-full flex items-center justify-center
                                   text-gray-500 text-lg active:bg-gray-200">
                        ✕
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 no-scrollbar">
                    <p x-show="cartItems.length === 0"
                       class="text-center text-gray-400 text-lg py-12">
                        Nenhum item adicionado
                    </p>
                    <template x-for="item in cartItems" :key="item.id">
                        <div class="flex items-center gap-3 py-4 border-b border-gray-50 last:border-0">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 leading-snug text-sm"
                                   x-text="item.name"></p>
                                <p class="text-xs text-gray-400 mt-0.5"
                                   x-text="item.quantity + '× R$ ' + item.price.toFixed(2).replace('.', ',')"></p>
                            </div>
                            <p class="font-black text-base flex-shrink-0"
                               style="color: {{ $setting->cor_primaria }};"
                               x-text="'R$ ' + item.total.toFixed(2).replace('.', ',')"></p>
                        </div>
                    </template>
                </div>

                <div class="flex-shrink-0 px-6 pt-4 pb-8 border-t border-gray-100">
                    <div class="flex justify-between items-center mb-5">
                        <span class="text-gray-500 font-medium">Total do pedido</span>
                        <span class="text-3xl font-black text-gray-900"
                              x-text="'R$ ' + totalPrice.toFixed(2).replace('.', ',')"></span>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="closeCart()"
                                class="flex-1 py-4 rounded-2xl font-bold text-base border-2 active:opacity-75 transition-opacity"
                                :style="'border-color: {{ $setting->cor_primaria }}; color: {{ $setting->cor_primaria }};'">
                            + Adicionar
                        </button>
                        <button type="button" @click="irParaCheckout()"
                                class="flex-1 py-4 rounded-2xl font-bold text-base text-white
                                       shadow-lg active:scale-95 transition-transform"
                                style="background-color: #16a34a; box-shadow: 0 4px 16px rgba(22,163,74,0.35);">
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
    <div x-show="step === 'checkout'" class="flex flex-col h-screen" style="background:#f4f4f6;">

        <header class="text-white flex-shrink-0 z-10"
                style="background: linear-gradient(135deg, {{ $setting->cor_primaria }} 0%, {{ $setting->cor_primaria }}dd 100%);
                       box-shadow:0 2px 16px rgba(0,0,0,0.18);">
            <div class="flex items-center px-5 py-4">
                <button type="button" @click="step = 'menu'; showCart = true"
                        class="mr-4 w-10 h-10 rounded-full bg-white/20 flex items-center justify-center
                               active:bg-white/30 text-xl">
                    ←
                </button>
                <h1 class="font-black text-xl flex-1 text-center">Finalizar Pedido</h1>
                <div class="w-10"></div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 space-y-3 no-scrollbar pb-4">

            {{-- Resumo --}}
            <div class="bg-white rounded-2xl overflow-hidden"
                 style="box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                <div class="px-4 py-3 border-b border-gray-50 flex items-center gap-2">
                    <span class="text-base">📋</span>
                    <h2 class="font-bold text-gray-800">Resumo do Pedido</h2>
                </div>
                <div class="px-4">
                    <template x-for="item in cartItems" :key="item.id">
                        <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                            <div>
                                <p class="font-semibold text-gray-800 text-sm" x-text="item.name"></p>
                                <p class="text-xs text-gray-400"
                                   x-text="item.quantity + '× R$ ' + item.price.toFixed(2).replace('.', ',')"></p>
                            </div>
                            <p class="font-bold text-sm"
                               style="color: {{ $setting->cor_primaria }};"
                               x-text="'R$ ' + item.total.toFixed(2).replace('.', ',')"></p>
                        </div>
                    </template>
                </div>
                <div class="px-4 py-3 flex justify-between items-center rounded-b-2xl"
                     style="background-color: {{ $setting->cor_primaria }}12;">
                    <span class="font-bold text-gray-700">Total</span>
                    <span class="text-2xl font-black"
                          style="color: {{ $setting->cor_primaria }};"
                          x-text="'R$ ' + totalPrice.toFixed(2).replace('.', ',')"></span>
                </div>
            </div>

            {{-- WhatsApp --}}
            <div class="bg-white rounded-2xl p-4"
                 style="box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                <label class="block font-bold text-gray-800 mb-2 text-sm">
                    📱 WhatsApp
                    <span class="text-gray-400 text-xs font-normal ml-1">(opcional)</span>
                </label>
                <input type="tel"
                       x-model="phone"
                       placeholder="(99) 99999-9999"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-base
                              focus:outline-none focus:ring-2 transition-shadow"
                       style="--tw-ring-color: {{ $setting->cor_primaria }}40;"
                       inputmode="tel">
            </div>

            {{-- Nota Fiscal --}}
            <label class="bg-white rounded-2xl p-4 flex items-center gap-4 cursor-pointer active:bg-gray-50"
                   style="box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                <div class="w-7 h-7 rounded-xl border-2 flex items-center justify-center flex-shrink-0 transition-all"
                     :style="nfe
                         ? 'background-color: {{ $setting->cor_primaria }}; border-color: {{ $setting->cor_primaria }};'
                         : 'border-color: #d1d5db; background: white;'">
                    <svg x-show="nfe" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <input type="checkbox" x-model="nfe" class="hidden">
                <div class="flex-1">
                    <p class="font-bold text-gray-800 text-sm">🧾 Solicitar Nota Fiscal</p>
                    <p class="text-xs text-gray-400 mt-0.5">Emitida automaticamente após o pagamento</p>
                </div>
            </label>

            {{-- Forma de pagamento --}}
            <div class="bg-white rounded-2xl p-4"
                 style="box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                <h2 class="font-bold text-gray-800 mb-3 text-sm">💳 Forma de pagamento</h2>
                <div class="grid grid-cols-3 gap-3">
                    <button type="button" @click="paymentType = 'credit_card'"
                            class="py-4 rounded-xl font-bold text-sm border-2 transition-all"
                            :style="paymentType === 'credit_card'
                                ? 'border-color: {{ $setting->cor_primaria }}; background-color: {{ $setting->cor_primaria }}14; color: {{ $setting->cor_primaria }};'
                                : 'border-color:#e5e7eb; color:#6b7280;'">
                        💳 Crédito
                    </button>
                    <button type="button" @click="paymentType = 'debit_card'"
                            class="py-4 rounded-xl font-bold text-sm border-2 transition-all"
                            :style="paymentType === 'debit_card'
                                ? 'border-color: {{ $setting->cor_primaria }}; background-color: {{ $setting->cor_primaria }}14; color: {{ $setting->cor_primaria }};'
                                : 'border-color:#e5e7eb; color:#6b7280;'">
                        💳 Débito
                    </button>
                    <button type="button" @click="paymentType = 'pix'"
                            class="py-4 rounded-xl font-bold text-sm border-2 transition-all"
                            :style="paymentType === 'pix'
                                ? 'border-color: #32BCAD; background-color: #32BCAD18; color: #32BCAD;'
                                : 'border-color:#e5e7eb; color:#6b7280;'">
                        <span class="block text-lg leading-none mb-0.5">⚡</span>
                        Pix
                    </button>
                </div>
            </div>
        </div>

        <div class="flex-shrink-0 p-4 bg-white border-t border-gray-100">
            <button type="button" @click="irParaCpfOuPagar()"
                    class="w-full py-5 rounded-2xl font-black text-xl text-white
                           active:scale-95 transition-transform"
                    style="background-color: {{ $setting->cor_primaria }};
                           box-shadow: 0 6px 24px {{ $setting->cor_primaria }}55;">
                Confirmar e Pagar
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP: CPF
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'cpf'" class="flex flex-col h-screen" style="background:#f4f4f6;">

        <header class="text-white flex-shrink-0 z-10"
                style="background: linear-gradient(135deg, {{ $setting->cor_primaria }} 0%, {{ $setting->cor_primaria }}dd 100%);
                       box-shadow:0 2px 16px rgba(0,0,0,0.18);">
            <div class="flex items-center px-5 py-4">
                <button type="button" @click="step = 'checkout'"
                        class="mr-4 w-10 h-10 rounded-full bg-white/20 flex items-center justify-center active:bg-white/30 text-xl">
                    ←
                </button>
                <h1 class="font-black text-xl flex-1 text-center">CPF na Nota Fiscal</h1>
                <div class="w-10"></div>
            </div>
        </header>

        <div class="flex-1 flex flex-col items-center justify-center px-6 gap-5">

            <div class="text-center">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-3"
                     style="background-color: {{ $setting->cor_primaria }}18;">
                    🧾
                </div>
                <h2 class="text-2xl font-black text-gray-800">CPF na nota?</h2>
                <p class="text-gray-500 mt-1 text-sm">Digite para incluir na nota fiscal</p>
            </div>

            {{-- Display CPF --}}
            <div class="w-full max-w-sm bg-white rounded-2xl border-2 px-6 py-4 text-center transition-colors"
                 :style="cpfValido
                     ? 'border-color: {{ $setting->cor_primaria }}; box-shadow: 0 0 0 4px {{ $setting->cor_primaria }}18;'
                     : 'border-color:#e5e7eb;'">
                <p class="text-xs text-gray-400 mb-1 font-semibold uppercase tracking-wider">CPF</p>
                <p class="text-3xl font-black tracking-widest text-gray-800 font-mono min-h-[2.5rem]"
                   x-text="cpfFormatado || '___.___.___-__'"></p>
            </div>

            {{-- Teclado numérico --}}
            <div class="w-full max-w-sm grid grid-cols-3 gap-2.5">
                <template x-for="digit in [1,2,3,4,5,6,7,8,9]" :key="digit">
                    <button type="button" @click="cpfPressDigit(digit)"
                            :disabled="cpfDigits.length >= 11"
                            class="h-16 rounded-2xl font-black text-2xl text-gray-800 bg-white
                                   shadow-sm border border-gray-100 active:scale-95
                                   transition-transform disabled:opacity-35"
                            x-text="digit"></button>
                </template>
                <button type="button" @click="cpfBackspace()"
                        class="h-16 rounded-2xl font-bold text-xl text-gray-500 bg-white
                               shadow-sm border border-gray-100 active:scale-95 transition-transform">
                    ⌫
                </button>
                <button type="button" @click="cpfPressDigit(0)"
                        :disabled="cpfDigits.length >= 11"
                        class="h-16 rounded-2xl font-black text-2xl text-gray-800 bg-white
                               shadow-sm border border-gray-100 active:scale-95
                               transition-transform disabled:opacity-35">
                    0
                </button>
                <button type="button" @click="cpfDigits = []"
                        class="h-16 rounded-2xl font-bold text-xs text-gray-400 bg-white
                               shadow-sm border border-gray-100 active:scale-95 transition-transform">
                    Limpar
                </button>
            </div>
        </div>

        <div class="flex-shrink-0 p-4 bg-white border-t border-gray-100 space-y-3">
            <button type="button" @click="confirmarCpf()"
                    :disabled="!cpfValido"
                    class="w-full py-4 rounded-2xl font-black text-lg text-white
                           active:scale-95 transition-transform disabled:opacity-35"
                    style="background-color: {{ $setting->cor_primaria }};
                           box-shadow: 0 6px 24px {{ $setting->cor_primaria }}50;">
                ✓ Confirmar CPF e Pagar
            </button>
            <button type="button" @click="pularCpf()"
                    class="w-full py-3 rounded-2xl font-semibold text-sm text-gray-500
                           border-2 border-gray-200 bg-white active:bg-gray-50">
                Pular — não quero CPF na nota
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP: PAGANDO
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'pagando'"
         class="h-screen flex flex-col items-center justify-center gap-7 px-8 relative overflow-hidden"
         style="background-color: {{ $setting->cor_primaria }};">

        {{-- Círculos decorativos de fundo --}}
        <div class="absolute -top-24 -right-24 w-64 h-64 rounded-full bg-white opacity-5"></div>
        <div class="absolute -bottom-16 -left-16 w-48 h-48 rounded-full bg-white opacity-5"></div>
        <div class="absolute top-1/3 left-1/2 -translate-x-1/2 w-80 h-80 rounded-full bg-white opacity-[0.03]"></div>

        {{-- Animação spinner + ícone --}}
        <div class="relative z-10 flex items-center justify-center">
            <div class="w-40 h-40 rounded-full border-[5px] border-white/15 absolute"></div>
            <div class="w-40 h-40 rounded-full border-[5px] border-transparent absolute anim-spin"
                 style="border-top-color: rgba(255,255,255,0.9);"></div>
            <span class="text-7xl anim-pulse" x-text="paymentType === 'pix' ? '📱' : '💳'"></span>
        </div>

        <div class="text-center text-white z-10">
            <h2 class="text-3xl font-black mb-2 leading-tight">Aguardando pagamento</h2>
            <p class="text-white/65 text-base" x-show="paymentType !== 'pix'">Aproxime ou insira o cartão<br>na maquininha</p>
            <p class="text-white/65 text-base" x-show="paymentType === 'pix'">Escaneie o QR Code com o app do seu banco</p>
        </div>

        {{-- QR Code Pix (exibido diretamente nesta tela quando for pix) --}}
        <div x-show="paymentType === 'pix'" class="z-10 w-full max-w-xs">
            <div class="bg-white rounded-2xl p-4 flex flex-col items-center gap-3 shadow-xl">
                <div x-show="pixQrCodeBase64" class="p-2 border-2 rounded-xl" style="border-color:#32BCAD55;">
                    <img :src="'data:image/png;base64,' + pixQrCodeBase64"
                         class="w-52 h-52 object-contain" alt="QR Code Pix">
                </div>
                <div x-show="!pixQrCodeBase64"
                     class="w-52 h-52 rounded-xl flex items-center justify-center"
                     style="background:#32BCAD12;">
                    <div class="w-10 h-10 rounded-full border-4 border-transparent anim-spin"
                         style="border-top-color:#32BCAD;"></div>
                </div>
                <p class="text-gray-400 text-xs text-center">O QR Code expira em 30 minutos</p>
            </div>
        </div>

        {{-- Aviso maquininha (só para cartão/débito) --}}
        <div x-show="paymentType !== 'pix'" class="z-10 w-full max-w-sm">
            <div class="flex items-center gap-4 bg-white rounded-2xl px-5 py-4 shadow-xl"
                 style="border-left: 6px solid #22c55e;">
                <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-2xl font-black text-white"
                     style="background-color: #22c55e;">
                    ✓
                </div>
                <div>
                    <p class="font-black text-gray-800 text-sm leading-tight">
                        Siga as instruções na maquininha
                    </p>
                    <p class="text-gray-500 text-xs mt-1 leading-snug">
                        Pressione o <strong class="text-green-600">botão verde</strong> para confirmar<br>e aproxime ou insira o cartão.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white/15 backdrop-blur-sm rounded-2xl px-10 py-5 text-center z-10 border border-white/20">
            <p class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-1">Valor a pagar</p>
            <p class="text-5xl font-black text-white"
               x-text="'R$ ' + totalPrice.toFixed(2).replace('.', ',')"></p>
        </div>

        <p class="text-white/35 text-xs text-center z-10">Não feche ou recarregue esta tela</p>

        @if ($setting->teste_pagamento_ativo)
        {{-- Botão de teste — só aparece quando habilitado nas configurações --}}
        <div class="z-10 flex flex-col items-center gap-2">
            <button type="button" @click="simularPagamento()"
                    class="flex items-center gap-2 px-5 py-2 rounded-xl text-sm font-semibold
                           bg-white/10 border border-white/20 text-white/70 hover:bg-white/20
                           hover:text-white transition-all backdrop-blur-sm">
                <span class="text-base">🧪</span>
                Simular Pagamento Aprovado
            </button>
            <p class="text-white/30 text-xs">Modo teste — não usar em produção</p>
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP: PAGANDO PIX
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'pagando_pix'" class="flex flex-col h-screen" style="background:#f4f4f6;">

        <header class="text-white flex-shrink-0 z-10"
                style="background: linear-gradient(135deg, #32BCAD 0%, #1a9e90 100%);
                       box-shadow:0 2px 16px rgba(0,0,0,0.18);">
            <div class="flex items-center px-5 py-4">
                <h1 class="font-black text-xl flex-1 text-center">⚡ Pagar com Pix</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto flex flex-col items-center justify-center gap-5 px-6 py-4">

            {{-- Valor --}}
            <div class="bg-white rounded-2xl px-8 py-4 text-center w-full"
                 style="box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-widest mb-1">Valor a pagar</p>
                <p class="text-4xl font-black" style="color:#32BCAD;"
                   x-text="'R$ ' + totalPrice.toFixed(2).replace('.', ',')"></p>
            </div>

            {{-- QR Code --}}
            <div class="bg-white rounded-2xl p-5 flex flex-col items-center gap-3 w-full"
                 style="box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                <p class="text-sm font-bold text-gray-700">📱 Abra o app do seu banco e escaneie o QR Code</p>

                <div x-show="pixQrCodeBase64" class="p-3 border-2 rounded-2xl" style="border-color:#32BCAD33;">
                    <img :src="'data:image/png;base64,' + pixQrCodeBase64"
                         class="w-64 h-64 object-contain" alt="QR Code Pix">
                </div>

                <div x-show="!pixQrCodeBase64"
                     class="w-64 h-64 rounded-2xl flex items-center justify-center"
                     style="background:#32BCAD12;">
                    <div class="w-12 h-12 rounded-full border-4 border-transparent anim-spin"
                         style="border-top-color:#32BCAD;"></div>
                </div>
            </div>

            {{-- Aguardando --}}
            <div class="flex items-center gap-3 text-gray-500">
                <div class="w-5 h-5 rounded-full border-2 border-transparent anim-spin flex-shrink-0"
                     style="border-top-color:#32BCAD;"></div>
                <p class="text-sm">Aguardando confirmação do pagamento...</p>
            </div>

            <p class="text-gray-400 text-xs text-center">O QR Code expira em 30 minutos.<br>Não feche ou recarregue esta tela.</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP: SUCESSO
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'sucesso'"
         class="h-screen flex flex-col items-center justify-center gap-8 px-8 relative overflow-hidden"
         style="background: linear-gradient(160deg, #dcfce7 0%, #f0fdf4 60%, #fff 100%);">

        <div class="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-green-200 opacity-30"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 rounded-full bg-green-200 opacity-25"></div>

        {{-- Check animado --}}
        <div class="relative z-10">
            <div class="w-32 h-32 rounded-full bg-green-500 flex items-center justify-center shadow-xl
                        shadow-green-500/30">
                <svg viewBox="0 0 50 50" class="w-16 h-16">
                    <polyline points="10,26 20,36 40,16"
                              fill="none" stroke="white" stroke-width="4"
                              stroke-linecap="round" stroke-linejoin="round"
                              class="anim-check"/>
                </svg>
            </div>
            {{-- Glow --}}
            <div class="absolute inset-0 rounded-full bg-green-400 opacity-20 blur-xl scale-125"></div>
        </div>

        <div class="text-center z-10">
            <h2 class="text-4xl font-black text-green-700 mb-2 leading-tight">Pagamento Aprovado!</h2>
            <p class="text-gray-600 text-xl">Obrigado pelo seu pedido 😊</p>
            <p x-show="orderId" class="text-gray-400 text-sm mt-2"
               x-text="'Pedido #' + orderId"></p>
        </div>

        <div class="bg-white rounded-2xl px-8 py-5 text-center z-10"
             style="box-shadow: 0 4px 20px rgba(0,0,0,0.07); border: 1px solid rgba(0,0,0,0.05);">
            <p class="text-gray-600 font-semibold">Seu pedido está sendo preparado.</p>
            <p x-show="phone" class="text-gray-400 text-sm mt-1">
                Você receberá uma notificação no WhatsApp.
            </p>
        </div>

        <div class="text-center z-10">
            <p class="text-gray-400 text-sm">
                Voltando em
                <span class="font-bold text-gray-600" x-text="resetCountdown"></span>
                segundos...
            </p>
            <button type="button" @click="resetKiosk()"
                    class="mt-3 text-sm font-semibold underline"
                    style="color: {{ $setting->cor_primaria }};">
                Novo pedido agora
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP: ERRO
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 'erro'"
         class="h-screen flex flex-col items-center justify-center gap-7 px-8 relative overflow-hidden"
         style="background: linear-gradient(160deg, #fee2e2 0%, #fff1f1 60%, #fff 100%);">

        <div class="absolute -top-16 -right-16 w-56 h-56 rounded-full bg-red-100 opacity-60"></div>

        <div class="relative z-10">
            <div class="w-28 h-28 rounded-full bg-red-500 flex items-center justify-center shadow-xl shadow-red-500/25">
                <span class="text-5xl text-white font-black">✕</span>
            </div>
            <div class="absolute inset-0 rounded-full bg-red-400 opacity-15 blur-xl scale-125"></div>
        </div>

        <div class="text-center z-10">
            <h2 class="text-3xl font-black text-red-700 mb-2">Ops! Algo deu errado</h2>
            <p class="text-gray-600 text-base"
               x-text="errorMessage || 'Não foi possível processar o pagamento.'"></p>
        </div>

        <div class="flex flex-col gap-3 w-full max-w-sm z-10">
            <button type="button" @click="step = 'checkout'"
                    class="w-full py-5 rounded-2xl font-black text-lg text-white
                           active:scale-95 transition-transform shadow-lg"
                    style="background-color: {{ $setting->cor_primaria }};">
                Tentar novamente
            </button>
            <button type="button" @click="resetKiosk()"
                    class="w-full py-4 rounded-2xl font-bold text-gray-600
                           border-2 border-gray-200 bg-white active:bg-gray-50">
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
        cpf: '',
        cpfDigits: [],
        paymentType: 'credit_card',

        // Pagamento
        orderId: null,
        transactionId: null,
        pollingInterval: null,
        errorMessage: '',

        // Pix
        pixQrCode: null,
        pixQrCodeBase64: null,
        pixPaymentId: null,
        pixCopiado: false,

        // Reset
        resetCountdown: {{ $setting->reset_timeout ?? 10 }},
        resetInterval: null,

        // ── Computados ───────────────────────────────────────────────────

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

        get cpfFormatado() {
            const d = this.cpfDigits;
            if (d.length === 0) return '';
            const s = d.join('');
            if (s.length <= 3)  return s;
            if (s.length <= 6)  return s.slice(0,3) + '.' + s.slice(3);
            if (s.length <= 9)  return s.slice(0,3) + '.' + s.slice(3,6) + '.' + s.slice(6);
            return s.slice(0,3) + '.' + s.slice(3,6) + '.' + s.slice(6,9) + '-' + s.slice(9);
        },

        get cpfValido() {
            return this.cpfDigits.length === 11;
        },

        // ── Mapeamento de ícones para categorias ─────────────────────────

        // Retorna a URL da primeira imagem disponível nos produtos da categoria
        getCategoryImage(categoryId) {
            const prod = this.products.find(p => p.category_id === categoryId && p.image);
            return prod ? prod.image : null;
        },

        getCategoryIcon(name) {
            const n = (name || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');

            const map = [
                [['bebida','drink','suco','agua','refri','cerveja','vinho','cha','bar','limon'], '🥤'],
                [['cafe','cafeteria','cappucc','espresso'], '☕'],
                [['lanche','sanduiche','hamburguer','burger','x-burguer','hot dog'], '🍔'],
                [['pizza','calzone'], '🍕'],
                [['salada','vegetal','legume'], '🥗'],
                [['sobremesa','doce','confeit','torta','bolo','pudim','mousse'], '🍰'],
                [['sorvete','gelato','picoleto','sundae'], '🍦'],
                [['acai'], '🫐'],
                [['frango','aves','galinha','frango grelhado'], '🍗'],
                [['carne','churrasco','bife','picanha','costela','contra'], '🥩'],
                [['peixe','fruto do mar','camarao','salmao'], '🐟'],
                [['sushi','temaki','japones','oriental'], '🍱'],
                [['vegano','natural','organico','saudavel','fit'], '🌱'],
                [['massa','macarrao','lasanha','penne','fetuccini'], '🍝'],
                [['sopa','caldo','cozido'], '🍲'],
                [['pao','padaria','croissant','brioche'], '🥐'],
                [['tapioca','crepe','panqueca'], '🥞'],
                [['porcao','aperitivo','petisco','entrada','tira gosto'], '🫕'],
                [['combo','kit','menu executivo'], '🎁'],
                [['promocao','especial','destaque','oferta'], '⭐'],
                [['infantil','kids','crianca'], '🧒'],
                [['almoco','jantar','refeicao','prato'], '🍽️'],
                [['marmita','quentinha'], '🫙'],
            ];

            for (const [keywords, icon] of map) {
                if (keywords.some(k => n.includes(k))) return icon;
            }
            return '🍽️';
        },

        // ── Init ──────────────────────────────────────────────────────────

        async init() {
            await this.carregarProdutos();
            this.$nextTick(() => this.initSidebarCarousel());
        },

        initSidebarCarousel() {
            const sidebar = document.getElementById('cat-sidebar');
            if (!sidebar) return;

            // Só ativa carousel se houver overflow
            if (sidebar.scrollHeight <= sidebar.clientHeight) return;

            let speed    = 0.6;   // px por frame
            let paused   = false;
            let animId;

            const tick = () => {
                if (!paused) {
                    sidebar.scrollTop += speed;
                    // Ao chegar no fim, volta suavemente ao topo
                    if (sidebar.scrollTop >= sidebar.scrollHeight - sidebar.clientHeight) {
                        paused = true;
                        setTimeout(() => {
                            sidebar.scrollTo({ top: 0, behavior: 'smooth' });
                            setTimeout(() => { paused = false; }, 900);
                        }, 600);
                    }
                }
                animId = requestAnimationFrame(tick);
            };

            // Pausa ao interagir
            sidebar.addEventListener('touchstart', () => { paused = true; });
            sidebar.addEventListener('touchend',   () => { setTimeout(() => { paused = false; }, 1500); });
            sidebar.addEventListener('mouseenter', () => { paused = true; });
            sidebar.addEventListener('mouseleave', () => { paused = false; });

            animId = requestAnimationFrame(tick);
        },

        async carregarProdutos() {
            this.loading = true;
            try {
                const data = await fetch('/api/kiosk/produtos', {
                    headers: { 'Accept': 'application/json' }
                }).then(r => r.json());

                this.categories = data.categories || [];
                this.products   = data.products   || [];

                const qtds = {};
                this.products.forEach(p => { qtds[p.id] = 0; });
                this.quantities = qtds;
            } catch (e) {
                console.error('[Kiosk] Erro ao carregar produtos', e);
            } finally {
                this.loading = false;
            }
        },

        // ── Splash ────────────────────────────────────────────────────────

        selectMode(value) {
            this.transitioning = true;
            this.mode = value;
            setTimeout(() => {
                this.step = 'menu';
                this.transitioning = false;
            }, 600);
        },

        // ── Carrinho ──────────────────────────────────────────────────────

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

        // ── Modal de produto ──────────────────────────────────────────────

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

        // ── CPF ───────────────────────────────────────────────────────────

        cpfPressDigit(d) {
            if (this.cpfDigits.length < 11) this.cpfDigits.push(d);
        },

        cpfBackspace() {
            this.cpfDigits.pop();
        },

        irParaCpfOuPagar() {
            if (this.nfe) {
                this.cpfDigits = [];
                this.step = 'cpf';
            } else {
                this.enviarPedido();
            }
        },

        confirmarCpf() {
            if (!this.cpfValido) return;
            this.cpf = this.cpfDigits.join('');
            this.enviarPedido();
        },

        pularCpf() {
            this.cpf = '';
            this.enviarPedido();
        },

        resetQuantidades() {
            const qtds = {};
            this.products.forEach(p => { qtds[p.id] = 0; });
            this.quantities = qtds;
        },

        // ── Helpers ───────────────────────────────────────────────────────

        csrfToken() {
            const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
            return match ? decodeURIComponent(match[1]) : '';
        },

        // ── Pedido / Pagamento ────────────────────────────────────────────

        async enviarPedido() {
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
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({
                        items,
                        mode:         this.mode,
                        phone:        this.phone,
                        nfe:          this.nfe,
                        cpf:          this.cpf,
                        payment_type: this.paymentType,
                    }),
                });

                const data = await response.json();

                if (data.status === 'success') {
                    // Modo bypass — pagamento aprovado instantaneamente
                    this.orderId = data.order_id;
                    this.step = 'sucesso';
                    this.iniciarContadorReset();
                } else if (data.status === 'created' && data.transaction_id) {
                    this.orderId       = data.order_id;
                    this.transactionId = data.transaction_id;
                    this.iniciarPolling();
                } else if (data.status === 'pix_created' && data.payment_id) {
                    this.orderId         = data.order_id;
                    this.pixPaymentId    = data.payment_id;
                    this.pixQrCode       = data.qr_code;
                    this.pixQrCodeBase64 = data.qr_code_base64;
                    // Permanece em 'pagando' e exibe o QR Code nessa mesma tela
                    this.iniciarPollingPix();
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
                } catch (e) {
                    // Erro de rede → continua tentando
                }
            }, 3000);
        },

        iniciarPollingPix() {
            this.pollingInterval = setInterval(async () => {
                try {
                    const data = await fetch(`/api/kiosk/pix-status/${this.pixPaymentId}`, {
                        headers: { 'Accept': 'application/json' }
                    }).then(r => r.json());

                    if (data.status === 'success') {
                        clearInterval(this.pollingInterval);
                        this.step = 'sucesso';
                        this.iniciarContadorReset();
                    } else if (data.status === 'error') {
                        clearInterval(this.pollingInterval);
                        this.errorMessage = data.message || 'Pix não confirmado.';
                        this.step = 'erro';
                    }
                } catch (e) {
                    // Erro de rede → continua tentando
                }
            }, 3000);
        },

        async simularPagamento() {
            if (! this.orderId) {
                alert('Nenhum pedido pendente para simular.');
                return;
            }

            // Para o polling do Mercado Pago
            clearInterval(this.pollingInterval);

            try {
                const data = await fetch('/api/kiosk/simular-pagamento', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ order_id: this.orderId }),
                }).then(r => r.json());

                if (data.status === 'success') {
                    this.step = 'sucesso';
                    this.iniciarContadorReset();
                } else {
                    this.errorMessage = data.message || 'Erro na simulação.';
                    this.step = 'erro';
                }
            } catch (e) {
                this.errorMessage = 'Erro de conexão ao simular pagamento.';
                this.step = 'erro';
            }
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

            this.step            = 'splash';
            this.mode            = null;
            this.phone           = '';
            this.nfe             = false;
            this.cpf             = '';
            this.cpfDigits       = [];
            this.paymentType     = 'credit_card';
            this.orderId         = null;
            this.transactionId   = null;
            this.errorMessage    = '';
            this.showCart        = false;
            this.resetCountdown  = {{ $setting->reset_timeout ?? 10 }};
            this.pixQrCode       = null;
            this.pixQrCodeBase64 = null;
            this.pixPaymentId    = null;
            this.pixCopiado      = false;

            this.resetQuantidades();
        },
    };
}
</script>
@endsection
