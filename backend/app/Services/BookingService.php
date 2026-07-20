<?php

namespace App\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentConflictException;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * @return array<int, Carbon>
     */
    public function availableSlots(Service $service, string $date): array
    {
        $businessStart = Carbon::parse("{$date} ".config('booking.business_hours.start'));
        $businessEnd = Carbon::parse("{$date} ".config('booking.business_hours.end'));
        $interval = config('booking.slot_interval_minutes');
        $duration = $service->duration_minutes;

        $busyRanges = Appointment::query()
            ->active()
            ->whereBetween('start_at', [$businessStart->clone()->subDay(), $businessEnd->clone()->addDay()])
            ->get(['start_at', 'end_at']);

        $slots = [];

        for ($slotStart = $businessStart->clone(); $slotStart->clone()->addMinutes($duration)->lte($businessEnd); $slotStart->addMinutes($interval)) {
            $slotEnd = $slotStart->clone()->addMinutes($duration);

            $hasConflict = $busyRanges->contains(
                fn (Appointment $appointment) => $appointment->start_at->lt($slotEnd) && $appointment->end_at->gt($slotStart)
            );

            if (! $hasConflict && $slotStart->isFuture()) {
                $slots[] = $slotStart->clone();
            }
        }

        return $slots;
    }

    public function book(User $client, Service $service, Carbon $startAt, ?string $notes, AppointmentSource $source = AppointmentSource::Web): Appointment
    {
        $endAt = $startAt->clone()->addMinutes($service->duration_minutes);

        return DB::transaction(function () use ($client, $service, $startAt, $endAt, $notes, $source) {
            $conflict = Appointment::query()
                ->overlapping($startAt, $endAt)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new AppointmentConflictException;
            }

            return Appointment::create([
                'user_id' => $client->id,
                'service_id' => $service->id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => AppointmentStatus::Pending,
                'source' => $source,
                'notes' => $notes,
            ]);
        });
    }
}
