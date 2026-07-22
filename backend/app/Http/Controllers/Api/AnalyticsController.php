<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Resumo de agendamentos pro dashboard do admin: por status, por dia, receita e serviços mais agendados.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $request->validate([
            'days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ]);

        $days = (int) $request->query('days', 30);
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $appointments = Appointment::query()
            ->with('service')
            ->whereBetween('start_at', [$from, $to])
            ->get();

        $byStatus = collect(AppointmentStatus::cases())->mapWithKeys(
            fn (AppointmentStatus $status) => [
                $status->value => $appointments->filter(fn (Appointment $a) => $a->status === $status)->count(),
            ]
        );

        $byDay = collect(range(0, $days - 1))->map(function (int $offset) use ($from, $appointments) {
            $date = $from->clone()->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'count' => $appointments->filter(fn (Appointment $a) => $a->start_at->isSameDay($date))->count(),
            ];
        });

        $revenue = $appointments
            ->filter(fn (Appointment $a) => in_array($a->status, [AppointmentStatus::Confirmed, AppointmentStatus::Completed], true))
            ->sum(fn (Appointment $a) => (float) $a->service->price);

        $topServices = $appointments
            ->groupBy(fn (Appointment $a) => $a->service->name)
            ->map(fn ($group, $name) => ['name' => $name, 'count' => $group->count()])
            ->sortByDesc('count')
            ->take(5)
            ->values();

        return response()->json([
            'by_status' => $byStatus,
            'by_day' => $byDay->values(),
            'revenue' => round($revenue, 2),
            'top_services' => $topServices,
        ]);
    }
}
