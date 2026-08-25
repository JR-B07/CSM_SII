<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CSM Operaciones') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-900">
    <nav class="sticky top-0 z-40 bg-white border-b border-slate-200">
        <input type="checkbox" id="nav-toggle" class="peer hidden">

        <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-14">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-bold text-slate-900 shrink-0">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white text-xs font-bold">CSM</span>
                <span class="hidden xs:inline">CSM Operaciones</span>
            </a>

            {{-- Menú de escritorio --}}
            <div class="hidden lg:flex items-center gap-1 text-sm">
                <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900">Inicio</a>
                <a href="{{ route('asistencias.checkin.form') }}" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900">Asistencia</a>
                <a href="{{ route('nominas.minomina.view') }}" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900">Mi Nómina</a>
                <a href="{{ route('documentos.index.view') }}" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900">Documentos</a>
                <a href="{{ route('formatos.index.view') }}" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900">Formatos</a>
                <a href="{{ route('perfil.mostrar') }}" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900">Mi Perfil</a>
                @auth
                    @if (auth()->user()->esRH())
                        <a href="{{ route('asistencias.index.view') }}" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900">Gestionar Asistencias</a>
                        <a href="{{ route('usuarios.pendientes.view') }}" class="px-3 py-2 rounded-lg text-amber-600 hover:bg-amber-50 font-medium">Pendientes</a>
                    @endif
                    @if (auth()->user()->esAdmin())
                        <a href="{{ route('usuarios.index') }}" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900">Usuarios</a>
                    @endif

                    <div class="w-px h-6 bg-slate-200 mx-1"></div>

                    <span class="px-3 text-slate-400 text-xs">{{ auth()->user()->nombre }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 font-medium">Salir</button>
                    </form>
                @endauth
            </div>

            {{-- Botón hamburguesa (solo móvil/tablet) --}}
            <label for="nav-toggle" class="lg:hidden flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer active:bg-slate-200">
                <svg class="h-6 w-6 peer-checked:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </label>
        </div>

        {{-- Menú móvil --}}
        <div class="hidden peer-checked:block lg:hidden border-t border-slate-200 bg-white max-h-[calc(100vh-3.5rem)] overflow-y-auto">
            <div class="px-2 py-2 flex flex-col text-[15px]">
                <a href="{{ route('dashboard') }}" class="px-3 py-3 rounded-lg text-slate-700 hover:bg-slate-100 active:bg-slate-100">Inicio</a>
                <a href="{{ route('asistencias.checkin.form') }}" class="px-3 py-3 rounded-lg text-slate-700 hover:bg-slate-100 active:bg-slate-100">Asistencia</a>
                <a href="{{ route('nominas.minomina.view') }}" class="px-3 py-3 rounded-lg text-slate-700 hover:bg-slate-100 active:bg-slate-100">Mi Nómina</a>
                <a href="{{ route('documentos.index.view') }}" class="px-3 py-3 rounded-lg text-slate-700 hover:bg-slate-100 active:bg-slate-100">Documentos</a>
                <a href="{{ route('formatos.index.view') }}" class="px-3 py-3 rounded-lg text-slate-700 hover:bg-slate-100 active:bg-slate-100">Formatos</a>
                <a href="{{ route('perfil.mostrar') }}" class="px-3 py-3 rounded-lg text-slate-700 hover:bg-slate-100 active:bg-slate-100">Mi Perfil</a>
                @auth
                    @if (auth()->user()->esRH())
                        <a href="{{ route('asistencias.index.view') }}" class="px-3 py-3 rounded-lg text-slate-700 hover:bg-slate-100 active:bg-slate-100">Gestionar Asistencias</a>
                        <a href="{{ route('usuarios.pendientes.view') }}" class="px-3 py-3 rounded-lg text-amber-600 font-medium hover:bg-amber-50">Pendientes</a>
                    @endif
                    @if (auth()->user()->esAdmin())
                        <a href="{{ route('usuarios.index') }}" class="px-3 py-3 rounded-lg text-slate-700 hover:bg-slate-100 active:bg-slate-100">Usuarios</a>
                    @endif

                    <div class="h-px bg-slate-200 my-2"></div>

                    <div class="px-3 py-2 text-slate-400 text-xs">Sesión: {{ auth()->user()->nombre }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-3 rounded-lg text-red-600 font-medium hover:bg-red-50">Salir</button>
                    </form>
                @endauth
            </div>
        </div>
    </nav>

    <main class="pb-safe">
        @yield('content')
    </main>
</body>

</html>
