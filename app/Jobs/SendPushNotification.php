<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job untuk mengirim push notification via Firebase FCM.
 * Kegagalan job TIDAK mempengaruhi alur utama.
 *
 * TODO: konfigurasi kreait/laravel-firebase setelah akun Firebase dibuat.
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

        // TODO: Implementasi FCM setelah kreait/laravel-firebase dikonfigurasi
        // $messaging = app(\Kreait\Firebase\Contract\Messaging::class);
        // $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $user->fcm_token)
        //     ->withNotification(\Kreait\Firebase\Messaging\Notification::create($this->title, $this->body))
        //     ->withData($this->data);
        // $messaging->send($message);

        Log::info('FCM push (stub): kirim notifikasi ke user', [
            'user_id' => $this->userId,
            'title'   => $this->title,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        // Kegagalan FCM hanya dicatat, tidak disebarkan
        Log::warning('Push notification gagal', [
            'user_id' => $this->userId,
            'error'   => $exception->getMessage(),
        ]);
    }
}
