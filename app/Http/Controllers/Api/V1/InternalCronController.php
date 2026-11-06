<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\ChatPurgeService;
use Illuminate\Http\Request;

class InternalCronController extends Controller
{
    public function purgeChats(Request $request, ChatPurgeService $purgeService)
    {
        if (! $this->hasValidToken($request)) {
            return response()->json(['message' => 'Token internal tidak valid.'], 403);
        }

        $result = $purgeService->purge(24);

        return response()->json([
            'message' => 'Pembersihan chat selesai.',
            'data' => $result,
        ]);
    }

    public function purgeNotifications(Request $request)
    {
        if (! $this->hasValidToken($request)) {
            return response()->json(['message' => 'Token internal tidak valid.'], 403);
        }

        $days = max(1, (int) $request->input('days', 30));
        $deleted = Notification::where('created_at', '<', now()->subDays($days))->delete();

        return response()->json([
            'message' => 'Pembersihan notifikasi selesai.',
            'data' => [
                'days' => $days,
                'deleted' => $deleted,
            ],
        ]);
    }

    private function hasValidToken(Request $request): bool
    {
        $configured = (string) config('services.chat_purge.secret', '');
        $provided = (string) (
            $request->header('X-Internal-Token')
            ?: $request->query('token', '')
            ?: $request->bearerToken()
        );

        return $configured !== ''
            && $provided !== ''
            && hash_equals($configured, $provided);
    }
}
