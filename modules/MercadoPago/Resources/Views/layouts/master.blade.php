<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Mercado Pago')</title>
    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white">
<main class="p-6 min-h-screen">
    @yield('content')
</main>
</body>
</html>
