<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function mine(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $appointments = $user
            ->appointments()
            ->with('service')
            ->orderByDesc('start_at')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Appointment::class);

        $request->validate([
            'date' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::enum(AppointmentStatus::class)],
        ]);

        $appointments = Appointment::query()
            ->with(['service', 'user'])
            ->when($request->date, fn ($query, $date) => $query->whereDate('start_at', $date))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('start_at')
            ->get();

        return AppointmentResource::collection($appointments);
    }

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

    public function store(StoreAppointmentRequest $request, BookingService $bookingService): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $service = Service::query()->findOrFail($request->validated('service_id'));
        abort_if(! $service instanceof Service, 404);

        $appointment = $bookingService->book(
            client: $user,
            service: $service,
            startAt: Carbon::parse($request->validated('start_at')),
            notes: $request->validated('notes'),
        );

        return AppointmentResource::make($appointment->load('service'))->response()->setStatusCode(201);
    }

    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): AppointmentResource
    {
        $this->authorize('updateStatus', $appointment);

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $appointment->update([
            'status' => $request->validated('status'),
            'confirmed_by' => $user->id,
        ]);

        return AppointmentResource::make($appointment->load('service'));
    }
}
