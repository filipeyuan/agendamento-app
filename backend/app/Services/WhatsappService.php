<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsappService
{
    private const API = 'https://api.twilio.com/2010-04-01';

    /**
     * Envia o lembrete do agendamento por WhatsApp. Falha silenciosamente (loga um aviso)
     * se o Twilio não estiver configurado, se o cliente não tiver telefone cadastrado, ou
     * se o envio falhar, do mesmo jeito que o envio de e-mail nunca derruba a requisição.
     */
    public function sendAppointmentReminder(Appointment $appointment): void
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $from = config('services.twilio.whatsapp_from');

        if (! $accountSid || ! $authToken || ! $from) {
            return;
        }

        $to = $this->formatPhoneNumber($appointment->user->phone);

        if (! $to) {
            return;
        }

        $when = $appointment->start_at->format('d/m/Y').' às '.$appointment->start_at->format('H:i');
        $message = "Lembrete Zelo: seu agendamento de {$appointment->service->name} é {$when}.";

        try {
            $response = Http::asForm()
                ->withBasicAuth($accountSid, $authToken)
                ->post(self::API."/Accounts/{$accountSid}/Messages.json", [
                    'From' => "whatsapp:{$from}",
                    'To' => "whatsapp:{$to}",
                    'Body' => $message,
                ]);

            if ($response->failed()) {
                Log::warning('Falha ao enviar lembrete via WhatsApp.', [
                    'appointment_id' => $appointment->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar lembrete via WhatsApp.', [
                'appointment_id' => $appointment->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Normaliza o telefone cadastrado (formato livre) pro padrão E.164 que o WhatsApp
     * exige. Assume Brasil (+55) quando o número não já vem com código de país.
     */
    private function formatPhoneNumber(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (! str_starts_with($digits, '55')) {
            $digits = '55'.$digits;
        }

        return '+'.$digits;
    }
}
