<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración de Nómina
    |--------------------------------------------------------------------------
    |
    | porcentaje_retardo: descuento aplicado por cada día con estado "tarde",
    | expresado como fracción del salario diario (0.10 a 0.50 según spec).
    |
    */
    'nomina' => [
        'porcentaje_retardo' => (float) env('NOMINA_PORCENTAJE_RETARDO', 0.5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Asistencias
    |--------------------------------------------------------------------------
    |
    | hora_limite: a partir de esta hora el check-in se marca como "tarde".
    |
    */
    'asistencia' => [
        'hora_limite' => env('ASISTENCIA_HORA_LIMITE', '09:15:00'),
    ],

];
