<?php

namespace App\Services;

use App\Models\Nomina;
use App\Models\Usuario;
use Illuminate\Support\Facades\Mail;

/**
 * Envío de notificaciones por correo (formulario de contacto, avisos de
 * nómina, etc). Usa el MAIL_MAILER configurado en .env (log en desarrollo,
 * SendGrid/Mailtrap en producción).
 */
class EmailService
{
    private const NOMBRES_MES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public function enviarContacto(string $nombre, string $email, string $mensaje): void
    {
        Mail::raw(
            "Nuevo mensaje de contacto de {$nombre} ({$email}):\n\n{$mensaje}",
            function ($message) use ($email, $nombre) {
                $message->to(config('mail.from.address'))
                    ->replyTo($email, $nombre)
                    ->subject('Nuevo mensaje de contacto — CSM Operaciones');
            }
        );
    }

    /**
     * Avisa al técnico que su nómina ya está calculada. Por seguridad no se
     * incluye el monto en el correo (viaja encriptado en BD); el técnico lo
     * consulta autenticado en el portal.
     */
    public function enviarNominaLista(Usuario $usuario, Nomina $nomina): void
    {
        $periodo = (self::NOMBRES_MES[$nomina->mes] ?? $nomina->mes).' '.$nomina->anio;

        Mail::raw(
            "Hola {$usuario->nombre},\n\n".
            "Tu nómina de {$periodo} ya está calculada y disponible en el portal de CSM Operaciones.\n".
            "Ingresa a tu cuenta y consulta la sección \"Mi Nómina\" para ver el detalle y descargar tu comprobante.\n\n".
            "CSM Operaciones",
            function ($message) use ($usuario, $periodo) {
                $message->to($usuario->email)
                    ->subject("Tu nómina de {$periodo} ya está lista");
            }
        );
    }

    /**
     * Avisa a todos los admins que hay una cuenta nueva esperando aprobación.
     */
    public function enviarNuevoRegistro(Usuario $nuevo): void
    {
        $admins = Usuario::where('rol', 'admin')->where('activo', true)->pluck('email');

        if ($admins->isEmpty()) {
            return;
        }

        Mail::raw(
            "Se registró una cuenta nueva en el portal de CSM Operaciones:\n\n".
            "Nombre: {$nuevo->nombre}\n".
            "Correo: {$nuevo->email}\n\n".
            "Ingresa a Usuarios > Pendientes de aprobación para revisarla.",
            function ($message) use ($admins) {
                $message->to($admins->all())
                    ->subject('Nueva cuenta pendiente de aprobación — CSM Operaciones');
            }
        );
    }

    public function enviarCuentaAprobada(Usuario $usuario): void
    {
        Mail::raw(
            "Hola {$usuario->nombre},\n\n".
            "Tu cuenta en el portal de CSM Operaciones fue aprobada. Ya puedes iniciar sesión con tu correo y contraseña.\n\n".
            "CSM Operaciones",
            function ($message) use ($usuario) {
                $message->to($usuario->email)->subject('Tu cuenta fue aprobada — CSM Operaciones');
            }
        );
    }

    public function enviarCuentaRechazada(Usuario $usuario, ?string $motivo = null): void
    {
        $texto = "Hola {$usuario->nombre},\n\nTu solicitud de cuenta en el portal de CSM Operaciones fue rechazada.";
        if ($motivo) {
            $texto .= "\n\nMotivo: {$motivo}";
        }
        $texto .= "\n\nSi crees que esto es un error, contacta a Recursos Humanos.\n\nCSM Operaciones";

        Mail::raw($texto, function ($message) use ($usuario) {
            $message->to($usuario->email)->subject('Tu solicitud de cuenta fue rechazada — CSM Operaciones');
        });
    }
}
