<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\DocumentoLegalController;
use App\Http\Controllers\FormatoController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\ServiciosController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Página Web Pública
Route::get('/', [HomepageController::class, 'index'])->name('home');
Route::get('/servicios', [ServiciosController::class, 'index'])->name('servicios');
Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio');
Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::post('/contacto', [ContactoController::class, 'store'])->name('contacto.store');

// Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
Route::get('/registro', [RegistroController::class, 'mostrarFormulario'])->name('registro');
Route::post('/registro', [RegistroController::class, 'store'])->name('registro.store');

Route::middleware('auth')->group(function () {
    // Mi Perfil
    Route::get('/mi-perfil', [PerfilController::class, 'mostrar'])->name('perfil.mostrar');

    // Documentos legales: RH/admin ven los de cualquiera, un empleado solo los suyos
    // (el controller valida la propiedad; ver DocumentoLegalController).
    Route::get('/usuarios/{usuarioId}/documentos-legales/data', [DocumentoLegalController::class, 'index'])->name('documentos-legales.index');
    Route::get('/documentos-legales/{id}/download', [DocumentoLegalController::class, 'download'])->name('documentos-legales.download');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/kpis', [DashboardController::class, 'getKpis'])->name('dashboard.kpis');
    Route::get('/dashboard/graficos', [DashboardController::class, 'getGraficos'])->name('dashboard.graficos');

    // Asistencias
    Route::get('/asistencias/checkin', [AsistenciaController::class, 'checkinForm'])->name('asistencias.checkin.form');
    Route::post('/asistencias/checkin', [AsistenciaController::class, 'checkin'])->name('asistencias.checkin');
    Route::post('/asistencias/checkout', [AsistenciaController::class, 'checkout'])->name('asistencias.checkout');
    Route::get('/asistencias/mi-historial', [AsistenciaController::class, 'miHistorial'])->name('asistencias.historial');
    Route::get('/asistencias/ultima', [AsistenciaController::class, 'ultima'])->name('asistencias.ultima');
    Route::get('/asistencias/proyectos', [AsistenciaController::class, 'proyectos'])->name('asistencias.proyectos');
    Route::get('/asistencias/reportes', [AsistenciaController::class, 'reportes'])->name('asistencias.reportes');

    // Documentos de proyecto
    Route::get('/documentos', [DocumentoController::class, 'indexView'])->name('documentos.index.view');
    Route::get('/documentos/data', [DocumentoController::class, 'lista'])->name('documentos.lista');
    Route::post('/documentos/upload', [DocumentoController::class, 'upload'])->name('documentos.upload');
    Route::get('/documentos/{id}/download', [DocumentoController::class, 'download'])->name('documentos.download');
    Route::patch('/documentos/{id}/permisos', [DocumentoController::class, 'updatePermisos'])->name('documentos.permisos');

    // Formatos (checklists, formatos de trabajo, triggers) — librería compartida imprimible
    Route::get('/formatos', [FormatoController::class, 'indexView'])->name('formatos.index.view');
    Route::get('/formatos/data', [FormatoController::class, 'index'])->name('formatos.index');
    Route::post('/formatos/upload', [FormatoController::class, 'store'])->name('formatos.upload');
    Route::get('/formatos/{id}/ver', [FormatoController::class, 'ver'])->name('formatos.ver');
    Route::delete('/formatos/{id}', [FormatoController::class, 'destroy'])->name('formatos.destroy');

    // Nóminas (portal del técnico)
    Route::get('/nominas/mi-nomina', [NominaController::class, 'miNominaView'])->name('nominas.minomina.view');
    Route::get('/nominas/mi-nomina/data', [NominaController::class, 'miNomina'])->name('nominas.minomina');
    Route::get('/nominas/mi-nomina/pdf', [NominaController::class, 'comprobantePdf'])->name('nominas.comprobante');
    Route::get('/nominas/historico', [NominaController::class, 'historico'])->name('nominas.historico');

    // Solo RH/Admin
    Route::middleware('role:RH,admin')->group(function () {
        Route::get('/asistencias', [AsistenciaController::class, 'indexView'])->name('asistencias.index.view');
        Route::get('/asistencias/data', [AsistenciaController::class, 'index'])->name('asistencias.index');
        Route::post('/asistencias/{id}/justificar', [AsistenciaController::class, 'justificar'])->name('asistencias.justificar');

        Route::get('/nominas', [NominaController::class, 'indexView'])->name('nominas.index.view');
        Route::get('/nominas/data', [NominaController::class, 'index'])->name('nominas.index');
        Route::post('/nominas/calcular', [NominaController::class, 'calcular'])->name('nominas.calcular');
        Route::get('/nominas/reporte-contador', [NominaController::class, 'reporteContador'])->name('nominas.reporte');
        Route::get('/nominas/reporte-contador/csv', [NominaController::class, 'reporteContadorCsv'])->name('nominas.reporte.csv');

        // Cuentas auto-registradas pendientes de aprobación
        Route::get('/usuarios/pendientes', [UsuarioController::class, 'pendientesView'])->name('usuarios.pendientes.view');
        Route::get('/usuarios/pendientes/data', [UsuarioController::class, 'pendientes'])->name('usuarios.pendientes');
        Route::post('/usuarios/{id}/aprobar', [UsuarioController::class, 'aprobar'])->name('usuarios.aprobar');
        Route::post('/usuarios/{id}/rechazar', [UsuarioController::class, 'rechazar'])->name('usuarios.rechazar');

        // Documentos legales de cada empleado (IMSS, INE, RFC, etc.)
        Route::get('/usuarios/{usuarioId}/documentos-legales', [DocumentoLegalController::class, 'gestionView'])->name('documentos-legales.gestion');
        Route::post('/usuarios/{usuarioId}/documentos-legales', [DocumentoLegalController::class, 'store'])->name('documentos-legales.store');
        Route::delete('/documentos-legales/{id}', [DocumentoLegalController::class, 'destroy'])->name('documentos-legales.destroy');
    });

    // Solo Admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/usuarios/data', [UsuarioController::class, 'index'])->name('usuarios.index.data');
        Route::resource('usuarios', UsuarioController::class)->except(['index']);
        Route::get('/usuarios', [UsuarioController::class, 'indexView'])->name('usuarios.index');
        Route::post('/usuarios/{id}/reset-password', [UsuarioController::class, 'resetPassword'])->name('usuarios.reset');
        Route::post('/usuarios/{id}/activar', [UsuarioController::class, 'activar'])->name('usuarios.activar');
    });
});
