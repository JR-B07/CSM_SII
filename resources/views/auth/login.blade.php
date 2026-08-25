@extends('layouts.guest')

@section('content')
<div class="card card-body">
    <h1 class="text-xl font-bold mb-1 text-center text-slate-900">CSM Operaciones</h1>
    <p class="text-sm text-slate-500 text-center mb-6">Inicia sesión para continuar</p>

    @if (session('status'))
        <div class="alert-success mb-4">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-error mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label">Correo electrónico</label>
            <input type="email" name="email" value="{{ old('email', $emailRecordado) }}" required autofocus
                   autocomplete="email" inputmode="email" class="input">
        </div>
        <div>
            <label class="label">Contraseña</label>
            <input type="password" name="password" required autocomplete="current-password" class="input">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600 py-1">
            <input type="checkbox" name="remember" @checked($emailRecordado) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            Recordarme
        </label>
        <button type="submit" class="btn-primary w-full py-3">
            Iniciar sesión
        </button>
    </form>

    <p class="text-sm text-center text-slate-500 mt-5">
        <a href="{{ route('registro') }}" class="btn-link">Crear cuenta nueva</a>
    </p>
</div>
@endsection
