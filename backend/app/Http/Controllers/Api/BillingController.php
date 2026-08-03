<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function checkout(Request $request, StripeService $stripe): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User || ! $user->isAdmin() || ! $user->business, 403);

        if ($user->business->isPro()) {
            return response()->json(['message' => 'Seu negócio já está no plano Pro.'], 422);
        }

        $checkout = $stripe->createSubscriptionCheckoutSession($user->business, $user->email);

        return response()->json(['checkout_url' => $checkout['url']]);
    }

    public function portal(Request $request, StripeService $stripe): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User || ! $user->isAdmin() || ! $user->business, 403);

        if (! $user->business->stripe_customer_id) {
            return response()->json(['message' => 'Assine o plano Pro pra gerenciar sua assinatura.'], 422);
        }

        $portal = $stripe->createBillingPortalSession($user->business);

        return response()->json(['portal_url' => $portal['url']]);
    }

    public function dismissPremiumPrompt(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User || ! $user->isAdmin() || ! $user->business, 403);

        $user->business->forceFill(['premium_prompt_seen_at' => now()])->save();

        return response()->json(['message' => 'Ok.']);
    }
}
