<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ChatPurgeService;
use Illuminate\Http\Request;

class InternalCronController extends Controller
{
    public function purgeChats(Request $request, ChatPurgeService $purgeService)
    {
        $configured = (string) config('services.chat_purge.secret', '');
        $provided = (string) (
            $request->header('X-Internal-Token')
            ?: $request->query('token', '')
            ?: $request->bearerToken()
        );

        if ($configured === '' || $provided === '' || ! hash_equals($configured, $provided)) {
            return response()->json(['message' => 'Token internal tidak valid.'], 403);
        }

        $result = $purgeService->purge(24);

        return response()->json([
            'message' => 'Pembersihan chat selesai.',
            'data' => $result,
        ]);
    }
}
