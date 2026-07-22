<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoogleCalendarConnection;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class GoogleCalendarController extends Controller
{
    /**
     * Retorna a URL de consentimento do Google pro admin conectar a conta.
     */
    public function connect(Request $request, GoogleCalendarService $googleCalendar): JsonResponse
    {
        $this->authorize('manage', GoogleCalendarConnection::class);

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        return response()->json(['url' => $googleCalendar->getAuthUrl($user)]);
    }

    /**
     * Callback público chamado pelo Google após o consentimento do admin.
     */
    public function callback(Request $request, GoogleCalendarService $googleCalendar): RedirectResponse
    {
        /** @var array<int, string> $allowedOrigins */
        $allowedOrigins = config('cors.allowed_origins');
        $frontendUrl = $allowedOrigins[0] ?? 'http://localhost:3000';

        $code = $request->query('code');
        $state = $request->query('state');

        if (! is_string($code) || ! is_string($state)) {
            return redirect("{$frontendUrl}/admin/horarios?google=error");
        }

        try {
            $googleCalendar->handleCallback($code, $state);
        } catch (Throwable) {
            return redirect("{$frontendUrl}/admin/horarios?google=error");
        }

        return redirect("{$frontendUrl}/admin/horarios?google=connected");
    }

    /**
     * Status da conexão com o Google Calendar.
     */
    public function status(GoogleCalendarService $googleCalendar): JsonResponse
    {
        $this->authorize('viewAny', GoogleCalendarConnection::class);

        return response()->json(['connected' => $googleCalendar->isConnected()]);
    }

    /**
     * Desconecta o Google Calendar.
     */
    public function disconnect(GoogleCalendarService $googleCalendar): Response
    {
        $this->authorize('manage', GoogleCalendarConnection::class);

        $googleCalendar->disconnect();

        return response()->noContent();
    }
}
