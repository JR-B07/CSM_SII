<?php

return [

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
