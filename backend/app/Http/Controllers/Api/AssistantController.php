<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Models\Business;
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

        $business = Business::where('slug', $request->validated('business'))->firstOrFail();

        $result = $assistantService->reply($user, $request->validated('messages'), $business);

        return response()->json($result);
    }
}
