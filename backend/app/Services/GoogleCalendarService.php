<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleCalendarService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const CALENDAR_API = 'https://www.googleapis.com/calendar/v3';

    private const STATE_CACHE_PREFIX = 'google-calendar-oauth-state:';

    /**
     * Gera a URL de consentimento do Google e guarda o "state" (CSRF + quem iniciou o fluxo).
     */
    public function getAuthUrl(User $admin): string
    {
        $state = Str::random(40);
        Cache::put(self::STATE_CACHE_PREFIX.$state, $admin->id, now()->addMinutes(10));

        $params = [
            'client_id' => config('services.google_calendar.client_id'),
            'redirect_uri' => config('services.google_calendar.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        return self::AUTH_URL.'?'.http_build_query($params);
    }

    /**
     * Troca o código do callback por tokens e salva a conexão.
     */
    public function handleCallback(string $code, string $state): void
    {
        $connectedBy = Cache::pull(self::STATE_CACHE_PREFIX.$state);

        if (! $connectedBy) {
            throw new RuntimeException('Sessão de conexão com o Google Calendar expirou. Tente de novo.');
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.google_calendar.client_id'),
            'client_secret' => config('services.google_calendar.client_secret'),
            'redirect_uri' => config('services.google_calendar.redirect_uri'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            Log::warning('Falha ao trocar o código do Google Calendar por tokens.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Não foi possível conectar ao Google Calendar.');
        }

        GoogleCalendarConnection::query()->delete();

        GoogleCalendarConnection::create([
            'access_token' => $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token'),
            'expires_at' => now()->addSeconds((int) $response->json('expires_in')),
            'connected_by' => $connectedBy,
        ]);
    }

    public function isConnected(): bool
    {
        return GoogleCalendarConnection::query()->exists();
    }

    public function disconnect(): void
    {
        GoogleCalendarConnection::query()->delete();
    }

    /**
     * Cria um evento no Google Calendar pro agendamento confirmado.
     */
    public function createEvent(Appointment $appointment): ?string
    {
        $connection = GoogleCalendarConnection::query()->first();

        if (! $connection) {
            return null;
        }

        $token = $this->freshAccessToken($connection);

        $response = Http::withToken($token)->post(
            self::CALENDAR_API."/calendars/{$connection->calendar_id}/events",
            [
                'summary' => "{$appointment->service->name} · {$appointment->user->name}",
                'description' => $appointment->notes,
                'start' => ['dateTime' => $appointment->start_at->toIso8601String()],
                'end' => ['dateTime' => $appointment->end_at->toIso8601String()],
            ]
        );

        if ($response->failed()) {
            Log::warning('Falha ao criar evento no Google Calendar.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('id');
    }

    /**
     * Remove o evento do Google Calendar (ex: agendamento cancelado).
     */
    public function deleteEvent(string $eventId): void
    {
        $connection = GoogleCalendarConnection::query()->first();

        if (! $connection) {
            return;
        }

        $token = $this->freshAccessToken($connection);

        $response = Http::withToken($token)->delete(
            self::CALENDAR_API."/calendars/{$connection->calendar_id}/events/{$eventId}"
        );

        if ($response->failed() && $response->status() !== 410) {
            Log::warning('Falha ao remover evento do Google Calendar.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    /**
     * Intervalos ocupados na agenda pessoal do Google Calendar conectado.
     *
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    public function getBusyRanges(Carbon $start, Carbon $end): array
    {
        $connection = GoogleCalendarConnection::query()->first();

        if (! $connection) {
            return [];
        }

        $token = $this->freshAccessToken($connection);

        $response = Http::withToken($token)->post(self::CALENDAR_API.'/freeBusy', [
            'timeMin' => $start->toIso8601String(),
            'timeMax' => $end->toIso8601String(),
            'items' => [['id' => $connection->calendar_id]],
        ]);

        if ($response->failed()) {
            Log::warning('Falha ao consultar disponibilidade no Google Calendar.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $busy = $response->json("calendars.{$connection->calendar_id}.busy") ?? [];

        return array_map(
            fn (array $range) => [Carbon::parse($range['start']), Carbon::parse($range['end'])],
            $busy
        );
    }

    private function freshAccessToken(GoogleCalendarConnection $connection): string
    {
        if ($connection->expires_at->isFuture()) {
            return $connection->access_token;
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.google_calendar.client_id'),
            'client_secret' => config('services.google_calendar.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::warning('Falha ao renovar o token do Google Calendar.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Falha ao renovar a conexão com o Google Calendar.');
        }

        $connection->update([
            'access_token' => $response->json('access_token'),
            'expires_at' => now()->addSeconds((int) $response->json('expires_in')),
        ]);

        return $connection->access_token;
    }
}
