<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 50));

        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'message' => 'Notifikasi ditandai sudah dibaca.',
            'data' => new NotificationResource($notification),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $updated = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
            'count' => $updated,
        ]);
    }
}
