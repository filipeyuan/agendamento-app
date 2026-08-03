<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        $user = User::query()->where('email_verification_token', $request->validated('token'))->first();

        if (! $user) {
            return response()->json(['message' => 'Link inválido ou expirado.'], 422);
        }

        $user->forceFill(['email_verified_at' => now(), 'email_verification_token' => null])->save();

        return response()->json(['message' => 'E-mail confirmado com sucesso.']);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        if ($user->isEmailVerified()) {
            return response()->json(['message' => 'Seu e-mail já está confirmado.']);
        }

        $user->sendEmailVerification();

        return response()->json(['message' => 'E-mail de confirmação reenviado.']);
    }
}
