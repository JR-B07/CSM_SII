<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CSM Operaciones') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-900 flex items-center justify-center p-4 sm:p-6">
    <div class="w-full max-w-sm">
        <div class="flex justify-center mb-6">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white text-sm font-bold shadow-lg">CSM</span>
        </div>
        @yield('content')
        <p class="text-center text-slate-500 text-xs mt-6">CSM Operaciones &middot; San Luis de la Paz, Guanajuato</p>
    </div>
</body>
</html>
