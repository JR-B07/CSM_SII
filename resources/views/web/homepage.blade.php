<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CSM Operaciones — Servicios Técnicos Industriales</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-900">
    <header class="relative bg-slate-900 text-white overflow-hidden">
        <nav class="relative z-10 max-w-6xl mx-auto px-4 py-5 flex items-center justify-between">
            <span class="flex items-center gap-2 font-bold">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold">CSM</span>
                <span class="hidden xs:inline">CSM Operaciones</span>
            </span>
            <a href="{{ route('login') }}" class="btn-primary btn-sm">
                Portal interno
            </a>
        </nav>

        <div class="relative z-10 max-w-6xl mx-auto px-4 pt-8 pb-20 sm:pt-14 sm:pb-28 text-center">
            <h1 class="text-3xl sm:text-5xl font-bold mb-4 tracking-tight">CSM Operaciones</h1>
            <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto">
                Supervisión EHS · Sistemas contra incendios · Electromecánica · Gestión de proyectos
            </p>
            <p class="text-sm text-slate-400 mt-2">San Luis de la Paz, Guanajuato, México</p>
        </div>
    </header>

    <section class="max-w-6xl mx-auto px-4 -mt-12 sm:-mt-16 relative z-10 pb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('servicios') }}" class="card card-body hover:shadow-md hover:-translate-y-0.5 transition text-center">
                <h2 class="font-semibold text-slate-900 mb-1.5">Supervisión EHS</h2>
                <p class="text-sm text-slate-500">Seguridad, salud y medio ambiente en cada proyecto.</p>
            </a>
            <a href="{{ route('servicios') }}" class="card card-body hover:shadow-md hover:-translate-y-0.5 transition text-center">
                <h2 class="font-semibold text-slate-900 mb-1.5">Sistemas contra incendios</h2>
                <p class="text-sm text-slate-500">Diseño, instalación y mantenimiento certificado.</p>
            </a>
            <a href="{{ route('servicios') }}" class="card card-body hover:shadow-md hover:-translate-y-0.5 transition text-center">
                <h2 class="font-semibold text-slate-900 mb-1.5">Electromecánica</h2>
                <p class="text-sm text-slate-500">Instalación y mantenimiento de sistemas industriales.</p>
            </a>
            <a href="{{ route('servicios') }}" class="card card-body hover:shadow-md hover:-translate-y-0.5 transition text-center">
                <h2 class="font-semibold text-slate-900 mb-1.5">Gestión de proyectos</h2>
                <p class="text-sm text-slate-500">Planeación y ejecución integral para clientes industriales.</p>
            </a>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 py-10 flex flex-wrap gap-x-6 gap-y-2 justify-center text-sm text-slate-500">
        <a href="{{ route('servicios') }}" class="hover:text-slate-900 hover:underline">Servicios</a>
        <a href="{{ route('portfolio') }}" class="hover:text-slate-900 hover:underline">Portfolio</a>
        <a href="{{ route('blog') }}" class="hover:text-slate-900 hover:underline">Blog</a>
    </section>

    <section id="contacto" class="bg-slate-50 py-14 sm:py-16 border-t border-slate-200">
        <div class="max-w-md mx-auto px-4">
            <h2 class="text-2xl font-bold mb-1 text-center text-slate-900">Contáctanos</h2>
            <p class="text-sm text-slate-500 text-center mb-6">Te respondemos a la brevedad.</p>

            @if (session('status'))
                <div class="alert-success mb-4">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert-error mb-4">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('contacto.store') }}" class="card card-body space-y-4">
                @csrf
                <input type="text" name="nombre" value="{{ old('nombre') }}" placeholder="Nombre" required class="input">
                <input type="email" name="email" value="{{ old('email') }}" placeholder="Correo electrónico" required inputmode="email" class="input">
                <textarea name="mensaje" placeholder="Mensaje" rows="4" required class="input">{{ old('mensaje') }}</textarea>
                <button type="submit" class="btn-primary w-full">Enviar</button>
            </form>
        </div>
    </section>

    <footer class="bg-slate-900 text-center text-sm text-slate-400 py-8">
        CSM Operaciones &middot; San Luis de la Paz, Guanajuato, México
    </footer>
</body>
</html>
