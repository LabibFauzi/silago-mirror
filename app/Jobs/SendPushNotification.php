<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * Mengirim push notification via Firebase Cloud Messaging (FCM).
 *
 * Dijalankan sinkron (dispatchSync) karena Render tidak menjalankan queue
 * worker. Kegagalan push TIDAK boleh mempengaruhi alur utama, jadi semua
 * exception ditangkap di sini.
 */
class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly int    $userId,
        public readonly string $title,
        public readonly string $body,
        public readonly array  $data = [],
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user || ! $user->fcm_token) {
            return; // Tidak ada token FCM, lewati
        }

        // Nilai data FCM wajib string; buang nilai null.
        $data = [];
        foreach ($this->data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $data[(string) $key] = is_bool($value)
                ? ($value ? '1' : '0')
                : (string) $value;
        }

        try {
            $message = CloudMessage::new()
                ->withToken($user->fcm_token)
                ->withNotification(Notification::create($this->title, $this->body))
                ->withData($data)
                ->withAndroidConfig(AndroidConfig::fromArray([
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'silago_default',
                        'sound' => 'default',
                    ],
                ]));

            app(Messaging::class)->send($message);

            Log::info('Push notification terkirim', [
                'user_id' => $this->userId,
                'type'    => $this->data['type'] ?? null,
                'token'   => substr($user->fcm_token, 0, 12) . '…',
            ]);
        } catch (NotFound $e) {
            // Token sudah tidak valid (app di-uninstall / token kadaluarsa).
            $user->update(['fcm_token' => null]);
        } catch (\Throwable $e) {
            // Kegagalan push hanya dicatat, tidak disebarkan.
            Log::warning('Push notification gagal', [
                'user_id' => $this->userId,
                'type'    => $this->data['type'] ?? null,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
