<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BusinessPlan;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Mail\PaymentConfirmedMail;
use App\Mail\RecurringPaymentConfirmedMail;
use App\Models\Appointment;
use App\Models\Business;
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

        $type = $event['type'] ?? null;

        /** @var array<string, mixed> $session */
        $session = $event['data']['object'] ?? [];

        if ($type === 'customer.subscription.updated' || $type === 'customer.subscription.deleted') {
            $this->syncSubscriptionStatus($session);

            return response()->json(['received' => true]);
        }

        if ($type === 'checkout.session.completed' && ($session['mode'] ?? null) === 'subscription') {
            $this->activateSubscription($session);

            return response()->json(['received' => true]);
        }

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

        $paymentIntentId = $session['payment_intent'] ?? null;

        match ($type) {
            'checkout.session.completed' => $this->markAsPaid($appointments, is_string($paymentIntentId) ? $paymentIntentId : null),
            'checkout.session.expired' => $this->releaseUnpaidSlots($appointments),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function activateSubscription(array $session): void
    {
        $businessId = $session['metadata']['business_id'] ?? null;
        $customerId = $session['customer'] ?? null;
        $subscriptionId = $session['subscription'] ?? null;

        if (! is_string($businessId) || ! is_string($customerId) || ! is_string($subscriptionId)) {
            return;
        }

        Business::query()->whereKey($businessId)->update([
            'plan' => BusinessPlan::Pro->value,
            'stripe_customer_id' => $customerId,
            'stripe_subscription_id' => $subscriptionId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    private function syncSubscriptionStatus(array $subscription): void
    {
        $subscriptionId = $subscription['id'] ?? null;

        if (! is_string($subscriptionId)) {
            return;
        }

        $isActive = in_array($subscription['status'] ?? null, ['active', 'trialing'], true);

        Business::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->update(['plan' => $isActive ? BusinessPlan::Pro->value : BusinessPlan::Free->value]);
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     */
    private function markAsPaid(Collection $appointments, ?string $paymentIntentId): void
    {
        $unpaid = $appointments->filter(fn (Appointment $appointment) => $appointment->payment_status === PaymentStatus::Pending);

        if ($unpaid->isEmpty()) {
            return;
        }

        Appointment::query()
            ->whereIn('id', $unpaid->pluck('id'))
            ->update([
                'payment_status' => PaymentStatus::Paid,
                'stripe_payment_intent_id' => $paymentIntentId,
            ]);

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
