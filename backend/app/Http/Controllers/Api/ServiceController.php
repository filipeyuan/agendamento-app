<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ServiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $services = Service::query()->where('active', true)->latest()->get();

        return ServiceResource::collection($services);
    }

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Service::class);

        $services = Service::query()->latest()->get();

        return ServiceResource::collection($services);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $this->authorize('create', Service::class);

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $service = Service::create([
            ...$request->validated(),
            'created_by' => $user->id,
        ])->refresh();

        return ServiceResource::make($service)->response()->setStatusCode(201);
    }

    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $this->authorize('update', $service);

        $service->update($request->validated());

        return ServiceResource::make($service);
    }

    public function destroy(Request $request, Service $service): Response
    {
        $this->authorize('delete', $service);

        $service->delete();

        return response()->noContent();
    }
}
