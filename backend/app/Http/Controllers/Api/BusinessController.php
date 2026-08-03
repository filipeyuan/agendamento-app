<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBusinessAccentColorRequest;
use App\Http\Requests\UpdateBusinessBannerRequest;
use App\Http\Requests\UpdateBusinessLogoRequest;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
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

    public function updateLogo(UpdateBusinessLogoRequest $request, CloudinaryService $cloudinary): BusinessResource
    {
        $business = $this->requireProAdmin($request);
        $previousPublicId = $business->logo_public_id;

        $upload = $cloudinary->upload($request->file('logo'), 'zelo/logos');
        $business->forceFill(['logo_url' => $upload['url'], 'logo_public_id' => $upload['public_id']])->save();

        if ($previousPublicId) {
            $cloudinary->destroy($previousPublicId);
        }

        return BusinessResource::make($business);
    }

    public function removeLogo(Request $request, CloudinaryService $cloudinary): BusinessResource
    {
        $business = $this->requireProAdmin($request);

        if ($business->logo_public_id) {
            $cloudinary->destroy($business->logo_public_id);
        }

        $business->forceFill(['logo_url' => null, 'logo_public_id' => null])->save();

        return BusinessResource::make($business);
    }

    public function updateBanner(UpdateBusinessBannerRequest $request, CloudinaryService $cloudinary): BusinessResource
    {
        $business = $this->requireProAdmin($request);
        $previousPublicId = $business->banner_public_id;

        $upload = $cloudinary->upload($request->file('banner'), 'zelo/banners');
        $business->forceFill(['banner_url' => $upload['url'], 'banner_public_id' => $upload['public_id']])->save();

        if ($previousPublicId) {
            $cloudinary->destroy($previousPublicId);
        }

        return BusinessResource::make($business);
    }

    public function removeBanner(Request $request, CloudinaryService $cloudinary): BusinessResource
    {
        $business = $this->requireProAdmin($request);

        if ($business->banner_public_id) {
            $cloudinary->destroy($business->banner_public_id);
        }

        $business->forceFill(['banner_url' => null, 'banner_public_id' => null])->save();

        return BusinessResource::make($business);
    }

    public function updateAccentColor(UpdateBusinessAccentColorRequest $request): BusinessResource
    {
        $business = $this->requireProAdmin($request);
        $business->forceFill(['accent_color' => $request->validated('accent_color')])->save();

        return BusinessResource::make($business);
    }

    private function requireProAdmin(Request $request): Business
    {
        $user = $request->user();
        abort_if(! $user instanceof User || ! $user->isAdmin() || ! $user->business, 403);

        $business = $user->business;
        abort_if(! $business->isPro(), 403, 'Esse recurso é exclusivo do plano Pro.');

        return $business;
    }
}
