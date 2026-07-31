<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleBlockRequest;
use App\Http\Resources\ScheduleBlockResource;
use App\Models\ScheduleBlock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ScheduleBlockController extends Controller
{
    /**
     * Lista os bloqueios de horário, com filtro opcional de período.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ScheduleBlock::class);

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $request->validate([
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $blocks = ScheduleBlock::query()
            ->where('business_id', $user->business_id)
            ->when($request->from, fn ($query, $from) => $query->whereDate('date', '>=', $from))
            ->when($request->to, fn ($query, $to) => $query->whereDate('date', '<=', $to))
            ->orderBy('date')
            ->get();

        return ScheduleBlockResource::collection($blocks);
    }

    /**
     * Cria um bloqueio de horário (dia inteiro ou um intervalo específico).
     */
    public function store(StoreScheduleBlockRequest $request): ScheduleBlockResource
    {
        $this->authorize('create', ScheduleBlock::class);

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $block = ScheduleBlock::create([
            ...$request->validated(),
            'business_id' => $user->business_id,
        ]);

        return ScheduleBlockResource::make($block);
    }

    /**
     * Remove um bloqueio de horário.
     */
    public function destroy(ScheduleBlock $scheduleBlock): Response
    {
        $this->authorize('delete', $scheduleBlock);

        $scheduleBlock->delete();

        return response()->noContent();
    }
}
