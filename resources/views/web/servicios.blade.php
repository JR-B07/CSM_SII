<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicios — CSM Operaciones</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-900">
    <div class="container-page max-w-4xl">
        <a href="{{ route('home') }}" class="btn-link text-sm">&larr; Volver al inicio</a>
        <h1 class="text-2xl sm:text-3xl font-bold mt-4 mb-8 text-slate-900">Nuestros servicios</h1>

        <div class="space-y-4">
            <div class="card card-body">
                <h2 class="text-lg font-semibold mb-1.5 text-slate-900">Supervisión EHS</h2>
                <p class="text-slate-600">Seguridad, salud ocupacional y cumplimiento ambiental en cada proyecto industrial.</p>
            </div>
            <div class="card card-body">
                <h2 class="text-lg font-semibold mb-1.5 text-slate-900">Sistemas contra incendios</h2>
                <p class="text-slate-600">Diseño, instalación, mantenimiento y certificación de sistemas de protección contra incendios.</p>
            </div>
            <div class="card card-body">
                <h2 class="text-lg font-semibold mb-1.5 text-slate-900">Electromecánica</h2>
                <p class="text-slate-600">Instalación y mantenimiento de sistemas eléctricos y mecánicos industriales.</p>
            </div>
            <div class="card card-body">
                <h2 class="text-lg font-semibold mb-1.5 text-slate-900">Gestión de proyectos</h2>
                <p class="text-slate-600">Planeación, ejecución y control integral de proyectos para clientes industriales.</p>
            </div>
        </div>
    </div>
</body>
</html>
