<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // O Render (e a maioria dos PaaS) termina o HTTPS na borda e repassa a
        // conexão como HTTP puro pro container. Sem confiar nesse proxy, o
        // Laravel acha que a conexão é HTTP e gera URLs absolutas erradas.
        $middleware->trustProxies(at: '*');

        // Laravel 11+ não aplica throttle nas rotas de API por padrão, então sem isso
        // toda rota (login, cadastro, assistente de IA) fica sem limite de requisições.
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
