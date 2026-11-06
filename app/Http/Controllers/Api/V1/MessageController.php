<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            abort(403, 'Unauthorized');
        }

        $query = $conversation->messages();

        if ($request->filled('after_id')) {
            $query->where('id', '>', $request->after_id);
        }

        $limit = $request->input('limit', 30);
        $messages = $query->orderBy('created_at', 'asc')->limit($limit)->get();

        return MessageResource::collection($messages);
    }

    public function store(StoreMessageRequest $request, Conversation $conversation, NotificationService $notificationService)
    {
        $userId = $request->user()->id;

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message = DB::transaction(function () use ($request, $conversation, $userId) {
            $msg = $conversation->messages()->create([
                'sender_id' => $userId,
                'type' => $request->type,
                'message' => $request->message,
                'location_lat' => $request->location_lat,
                'location_lng' => $request->location_lng,
                'location_name' => $request->location_name,
                'location_address' => $request->location_address,
                'location_accuracy' => $request->location_accuracy,
            ]);

            $conversation->touch();
            return $msg;
        });

        // Kirim notifikasi pesanBaru ke pihak lain
        $recipientId = $conversation->buyer_id === $userId ? $conversation->seller_id : $conversation->buyer_id;
        $notificationService->send($recipientId, 'pesanBaru', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ]);

        return new MessageResource($message);
    }

    public function markRead(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Messages marked as read']);
    }
}
