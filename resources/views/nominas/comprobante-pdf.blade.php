<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .subtitulo { color: #6b7280; margin-top: 2px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td, th { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { color: #6b7280; font-weight: normal; width: 50%; }
        .descuento { color: #b91c1c; }
        .total-row td { font-size: 16px; font-weight: bold; border-top: 2px solid #1f2937; border-bottom: none; padding-top: 12px; }
        .footer { margin-top: 40px; font-size: 10px; color: #9ca3af; }
    </style>
</head>
<body>
    <h1>CSM Operaciones</h1>
    <p class="subtitulo">Comprobante de nómina — {{ $nomina->mes }}/{{ $nomina->anio }}</p>

    <table>
        <tr><th>Empleado</th><td>{{ $nomina->usuario->nombre }}</td></tr>
        <tr><th>Departamento</th><td>{{ $nomina->usuario->departamento }}</td></tr>
        <tr><th>Salario diario</th><td>${{ number_format((float) $nomina->salario_diario, 2) }}</td></tr>
        <tr><th>Días laborales del periodo</th><td>{{ $nomina->dias_laborales }}</td></tr>
        <tr><th>Días asistidos</th><td>{{ $nomina->dias_asistidos }}</td></tr>
        <tr><th>Descuento por faltas</th><td class="descuento">- ${{ number_format((float) $nomina->descuentos_faltas, 2) }}</td></tr>
        <tr><th>Descuento por retardos</th><td class="descuento">- ${{ number_format((float) $nomina->descuentos_retardos, 2) }}</td></tr>
        <tr class="total-row"><td>Total a pagar</td><td>${{ number_format((float) $nomina->total_pago, 2) }}</td></tr>
    </table>

    <p class="footer">
        Documento generado automáticamente por el sistema de nómina de CSM Operaciones el {{ now()->format('d/m/Y H:i') }}.
        Estado: {{ $nomina->estado }}.
    </p>
</body>
</html>
