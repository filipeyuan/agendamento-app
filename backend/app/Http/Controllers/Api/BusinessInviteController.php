<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInviteRequest;
use App\Http\Resources\UserResource;
use App\Models\BusinessInvite;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class BusinessInviteController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $invite = BusinessInvite::query()->where('token', $token)->with('business')->first();
        $business = $invite?->business;

        if (! $invite || ! $business || $invite->isExpired()) {
            return response()->json(['message' => 'Convite inválido ou expirado.'], 422);
        }

        return response()->json([
            'email' => $invite->email,
            'business_name' => $business->name,
            'expires_at' => $invite->expires_at,
        ]);
    }

    public function accept(AcceptInviteRequest $request): JsonResponse
    {
        $invite = BusinessInvite::query()->where('token', $request->validated('token'))->first();

        if (! $invite || $invite->isExpired()) {
            return response()->json(['message' => 'Convite inválido ou expirado.'], 422);
        }

        if (User::where('email', $invite->email)->exists()) {
            $invite->delete();

            return response()->json(['message' => 'Esse e-mail já está cadastrado.'], 422);
        }

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $invite->email,
            'password' => $request->validated('password'),
            'role' => UserRole::Admin,
            'business_id' => $invite->business_id,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $invite->delete();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => UserResource::make($user->loadMissing('business')),
            'token' => $token,
        ], 201);
    }
}
