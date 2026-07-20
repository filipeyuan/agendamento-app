<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@agendamento.app')],
            [
                'name' => 'Administrador',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'admin12345')),
                'role' => UserRole::Admin,
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
                ['name' => $service['name']],
                [...$service, 'created_by' => $admin->id]
            );
        }
    }
}
