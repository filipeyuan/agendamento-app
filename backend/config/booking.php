<?php

declare(strict_types=1);

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

    /*
    |--------------------------------------------------------------------------
    | Autoatendimento do cliente
    |--------------------------------------------------------------------------
    |
    | Quantas horas de antecedência o cliente precisa ter pra cancelar ou
    | remarcar o próprio agendamento sem falar com o admin.
    |
    */

    'client_action_window_hours' => (int) env('BOOKING_CLIENT_ACTION_WINDOW_HOURS', 2),

    /*
    |--------------------------------------------------------------------------
    | Agendamento recorrente
    |--------------------------------------------------------------------------
    |
    | Máximo de ocorrências semanais que o cliente pode agendar de uma vez.
    |
    */

    'max_recurring_occurrences' => (int) env('BOOKING_MAX_RECURRING_OCCURRENCES', 12),

];
