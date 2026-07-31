<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessController extends Controller
{
    /**
     * Lista os negócios cadastrados na plataforma, pro cliente escolher onde agendar.
     */
    public function index(): AnonymousResourceCollection
    {
        $businesses = Business::query()->orderBy('name')->get();

        return BusinessResource::collection($businesses);
    }

    /**
     * Mostra um negócio pelo slug.
     */
    public function show(Business $business): BusinessResource
    {
        return BusinessResource::make($business);
    }
}
