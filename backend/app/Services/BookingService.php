<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\AppointmentActionNotAllowedException;
use App\Exceptions\AppointmentConflictException;
use App\Exceptions\InvalidStaffAssignmentException;
use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private GoogleCalendarService $googleCalendar,
        private StripeService $stripe,
    ) {}

    /**
     * @return array<int, Carbon>
     */
    public function availableSlots(Service $service, string $date, ?User $staff = null): array
    {
        $this->validateStaffAssignment($service, $staff);

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        $businessHour = BusinessHour::query()
            ->where('business_id', $service->business_id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (! $businessHour || ! $businessHour->is_open) {
            return [];
        }

        $blocks = ScheduleBlock::query()
            ->where('business_id', $service->business_id)
            ->whereDate('date', $date)
            ->get();

        if ($blocks->contains(fn (ScheduleBlock $block) => $block->isAllDay())) {
            return [];
        }

        $businessStart = Carbon::parse("{$date} {$businessHour->start_time}");
        $businessEnd = Carbon::parse("{$date} {$businessHour->end_time}");
        $interval = config('booking.slot_interval_minutes');
        $duration = $service->duration_minutes;

        $staffId = $staff?->id;

        $busyRanges = Appointment::query()
            ->when(
                $staffId,
                fn ($query) => $query->where('staff_id', $staffId),
                fn ($query) => $query->where('business_id', $service->business_id)
            )
            ->active()
            ->whereBetween('start_at', [$businessStart->clone()->subDay(), $businessEnd->clone()->addDay()])
            ->get(['start_at', 'end_at'])
            ->map(fn (Appointment $appointment) => [$appointment->start_at, $appointment->end_at])
            ->concat($blocks->map(fn (ScheduleBlock $block) => [
                Carbon::parse("{$date} {$block->start_time}"),
                Carbon::parse("{$date} {$block->end_time}"),
            ]))
            ->concat($this->googleCalendar->getBusyRanges($businessStart, $businessEnd, $service->business_id));

        $slots = [];

        for ($slotStart = $businessStart->clone(); $slotStart->clone()->addMinutes($duration)->lte($businessEnd); $slotStart->addMinutes($interval)) {
            $slotEnd = $slotStart->clone()->addMinutes($duration);

            $hasConflict = $busyRanges->contains(
                fn (array $range) => $range[0]->lt($slotEnd) && $range[1]->gt($slotStart)
            );

            if (! $hasConflict && $slotStart->isFuture()) {
                $slots[] = $slotStart->clone();
            }
        }

        return $slots;
    }

    public function book(
        User $client,
        Service $service,
        Carbon $startAt,
        ?string $notes,
        AppointmentSource $source = AppointmentSource::Web,
        ?User $staff = null
    ): Appointment {
        $this->validateStaffAssignment($service, $staff);

        $endAt = $startAt->clone()->addMinutes($service->duration_minutes);

        return DB::transaction(function () use ($client, $service, $startAt, $endAt, $notes, $source, $staff) {
            $this->assertSlotFree($service->business_id, $startAt, $endAt, staffId: $staff?->id);

            return Appointment::create([
                'user_id' => $client->id,
                'service_id' => $service->id,
                'staff_id' => $staff?->id,
                'business_id' => $service->business_id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => AppointmentStatus::Pending,
                'payment_status' => PaymentStatus::Pending,
                'source' => $source,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Cria uma série de agendamentos semanais recorrentes, todos compartilhando o mesmo
     * recurring_group_id. Se qualquer ocorrência conflitar, nenhuma é criada.
     *
     * @return Collection<int, Appointment>
     */
    public function bookRecurring(
        User $client,
        Service $service,
        Carbon $firstStartAt,
        ?string $notes,
        int $occurrences,
        AppointmentSource $source = AppointmentSource::Web,
        ?User $staff = null
    ): Collection {
        $this->validateStaffAssignment($service, $staff);

        $groupId = (string) Str::uuid();
        $duration = $service->duration_minutes;

        return DB::transaction(function () use ($client, $service, $firstStartAt, $notes, $occurrences, $source, $groupId, $duration, $staff) {
            $appointments = collect();

            for ($i = 0; $i < $occurrences; $i++) {
                $startAt = $firstStartAt->clone()->addWeeks($i);
                $endAt = $startAt->clone()->addMinutes($duration);

                $this->assertSlotFree($service->business_id, $startAt, $endAt, staffId: $staff?->id);

                $appointments->push(Appointment::create([
                    'user_id' => $client->id,
                    'service_id' => $service->id,
                    'staff_id' => $staff?->id,
                    'business_id' => $service->business_id,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'status' => AppointmentStatus::Pending,
                    'payment_status' => PaymentStatus::Pending,
                    'source' => $source,
                    'notes' => $notes,
                    'recurring_group_id' => $groupId,
                ]));
            }

            return $appointments;
        });
    }

    /**
     * Remarca um agendamento do próprio cliente pra um novo horário, respeitando a janela
     * mínima de antecedência configurada.
     */
    public function reschedule(Appointment $appointment, Carbon $newStartAt): Appointment
    {
        $this->assertWithinClientActionWindow($appointment);

        if (! $newStartAt->isFuture()) {
            throw new AppointmentActionNotAllowedException('O novo horário precisa ser no futuro.');
        }

        $duration = (int) $appointment->start_at->diffInMinutes($appointment->end_at);
        $newEndAt = $newStartAt->clone()->addMinutes($duration);

        DB::transaction(function () use ($appointment, $newStartAt, $newEndAt) {
            $this->assertSlotFree(
                $appointment->business_id,
                $newStartAt,
                $newEndAt,
                excludeAppointmentId: $appointment->id,
                staffId: $appointment->staff_id
            );

            $appointment->update([
                'start_at' => $newStartAt,
                'end_at' => $newEndAt,
            ]);
        });

        if ($appointment->google_event_id) {
            $oldAppointment = clone $appointment;
            $appointment->loadMissing(['service', 'user']);
            $appointment->update([
                'google_event_id' => $this->googleCalendar->createEvent($appointment),
            ]);
            $this->googleCalendar->deleteEvent($oldAppointment);
        }

        return $appointment->refresh();
    }

    /**
     * Cancela um agendamento a pedido do próprio cliente, respeitando a janela mínima de
     * antecedência configurada.
     */
    public function cancelByClient(Appointment $appointment): void
    {
        $this->assertWithinClientActionWindow($appointment);

        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        if ($appointment->google_event_id) {
            $this->googleCalendar->deleteEvent($appointment);
            $appointment->update(['google_event_id' => null]);
        }

        $this->stripe->refund($appointment->loadMissing('service'));
    }

    private function assertWithinClientActionWindow(Appointment $appointment): void
    {
        if (in_array($appointment->status, [AppointmentStatus::Cancelled, AppointmentStatus::Completed], true)) {
            throw new AppointmentActionNotAllowedException('Esse agendamento não pode mais ser alterado.');
        }

        $windowHours = (int) config('booking.client_action_window_hours');

        if (now()->addHours($windowHours)->greaterThanOrEqualTo($appointment->start_at)) {
            throw new AppointmentActionNotAllowedException(
                "Esse agendamento é em menos de {$windowHours}h. Entre em contato com o estabelecimento pra alterar."
            );
        }
    }

    private function assertSlotFree(
        int $businessId,
        Carbon $startAt,
        Carbon $endAt,
        ?int $excludeAppointmentId = null,
        ?int $staffId = null
    ): void {
        $conflict = Appointment::query()
            ->when(
                $staffId,
                fn ($query) => $query->where('staff_id', $staffId),
                fn ($query) => $query->where('business_id', $businessId)
            )
            ->overlapping($startAt, $endAt)
            ->when($excludeAppointmentId, fn ($query, $id) => $query->whereKeyNot($id))
            ->lockForUpdate()
            ->exists();

        if ($conflict) {
            throw new AppointmentConflictException;
        }
    }

    private function validateStaffAssignment(Service $service, ?User $staff): void
    {
        $hasStaff = $service->staff()->exists();

        if ($hasStaff && ! $staff) {
            throw new InvalidStaffAssignmentException('Escolha um profissional pra esse serviço.');
        }

        if ($staff && (! $hasStaff || ! $service->staff()->whereKey($staff->id)->exists())) {
            throw new InvalidStaffAssignmentException('Esse profissional não atende esse serviço.');
        }
    }
}
