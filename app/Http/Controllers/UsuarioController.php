<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function __construct(protected EmailService $emailService) {}

    public function indexView(): View
    {
        return view('usuarios.index');
    }

    /**
     * Solo cuentas aprobadas. Las pendientes/rechazadas no deben mezclarse
     * aquí como si fueran empleados dados de alta normalmente — viven en
     * /usuarios/pendientes hasta que un admin decide su estado.
     */
    public function index(): JsonResponse
    {
        // Sin controles de "siguiente página" en la vista, un límite bajo
        // deja usuarios invisibles sin avisar. 200 cubre de sobra el
        // tamaño real de la empresa (14 empleados) más años de crecimiento.
        return response()->json(
            Usuario::where('estado_cuenta', 'aprobado')->orderBy('nombre')->paginate(200)
        );
    }

    public function create(): View
    {
        return view('usuarios.form', ['usuario' => new Usuario()]);
    }

    /**
     * El formulario de alta (usuarios/form.blade.php) es un <form> HTML
     * normal, no una llamada por axios: por eso responde con una redirección
     * y un mensaje flash, no con JSON (si no, el navegador solo mostraba el
     * JSON crudo en pantalla y el usuario nunca volvía al listado).
     */
    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarDatos($request);
        $datos['password'] ??= Str::random(12);

        Usuario::create($datos + [
            'activo' => true,
            'estado_cuenta' => 'aprobado',
            'aprobado_por' => Auth::id(),
            'aprobado_en' => now(),
        ]);

        return redirect()->route('usuarios.index')->with('status', 'Usuario creado exitosamente.');
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(Usuario::findOrFail($id));
    }

    public function edit(int $id): View
    {
        return view('usuarios.form', ['usuario' => Usuario::findOrFail($id)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $usuario = Usuario::findOrFail($id);
        $datos = $this->validarDatos($request, $usuario->id);

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('status', 'Usuario actualizado exitosamente.');
    }

    /**
     * No se elimina físicamente al usuario (cascadearía sus asistencias y
     * nóminas, violando la retención legal de 7 años). Se desactiva.
     */
    public function destroy(int $id): JsonResponse
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->update(['activo' => false]);

        return response()->json(['message' => 'Usuario desactivado correctamente.']);
    }

    public function activar(int $id): JsonResponse
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->update(['activo' => true]);

        return response()->json(['message' => 'Usuario reactivado correctamente.', 'usuario' => $usuario]);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $request->validate(['password' => ['required', 'string', 'min:8']]);

        $usuario = Usuario::findOrFail($id);
        $usuario->update(['password' => $request->input('password')]);

        return response()->json(['message' => 'Contraseña restablecida correctamente.']);
    }

    public function pendientesView(): View
    {
        return view('usuarios.pendientes');
    }

    public function pendientes(): JsonResponse
    {
        return response()->json([
            'usuarios' => Usuario::where('estado_cuenta', 'pendiente')->orderBy('created_at')->get(),
        ]);
    }

    /**
     * El admin define aquí el rol/departamento/salario_diario del nuevo
     * empleado, ya que el formulario de auto-registro nunca los captura.
     */
    public function aprobar(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::where('estado_cuenta', 'pendiente')->findOrFail($id);

        $datos = $request->validate([
            'rol' => ['required', Rule::in(['rescatista', 'supervisor', 'RH', 'admin', 'contador'])],
            'departamento' => ['nullable', 'string', 'max:50'],
            'salario_diario' => ['required', 'numeric', 'min:0'],
        ]);

        $usuario->update($datos + [
            'activo' => true,
            'estado_cuenta' => 'aprobado',
            'aprobado_por' => Auth::id(),
            'aprobado_en' => now(),
        ]);

        $this->emailService->enviarCuentaAprobada($usuario);

        return response()->json(['message' => 'Usuario aprobado correctamente.', 'usuario' => $usuario]);
    }

    public function rechazar(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::where('estado_cuenta', 'pendiente')->findOrFail($id);

        $motivo = $request->validate(['motivo' => ['nullable', 'string', 'max:255']])['motivo'] ?? null;

        $usuario->update([
            'estado_cuenta' => 'rechazado',
            'activo' => false,
            'aprobado_por' => Auth::id(),
            'aprobado_en' => now(),
        ]);

        $this->emailService->enviarCuentaRechazada($usuario, $motivo);

        return response()->json(['message' => 'Solicitud rechazada correctamente.']);
    }

    private function validarDatos(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('usuarios', 'email')->ignore($ignorarId)],
            'rol' => ['required', Rule::in(['rescatista', 'supervisor', 'RH', 'admin', 'contador'])],
            'departamento' => ['nullable', 'string', 'max:50'],
            'salario_diario' => ['required', 'numeric', 'min:0'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
    }
}
