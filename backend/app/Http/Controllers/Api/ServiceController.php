<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ServiceController extends Controller
{
    /**
     * Lista os serviços ativos de um negócio.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'business' => ['required', 'string', 'exists:businesses,slug'],
        ]);

        $business = Business::where('slug', $request->string('business'))->firstOrFail();

        $services = Service::query()
            ->where('business_id', $business->id)
            ->where('active', true)
            ->with('staff')
            ->latest()
            ->get();

        return ServiceResource::collection($services);
    }

    /**
     * Lista todos os serviços, inclusive inativos.
     */
    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Service::class);

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $services = Service::query()->where('business_id', $user->business_id)->with('staff')->latest()->get();

        return ServiceResource::collection($services);
    }

    /**
     * Cria um serviço.
     */
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $this->authorize('create', Service::class);

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $service = Service::create([
            ...$request->validated(),
            'created_by' => $user->id,
            'business_id' => $user->business_id,
        ])->refresh();

        $service->staff()->sync($request->validated('staff_ids', []));

        return ServiceResource::make($service->load('staff'))->response()->setStatusCode(201);
    }

    /**
     * Atualiza um serviço.
     */
    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $this->authorize('update', $service);

        $service->update($request->validated());

        if ($request->has('staff_ids')) {
            $service->staff()->sync($request->validated('staff_ids', []));
        }

        return ServiceResource::make($service->load('staff'));
    }

    /**
     * Remove um serviço.
     */
    public function destroy(Request $request, Service $service): Response
    {
        $this->authorize('delete', $service);

        $service->delete();

        return response()->noContent();
    }
}
