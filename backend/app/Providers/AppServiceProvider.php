<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // O parâmetro precisa aceitar null (mesmo sem uso) pra o Gate liberar visitantes
        // sem login; um closure sem parâmetro nenhum é tratado como "exige autenticação".
        Gate::define('viewApiDocs', fn (?User $user = null) => true);
    }
}
