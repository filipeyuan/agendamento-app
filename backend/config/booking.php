<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Horário de funcionamento
    |--------------------------------------------------------------------------
    |
    | Usado pra gerar os horários candidatos ao montar a agenda de um dia.
    | MVP: um único horário de funcionamento para todo o negócio (sem
    | múltiplos prestadores).
    |
    */

    'business_hours' => [
        'start' => env('BOOKING_HOURS_START', '09:00'),
        'end' => env('BOOKING_HOURS_END', '18:00'),
    ],

    'slot_interval_minutes' => (int) env('BOOKING_SLOT_INTERVAL_MINUTES', 30),

];
