<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BusinessInvite>
 */
class BusinessInviteFactory extends Factory
{
    protected $model = BusinessInvite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'email' => fake()->unique()->safeEmail(),
            'token' => Str::random(40),
            'invited_by' => User::factory()->admin(),
            'expires_at' => now()->addDays(7),
        ];
    }
}
