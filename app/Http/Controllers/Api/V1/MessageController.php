<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\ConversationPresence;
use App\Models\Message;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            abort(403, 'Kamu tidak memiliki akses ke percakapan ini.');
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
            return response()->json(['message' => 'Kamu tidak memiliki akses ke percakapan ini.'], 403);
        }

        $message = DB::transaction(function () use ($request, $conversation, $userId) {
            $data = [
                'sender_id' => $userId,
                'type' => $request->type,
                'message' => $request->message,
            ];

            if ($request->type === 'location') {
                $data['location_lat'] = $request->location_lat;
                $data['location_lng'] = $request->location_lng;
                $data['location_name'] = $request->location_name;
                $data['location_address'] = $request->location_address;
                $data['location_accuracy'] = $request->location_accuracy;
            } elseif ($request->type === 'image' && $request->hasFile('image')) {
                $imageData = $this->storeImage($request->file('image'), $conversation->id);
                $data['image_path'] = $imageData['full'];
                $data['image_thumbnail'] = $imageData['thumbnail'];
                $data['image_caption'] = $request->caption;
            }

            $msg = $conversation->messages()->create($data);
            $conversation->touch();
            return $msg;
        });

        $recipientId = $conversation->buyer_id === $userId ? $conversation->seller_id : $conversation->buyer_id;
        $notificationService->newMessage(
            $recipientId,
            $conversation,
            (string) $request->user()->name,
        );

        return new MessageResource($message);
    }

    private function storeImage($file, int $conversationId): array
    {
        $disk = Storage::disk('public');
        $dir = "conversations/{$conversationId}";
        
        $timestamp = now()->timestamp;
        $hash = Str::random(12);
        $ext = $file->getClientOriginalExtension();
        
        $filename = "{$timestamp}-{$hash}.{$ext}";
        $fullPath = "{$dir}/{$filename}";
        
        $file->storeAs($dir, $filename, 'public');
        
        $thumbnailFilename = "{$timestamp}-{$hash}-thumb.{$ext}";
        $thumbnailPath = "{$dir}/{$thumbnailFilename}";
        
        try {
            $manager = ImageManager::usingDriver(GdDriver::class);
            // Baca dari file yang sudah tersimpan, bukan file upload sementara.
            $image = $manager->decodePath($disk->path($fullPath));
            $image->cover(200, 200);

            $extension = strtolower($ext) ?: 'jpg';
            $encoded = $image->encodeUsingFileExtension($extension, quality: 80);

            $disk->put($thumbnailPath, (string) $encoded);
        } catch (\Throwable $e) {
            // Thumbnail opsional: jika gagal, gunakan gambar asli.
            $thumbnailPath = $fullPath;
        }
        
        return [
            'full' => $fullPath,
            'thumbnail' => $thumbnailPath,
        ];
    }

    public function markRead(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json(['message' => 'Kamu tidak memiliki akses ke percakapan ini.'], 403);
        }

        $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        ConversationPresence::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
            ],
            ['last_seen_at' => now()],
        );

        // Membuka chat juga menuntaskan badge notifikasi pesan untuk chat ini.
        Notification::query()
            ->where('user_id', $userId)
            ->where('type', 'new_message')
            ->where('reference_id', $conversation->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Pesan ditandai sudah dibaca.']);
    }
}
