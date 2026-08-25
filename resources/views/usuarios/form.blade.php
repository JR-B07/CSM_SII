@extends('layouts.app')

@section('content')
<div class="container-page-narrow">
    <div class="page-header">
        <h1 class="page-title">
            {{ $usuario->exists ? 'Editar usuario' : 'Nuevo usuario' }}
        </h1>
    </div>

    @if ($errors->any())
        <div class="alert-error mb-4">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-body">
        <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="space-y-4">
            @csrf
            @if ($usuario->exists) @method('PUT') @endif

            <div>
                <label class="label">Nombre</label>
                <input type="text" name="nombre" value="{{ old('nombre', $usuario->nombre) }}" required class="input">
            </div>
            <div>
                <label class="label">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required inputmode="email" class="input">
            </div>
            <div>
                <label class="label">Rol</label>
                <select name="rol" class="input">
                    @foreach (['rescatista', 'supervisor', 'RH', 'admin', 'contador'] as $rol)
                    <option value="{{ $rol }}" @selected(old('rol', $usuario->rol) === $rol)>{{ $rol }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Departamento</label>
                <input type="text" name="departamento" value="{{ old('departamento', $usuario->departamento) }}" class="input">
            </div>
            <div>
                <label class="label">Salario diario</label>
                <input type="number" step="0.01" name="salario_diario" value="{{ old('salario_diario', $usuario->salario_diario) }}" required inputmode="decimal" class="input">
            </div>
            <div>
                <label class="label">Contraseña {{ $usuario->exists ? '(dejar vacío para no cambiar)' : '' }}</label>
                <input type="password" name="password" class="input">
            </div>

            <button type="submit" class="btn-primary w-full">
                Guardar
            </button>
        </form>
    </div>

    @if ($usuario->exists)
    <a href="{{ route('documentos-legales.gestion', $usuario) }}" class="block text-center btn-link mt-4">
        Ver documentos legales (IMSS, INE, RFC…)
    </a>
    @endif
</div>
@endsection
