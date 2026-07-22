<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Models\User;
use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;

class AssistantController extends Controller
{
    /**
     * Conversa com o assistente de agendamento via IA.
     */
    public function chat(ChatRequest $request, AssistantService $assistantService): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $result = $assistantService->reply($user, $request->validated('messages'));

        return response()->json($result);
    }
}
