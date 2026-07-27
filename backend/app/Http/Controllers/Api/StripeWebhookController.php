<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Mail\PaymentConfirmedMail;
use App\Mail\RecurringPaymentConfirmedMail;
use App\Models\Appointment;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\RecurringPaymentConfirmedNotification;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $appointments = Appointment::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->with(['service', 'user'])
            ->get();

        if ($appointments->isEmpty()) {
            return response()->json(['received' => true]);
        }

        match ($event['type'] ?? null) {
            'checkout.session.completed' => $this->markAsPaid($appointments),
            'checkout.session.expired' => $this->releaseUnpaidSlots($appointments),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     */
    private function markAsPaid(Collection $appointments): void
    {
        $unpaid = $appointments->filter(fn (Appointment $appointment) => $appointment->payment_status === PaymentStatus::Pending);

        if ($unpaid->isEmpty()) {
            return;
        }

        Appointment::query()
            ->whereIn('id', $unpaid->pluck('id'))
            ->update(['payment_status' => PaymentStatus::Paid]);

        /** @var Appointment $first */
        $first = $unpaid->first();

        try {
            if ($unpaid->count() > 1) {
                Mail::to($first->user->email)->send(new RecurringPaymentConfirmedMail($unpaid));
            } else {
                Mail::to($first->user->email)->send(new PaymentConfirmedMail($first));
            }
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar e-mail de pagamento confirmado.', [
                'appointment_ids' => $unpaid->pluck('id')->all(),
                'message' => $e->getMessage(),
            ]);
        }

        try {
            if ($unpaid->count() > 1) {
                $first->user->notify(new RecurringPaymentConfirmedNotification($unpaid));
            } else {
                $first->user->notify(new PaymentConfirmedNotification($first));
            }
        } catch (Throwable $e) {
            Log::warning('Falha ao criar notificação de pagamento confirmado.', [
                'appointment_ids' => $unpaid->pluck('id')->all(),
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     */
    private function releaseUnpaidSlots(Collection $appointments): void
    {
        $stillPending = $appointments->filter(fn (Appointment $appointment) => $appointment->payment_status === PaymentStatus::Pending);

        if ($stillPending->isEmpty()) {
            return;
        }

        Appointment::query()->whereIn('id', $stillPending->pluck('id'))->delete();
    }
}
