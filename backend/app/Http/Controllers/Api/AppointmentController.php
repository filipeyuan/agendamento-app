<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Mail\AppointmentCancelledMail;
use App\Mail\AppointmentConfirmedMail;
use App\Mail\AppointmentRescheduledMail;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\AppointmentRescheduledNotification;
use App\Services\BookingService;
use App\Services\GoogleCalendarService;
use App\Services\StripeService;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Response as DocumentedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class AppointmentController extends Controller
{
    /**
     * Lista os agendamentos do usuário autenticado.
     */
    public function mine(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $request->validate([
            'status' => ['sometimes', Rule::enum(AppointmentStatus::class)],
            'scope' => ['sometimes', Rule::in(['upcoming', 'past', 'all'])],
        ]);

        $scope = $request->string('scope', 'upcoming')->toString();

        $appointments = $user
            ->appointments()
            ->with('service')
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($scope === 'upcoming', fn ($query) => $query->where('start_at', '>=', now()))
            ->when($scope === 'past', fn ($query) => $query->where('start_at', '<', now()))
            ->orderBy('start_at', $scope === 'past' ? 'desc' : 'asc')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    /**
     * Lista todos os agendamentos, com filtros opcionais de data (exata ou intervalo) e status.
     */
    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Appointment::class);

        $request->validate([
            'date' => ['sometimes', 'date'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::enum(AppointmentStatus::class)],
        ]);

        $appointments = Appointment::query()
            ->with(['service', 'user'])
            ->when($request->date, fn ($query, $date) => $query->whereDate('start_at', $date))
            ->when($request->from, fn ($query, $from) => $query->where('start_at', '>=', $from))
            ->when($request->to, fn ($query, $to) => $query->where('start_at', '<', $to))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('start_at')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    /**
     * Lista os horários livres de um serviço em uma data.
     */
    public function availableSlots(Request $request, Service $service, BookingService $bookingService): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $slots = $bookingService->availableSlots($service, $validated['date']);

        return response()->json([
            'slots' => array_map(fn (Carbon $slot) => $slot->toIso8601String(), $slots),
        ]);
    }

    /**
     * Cria um agendamento.
     */
    #[DocumentedResponse(
        status: 409,
        description: 'Horário conflita com um agendamento já existente.',
        type: 'array{message: string}',
        examples: [['message' => 'Esse horário acabou de ser ocupado. Escolha outro horário.']],
    )]
    public function store(StoreAppointmentRequest $request, BookingService $bookingService, StripeService $stripe): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $service = Service::query()->findOrFail($request->validated('service_id'));
        abort_if(! $service instanceof Service, 404);

        $occurrences = $request->validated('recurring_occurrences');

        if ($occurrences) {
            return $this->storeRecurring($bookingService, $stripe, $user, $service, $request, (int) $occurrences);
        }

        $appointment = $bookingService->book(
            client: $user,
            service: $service,
            startAt: Carbon::parse($request->validated('start_at')),
            notes: $request->validated('notes'),
        );

        try {
            $checkout = $stripe->createCheckoutSession($appointment->load(['service', 'user']));
        } catch (Throwable $e) {
            $appointment->delete();
            throw $e;
        }

        $appointment->update(['stripe_checkout_session_id' => $checkout['id']]);

        return AppointmentResource::make($appointment)
            ->additional(['checkout_url' => $checkout['url']])
            ->response()
            ->setStatusCode(201);
    }

    private function storeRecurring(
        BookingService $bookingService,
        StripeService $stripe,
        User $user,
        Service $service,
        StoreAppointmentRequest $request,
        int $occurrences
    ): JsonResponse {
        $appointments = $bookingService->bookRecurring(
            client: $user,
            service: $service,
            firstStartAt: Carbon::parse($request->validated('start_at')),
            notes: $request->validated('notes'),
            occurrences: $occurrences,
        );

        $ids = $appointments->pluck('id');

        try {
            $checkout = $stripe->createRecurringCheckoutSession(
                $appointments->map(fn (Appointment $appointment) => $appointment->load(['service', 'user']))
            );
        } catch (Throwable $e) {
            Appointment::query()->whereIn('id', $ids)->delete();
            throw $e;
        }

        Appointment::query()->whereIn('id', $ids)->update(['stripe_checkout_session_id' => $checkout['id']]);

        $refreshed = Appointment::query()->whereIn('id', $ids)->with('service')->orderBy('start_at')->get();

        return AppointmentResource::collection($refreshed)
            ->additional(['checkout_url' => $checkout['url']])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Cancela um agendamento a pedido do próprio cliente.
     */
    public function cancel(Appointment $appointment, BookingService $bookingService): AppointmentResource
    {
        $this->authorize('manageOwn', $appointment);

        $bookingService->cancelByClient($appointment);

        $appointment->refresh()->loadMissing(['service', 'user']);

        $this->sendMailSafely($appointment, new AppointmentCancelledMail($appointment));
        $this->notifySafely($appointment, new AppointmentCancelledNotification($appointment));

        return AppointmentResource::make($appointment);
    }

    /**
     * Remarca um agendamento a pedido do próprio cliente.
     */
    public function reschedule(
        RescheduleAppointmentRequest $request,
        Appointment $appointment,
        BookingService $bookingService
    ): AppointmentResource {
        $this->authorize('manageOwn', $appointment);

        $rescheduled = $bookingService->reschedule($appointment, Carbon::parse($request->validated('start_at')));
        $rescheduled->loadMissing(['service', 'user']);

        $this->sendMailSafely($rescheduled, new AppointmentRescheduledMail($rescheduled));
        $this->notifySafely($rescheduled, new AppointmentRescheduledNotification($rescheduled));

        return AppointmentResource::make($rescheduled);
    }

    /**
     * Atualiza o status de um agendamento (confirmar, cancelar ou concluir).
     */
    public function updateStatus(
        UpdateAppointmentStatusRequest $request,
        Appointment $appointment,
        GoogleCalendarService $googleCalendar
    ): AppointmentResource {
        $this->authorize('updateStatus', $appointment);

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $newStatus = AppointmentStatus::from($request->validated('status'));

        $appointment->update([
            'status' => $newStatus,
            'confirmed_by' => $user->id,
        ]);

        $appointment->loadMissing(['service', 'user']);

        if ($newStatus === AppointmentStatus::Confirmed) {
            if (! $appointment->google_event_id) {
                $appointment->update([
                    'google_event_id' => $googleCalendar->createEvent($appointment),
                ]);
            }

            $this->sendMailSafely($appointment, new AppointmentConfirmedMail($appointment));
            $this->notifySafely($appointment, new AppointmentConfirmedNotification($appointment));
        }

        if ($newStatus === AppointmentStatus::Cancelled) {
            if ($appointment->google_event_id) {
                $googleCalendar->deleteEvent($appointment->google_event_id);
                $appointment->update(['google_event_id' => null]);
            }

            $this->sendMailSafely($appointment, new AppointmentCancelledMail($appointment));
            $this->notifySafely($appointment, new AppointmentCancelledNotification($appointment));
        }

        return AppointmentResource::make($appointment);
    }

    private function sendMailSafely(Appointment $appointment, Mailable $mailable): void
    {
        try {
            Mail::to($appointment->user->email)->send($mailable);
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar e-mail de status de agendamento.', [
                'appointment_id' => $appointment->id,
                'mailable' => $mailable::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function notifySafely(Appointment $appointment, Notification $notification): void
    {
        try {
            $appointment->user->notify($notification);
        } catch (Throwable $e) {
            Log::warning('Falha ao criar notificação de status de agendamento.', [
                'appointment_id' => $appointment->id,
                'notification' => $notification::class,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
