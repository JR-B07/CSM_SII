@extends('layouts.guest')

@section('content')
<div class="card card-body">
    <h1 class="text-xl font-bold mb-1 text-center text-slate-900">Crear cuenta</h1>
    <p class="text-sm text-slate-500 text-center mb-6">
        Tu cuenta quedará pendiente hasta que un administrador la apruebe.
    </p>

    @if ($errors->any())
        <div class="alert-error mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('registro.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label">Nombre completo</label>
            <input type="text" name="nombre" value="{{ old('nombre') }}" required autofocus
                   autocomplete="name" class="input">
        </div>
        <div>
            <label class="label">Correo electrónico</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   autocomplete="email" inputmode="email" class="input">
        </div>
        <div>
            <label class="label">Contraseña</label>
            <input type="password" name="password" required minlength="8" autocomplete="new-password" class="input">
        </div>
        <div>
            <label class="label">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="input">
        </div>
        <button type="submit" class="btn-primary w-full py-3">
            Solicitar cuenta
        </button>
    </form>

    <p class="text-sm text-center text-slate-500 mt-5">
        <a href="{{ route('login') }}" class="btn-link">Ya tengo cuenta, iniciar sesión</a>
    </p>
</div>
@endsection
