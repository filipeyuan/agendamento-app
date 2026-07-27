<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Lista as últimas notificações do usuário autenticado, com a contagem de não lidas.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $limit = (int) $request->integer('limit', 20);

        $notifications = $user->notifications()->latest()->limit($limit + 1)->get();

        return response()->json([
            'data' => NotificationResource::collection($notifications->take($limit)),
            'has_more' => $notifications->count() > $limit,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Marca uma notificação do usuário autenticado como lida.
     */
    public function markRead(Request $request, DatabaseNotification $notification): Response
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);
        abort_if($notification->notifiable_id !== $user->id, 403);

        $notification->markAsRead();

        return response()->noContent();
    }

    /**
     * Marca todas as notificações do usuário autenticado como lidas.
     */
    public function markAllRead(Request $request): Response
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
