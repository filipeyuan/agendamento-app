<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\InviteTeamMemberRequest;
use App\Http\Resources\BusinessInviteResource;
use App\Http\Resources\TeamMemberResource;
use App\Mail\TeamInviteMail;
use App\Models\Business;
use App\Models\BusinessInvite;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $this->requireAdmin($request);

        $members = $business->admins()->orderBy('created_at')->get();
        $invites = $business->invites()
            ->where('expires_at', '>', now())
            ->with('invitedBy')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'members' => TeamMemberResource::collection($members),
            'invites' => BusinessInviteResource::collection($invites),
        ]);
    }

    public function invite(InviteTeamMemberRequest $request): BusinessInviteResource
    {
        $user = $request->user();
        abort_if(! $user instanceof User || ! $user->isAdmin() || ! $user->business, 403);
        $business = $user->business;

        $invite = BusinessInvite::updateOrCreate(
            ['business_id' => $business->id, 'email' => $request->validated('email')],
            ['token' => Str::random(40), 'invited_by' => $user->id, 'expires_at' => now()->addDays(7)]
        );

        $frontendUrl = $this->frontendUrl();

        try {
            Mail::to($invite->email)->send(
                new TeamInviteMail($business->name, "{$frontendUrl}/aceitar-convite?token={$invite->token}")
            );
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar e-mail de convite de equipe.', [
                'business_id' => $business->id,
                'email' => $invite->email,
                'message' => $e->getMessage(),
            ]);
        }

        return BusinessInviteResource::make($invite->load('invitedBy'));
    }

    public function cancelInvite(Request $request, BusinessInvite $invite): JsonResponse
    {
        $business = $this->requireAdmin($request);
        abort_if($invite->business_id !== $business->id, 403);

        $invite->delete();

        return response()->json(['message' => 'Convite cancelado.']);
    }

    public function removeMember(Request $request, User $member): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User || ! $user->isAdmin() || ! $user->business, 403);
        $business = $user->business;

        abort_if($member->business_id !== $business->id, 403);
        abort_if($member->id === $user->id, 422, 'Você não pode remover a si mesmo.');

        $member->assignedServices()->detach();
        $member->tokens()->delete();
        $member->forceFill(['business_id' => null, 'role' => UserRole::Client])->save();

        return response()->json(['message' => 'Membro removido da equipe.']);
    }

    private function requireAdmin(Request $request): Business
    {
        $user = $request->user();
        abort_if(! $user instanceof User || ! $user->isAdmin() || ! $user->business, 403);

        return $user->business;
    }

    private function frontendUrl(): string
    {
        /** @var array<int, string> $allowedOrigins */
        $allowedOrigins = config('cors.allowed_origins');

        return rtrim($allowedOrigins[0] ?? 'http://localhost:3000', '/');
    }
}
