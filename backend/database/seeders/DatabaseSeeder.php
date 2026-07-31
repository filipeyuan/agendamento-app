<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Mesmo negócio criado pela migração de backfill em produção (2026_07_30_182301),
        // pra ficar consistente entre um banco novo e um banco já existente.
        $business = Business::firstOrCreate(
            ['slug' => 'negocio-padrao'],
            ['name' => 'Negócio Padrão']
        );

        for ($dayOfWeek = 0; $dayOfWeek <= 6; $dayOfWeek++) {
            BusinessHour::firstOrCreate(
                ['business_id' => $business->id, 'day_of_week' => $dayOfWeek],
                [
                    'is_open' => true,
                    'start_time' => config('booking.business_hours.start'),
                    'end_time' => config('booking.business_hours.end'),
                ]
            );
        }

        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@agendamento.app')],
            [
                'name' => 'Administrador',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'admin12345')),
                'role' => UserRole::Admin,
                'business_id' => $business->id,
            ]
        );

        $services = [
            ['name' => 'Corte de cabelo', 'description' => 'Corte tradicional', 'duration_minutes' => 30, 'price' => 40],
            ['name' => 'Barba', 'description' => 'Aparar e desenhar a barba', 'duration_minutes' => 30, 'price' => 30],
            ['name' => 'Corte + Barba', 'description' => 'Combo completo', 'duration_minutes' => 60, 'price' => 60],
            ['name' => 'Coloração', 'description' => 'Coloração completa', 'duration_minutes' => 90, 'price' => 120],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(
                ['business_id' => $business->id, 'name' => $service['name']],
                [...$service, 'created_by' => $admin->id, 'business_id' => $business->id]
            );
        }

        $this->call(DemoAppointmentSeeder::class);
    }
}
