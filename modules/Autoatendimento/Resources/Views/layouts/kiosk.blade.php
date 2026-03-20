<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $setting->titulo ?? 'Autoatendimento' }}</title>

    {{-- Google Fonts: Outfit --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Font Awesome 7 Pro --}}
    @vite(['resources/scss/fontawesome.scss'])

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <style>
        :root {
            --cor: {{ $setting->cor_primaria ?? '#583f32' }};
        }

        * { box-sizing: border-box; }

        [x-cloak] { display: none !important; }

        body {
            font-family: 'Outfit', sans-serif;
            font-optical-sizing: auto;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            overscroll-behavior: none;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }

        /* Scrollbars ocultas */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Bottom sheet */
        .sheet-enter-active .sheet-scrim,
        .sheet-leave-active .sheet-scrim {
            transition: opacity 0.3s ease;
        }
        .sheet-enter-from .sheet-scrim,
        .sheet-leave-to   .sheet-scrim { opacity: 0; }
        .sheet-enter-active .sheet-panel,
        .sheet-leave-active .sheet-panel {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sheet-enter-from .sheet-panel,
        .sheet-leave-to   .sheet-panel { transform: translateY(100%); }

        /* Splash fade */
        .splash-fade-enter-active {
            animation: fade-in 0.55s ease-out forwards;
        }
        .splash-fade-leave-active { display: none; }
        @keyframes fade-in {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* Badge pop */
        .badge-pop-enter-active {
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .badge-pop-leave-active { transition: all 0.15s ease; }
        .badge-pop-enter-from,
        .badge-pop-leave-to { transform: scale(0); opacity: 0; }

        /* Spin */
        @keyframes spin-slow {
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spin-slow 1.4s linear infinite;
        }

        /* Pulse scale */
        @keyframes pulse-scale {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.08); }
        }
        .animate-pulse-scale {
            animation: pulse-scale 1.6s ease-in-out infinite;
        }

        /* Checkmark draw */
        @keyframes draw-check {
            to { stroke-dashoffset: 0; }
        }
        .draw-check {
            stroke-dasharray: 60;
            stroke-dashoffset: 60;
            animation: draw-check 0.6s ease-out 0.3s forwards;
        }
    </style>
</head>
<body class="antialiased bg-gray-100 h-screen overflow-hidden">
    @yield('content')
</body>
</html>
