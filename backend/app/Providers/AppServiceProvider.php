<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Login/cadastro: limite baixo por IP pra dificultar força bruta. Desativado em
        // testes pra não estourar com as várias tentativas seguidas dos testes de auth.
        RateLimiter::for('auth', fn (Request $request) => app()->environment('testing')
            ? Limit::none()
            : Limit::perMinute(5)->by($request->ip()));

        // Assistente via IA custa uma chamada real ao Gemini por mensagem, então o limite
        // é mais apertado que o da API em geral pra evitar abuso.
        RateLimiter::for('assistant', fn (Request $request) => app()->environment('testing')
            ? Limit::none()
            : Limit::perMinute(15)->by($request->user()?->id ?: $request->ip()));
    }
}
