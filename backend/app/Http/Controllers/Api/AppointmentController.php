<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentNotifier;
use App\Services\BookingService;
use App\Services\GoogleCalendarService;
use App\Services\StripeService;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Response as DocumentedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
            ->with(['service', 'business', 'staff'])
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

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $request->validate([
            'date' => ['sometimes', 'date'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::enum(AppointmentStatus::class)],
            'staff_id' => ['sometimes', 'integer', 'exists:users,id'],
        ]);

        $appointments = Appointment::query()
            ->where('business_id', $user->business_id)
            ->with(['service', 'user', 'business', 'staff'])
            ->when($request->date, fn ($query, $date) => $query->whereDate('start_at', $date))
            ->when($request->from, fn ($query, $from) => $query->where('start_at', '>=', $from))
            ->when($request->to, fn ($query, $to) => $query->where('start_at', '<', $to))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->staff_id, fn ($query, $staffId) => $query->where('staff_id', $staffId))
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
            'staff_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $staff = isset($validated['staff_id']) ? User::query()->find((int) $validated['staff_id']) : null;

        $slots = $bookingService->availableSlots($service, $validated['date'], $staff);

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

        $staffId = $request->validated('staff_id');
        $staff = $staffId ? User::query()->find((int) $staffId) : null;

        $occurrences = $request->validated('recurring_occurrences');

        if ($occurrences) {
            return $this->storeRecurring($bookingService, $stripe, $user, $service, $request, (int) $occurrences, $staff);
        }

        $appointment = $bookingService->book(
            client: $user,
            service: $service,
            startAt: Carbon::parse($request->validated('start_at')),
            notes: $request->validated('notes'),
            staff: $staff,
        );

        try {
            $checkout = $stripe->createCheckoutSession($appointment->load(['service', 'user', 'business', 'staff']));
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
        int $occurrences,
        ?User $staff = null
    ): JsonResponse {
        $appointments = $bookingService->bookRecurring(
            client: $user,
            service: $service,
            firstStartAt: Carbon::parse($request->validated('start_at')),
            notes: $request->validated('notes'),
            occurrences: $occurrences,
            staff: $staff,
        );

        $ids = $appointments->pluck('id');

        try {
            $checkout = $stripe->createRecurringCheckoutSession(
                $appointments->map(fn (Appointment $appointment) => $appointment->load(['service', 'user', 'business', 'staff']))
            );
        } catch (Throwable $e) {
            Appointment::query()->whereIn('id', $ids)->delete();
            throw $e;
        }

        Appointment::query()->whereIn('id', $ids)->update(['stripe_checkout_session_id' => $checkout['id']]);

        $refreshed = Appointment::query()->whereIn('id', $ids)->with(['service', 'business', 'staff'])->orderBy('start_at')->get();

        return AppointmentResource::collection($refreshed)
            ->additional(['checkout_url' => $checkout['url']])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Cancela um agendamento a pedido do próprio cliente.
     */
    public function cancel(Appointment $appointment, BookingService $bookingService, AppointmentNotifier $notifier): AppointmentResource
    {
        $this->authorize('manageOwn', $appointment);

        $bookingService->cancelByClient($appointment);

        $appointment->refresh();
        $notifier->notifyCancelled($appointment);

        return AppointmentResource::make($appointment);
    }

    /**
     * Remarca um agendamento a pedido do próprio cliente.
     */
    public function reschedule(
        RescheduleAppointmentRequest $request,
        Appointment $appointment,
        BookingService $bookingService,
        AppointmentNotifier $notifier
    ): AppointmentResource {
        $this->authorize('manageOwn', $appointment);

        $rescheduled = $bookingService->reschedule($appointment, Carbon::parse($request->validated('start_at')));
        $notifier->notifyRescheduled($rescheduled);

        return AppointmentResource::make($rescheduled);
    }

    /**
     * Atualiza o status de um agendamento (confirmar, cancelar ou concluir).
     */
    public function updateStatus(
        UpdateAppointmentStatusRequest $request,
        Appointment $appointment,
        GoogleCalendarService $googleCalendar,
        StripeService $stripe,
        AppointmentNotifier $notifier
    ): AppointmentResource {
        $this->authorize('updateStatus', $appointment);

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $newStatus = AppointmentStatus::from($request->validated('status'));

        $appointment->update([
            'status' => $newStatus,
            'confirmed_by' => $user->id,
        ]);

        $appointment->loadMissing(['service', 'user', 'business', 'staff']);

        if ($newStatus === AppointmentStatus::Confirmed) {
            if (! $appointment->google_event_id) {
                $appointment->update([
                    'google_event_id' => $googleCalendar->createEvent($appointment),
                ]);
            }

            $notifier->notifyConfirmed($appointment);
        }

        if ($newStatus === AppointmentStatus::Cancelled) {
            if ($appointment->google_event_id) {
                $googleCalendar->deleteEvent($appointment);
                $appointment->update(['google_event_id' => null]);
            }

            $stripe->refund($appointment);

            $notifier->notifyCancelled($appointment);
        }

        return AppointmentResource::make($appointment);
    }
}
