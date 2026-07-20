<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::query()->where('active', true)->latest()->get();

        return ServiceResource::collection($services);
    }

    public function adminIndex(Request $request)
    {
        $this->authorize('viewAny', Service::class);

        $services = Service::query()->latest()->get();

        return ServiceResource::collection($services);
    }

    public function store(StoreServiceRequest $request)
    {
        $this->authorize('create', Service::class);

        $service = Service::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ])->refresh();

        return ServiceResource::make($service)->response()->setStatusCode(201);
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $this->authorize('update', $service);

        $service->update($request->validated());

        return ServiceResource::make($service);
    }

    public function destroy(Request $request, Service $service)
    {
        $this->authorize('delete', $service);

        $service->delete();

        return response()->noContent();
    }
}
