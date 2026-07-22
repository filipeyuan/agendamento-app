<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBusinessHoursRequest;
use App\Http\Resources\BusinessHourResource;
use App\Models\BusinessHour;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessHourController extends Controller
{
    /**
     * Lista o horário de atendimento dos 7 dias da semana.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', BusinessHour::class);

        $hours = BusinessHour::query()->orderBy('day_of_week')->get();

        return BusinessHourResource::collection($hours);
    }

    /**
     * Atualiza o horário de atendimento dos 7 dias da semana.
     */
    public function update(UpdateBusinessHoursRequest $request): AnonymousResourceCollection
    {
        $this->authorize('update', BusinessHour::class);

        foreach ($request->validated('hours') as $day) {
            BusinessHour::query()->updateOrCreate(
                ['day_of_week' => $day['day_of_week']],
                [
                    'is_open' => $day['is_open'],
                    'start_time' => $day['is_open'] ? $day['start_time'] : null,
                    'end_time' => $day['is_open'] ? $day['end_time'] : null,
                ]
            );
        }

        $hours = BusinessHour::query()->orderBy('day_of_week')->get();

        return BusinessHourResource::collection($hours);
    }
}
