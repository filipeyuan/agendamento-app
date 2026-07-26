<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Mail\PaymentConfirmedMail;
use App\Models\Appointment;
use App\Notifications\PaymentConfirmedNotification;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StripeWebhookController extends Controller
{
    /**
     * Recebe eventos do Stripe sobre o pagamento do agendamento.
     */
    public function handle(Request $request, StripeService $stripe): JsonResponse
    {
        $signature = $request->header('Stripe-Signature');

        if (! is_string($signature)) {
            return response()->json(['message' => 'Assinatura ausente.'], 400);
        }

        $event = $stripe->verifyWebhook($request->getContent(), $signature);

        if ($event === null) {
            return response()->json(['message' => 'Assinatura inválida.'], 400);
        }

        /** @var array<string, mixed> $session */
        $session = $event['data']['object'] ?? [];
        $sessionId = $session['id'] ?? null;

        if (! is_string($sessionId)) {
            return response()->json(['received' => true]);
        }

        $appointment = Appointment::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if (! $appointment) {
            return response()->json(['received' => true]);
        }

        match ($event['type'] ?? null) {
            'checkout.session.completed' => $this->markAsPaid($appointment),
            'checkout.session.expired' => $this->releaseUnpaidSlot($appointment),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    private function markAsPaid(Appointment $appointment): void
    {
        if ($appointment->payment_status === PaymentStatus::Paid) {
            return;
        }

        $appointment->update(['payment_status' => PaymentStatus::Paid]);
        $appointment->load(['service', 'user']);

        try {
            Mail::to($appointment->user->email)->send(new PaymentConfirmedMail($appointment));
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar e-mail de pagamento confirmado.', [
                'appointment_id' => $appointment->id,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $appointment->user->notify(new PaymentConfirmedNotification($appointment));
        } catch (Throwable $e) {
            Log::warning('Falha ao criar notificação de pagamento confirmado.', [
                'appointment_id' => $appointment->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function releaseUnpaidSlot(Appointment $appointment): void
    {
        if ($appointment->payment_status === PaymentStatus::Pending) {
            $appointment->delete();
        }
    }
}
