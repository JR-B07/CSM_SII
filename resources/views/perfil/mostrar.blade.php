@extends('layouts.app')

@section('content')
<div class="container-page">
    <div class="page-header">
        <h1 class="page-title">Mi Perfil</h1>
    </div>

    <div class="card card-body space-y-3 mb-8">
        <div class="flex justify-between">
            <span class="text-slate-500">Nombre</span>
            <span class="font-medium text-slate-900">{{ $usuario->nombre }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500">Correo</span>
            <span class="font-medium text-slate-900">{{ $usuario->email }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500">Rol</span>
            <span class="badge-blue">{{ $usuario->rol }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500">Departamento</span>
            <span class="font-medium text-slate-900">{{ $usuario->departamento ?? '—' }}</span>
        </div>
    </div>

    <h2 class="text-lg font-bold mb-2 text-slate-900">Mis documentos legales</h2>
    <p class="text-sm text-slate-500 mb-4">
        Estos documentos los sube y administra Recursos Humanos. Aquí solo puedes consultarlos.
    </p>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Número</th>
                    <th>Archivo</th>
                    <th>Vence</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documentos as $documento)
                    <tr>
                        <td>{{ $documento->tipo }}</td>
                        <td>{{ $documento->numero_documento ?? '—' }}</td>
                        <td>
                            <a href="{{ route('documentos-legales.download', $documento) }}" class="btn-link">
                                {{ $documento->nombre_archivo }}
                            </a>
                        </td>
                        <td>{{ $documento->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-slate-400 py-6">Aún no tienes documentos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
