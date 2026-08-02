<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeactivateAccountRequest;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Cria um novo usuário (cliente, ou admin dono de um negócio novo) e retorna um token de acesso.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $isBusiness = $request->validated('account_type') === 'business';

        $business = $isBusiness
            ? Business::create([
                'name' => $request->validated('business_name'),
                'slug' => $this->uniqueSlug((string) $request->validated('business_name')),
            ])
            : null;

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'phone' => $request->validated('phone'),
            'role' => $isBusiness ? UserRole::Admin : UserRole::Client,
            'business_id' => $business?->id,
        ]);

        $user->refresh()->loadMissing('business');

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => UserResource::make($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Autentica um usuário e retorna um token de acesso.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas estão incorretas.'],
            ]);
        }

        if ($user->isDeactivated()) {
            $user->forceFill(['deactivated_at' => null])->save();
        }

        $user->loadMissing('business');

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => UserResource::make($user),
            'token' => $token,
        ]);
    }

    /**
     * Revoga o token de acesso usado na requisição atual.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Sessão encerrada com sucesso.']);
    }

    /**
     * Retorna o usuário autenticado.
     */
    public function me(Request $request): UserResource
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        return UserResource::make($user->loadMissing('business'));
    }

    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $user->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
        ]);

        return UserResource::make($user->loadMissing('business'));
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $user->update(['password' => $request->validated('password')]);

        return response()->json(['message' => 'Senha atualizada com sucesso.']);
    }

    public function deactivate(DeactivateAccountRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $user->tokens()->delete();
        $user->forceFill(['deactivated_at' => now()])->save();

        return response()->json(['message' => 'Conta desativada com sucesso.']);
    }

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();

            if ($user->isAdmin() && $user->business_id) {
                Business::whereKey($user->business_id)->delete();
            }

            $user->delete();
        });

        return response()->json(['message' => 'Conta excluída com sucesso.']);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Business::where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
