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
use Dedoc\Scramble\Attributes\Response as DocumentedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    /**
     * Lista os agendamentos do usuário autenticado.
     */
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

    /**
     * Atualiza o status de um agendamento (confirmar, cancelar ou concluir).
     */
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
