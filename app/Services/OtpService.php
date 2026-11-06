<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    /**
     * Generate dan simpan OTP untuk user.
     */
    public function generateAndSend(User $user, string $purpose = 'login'): string
    {
        $length = (int) config('services.otp.length', 6);
        $expiry = (int) config('services.otp.expiry_minutes', 15);

        $min = (int) str_repeat('1', $length);
        $max = (int) str_repeat('9', $length);
        $code = (string) random_int($min, $max);

        $user->update([
            'otp_code' => $code,
            'otp_expires_at' => now()->addMinutes($expiry),
        ]);

        Cache::put("otp_{$purpose}_{$user->email}", $code, now()->addMinutes($expiry));

        $this->sendOtpEmail($user, $code, $purpose);

        // Development: Log OTP untuk testing tanpa email server
        if (config('app.debug')) {
            \Log::channel('single')->info("OTP {$purpose} untuk {$user->email}: {$code} (expires: {$expiry} menit)");
        }

        return $code;
    }

    /**
     * Verifikasi OTP untuk user.
     */
    public function verify(User $user, string $code, string $purpose = 'login'): bool
    {
        if (!$user->otp_code || !$user->otp_expires_at) {
            return false;
        }

        if ($user->otp_expires_at->isPast()) {
            return false;
        }

        $cached = Cache::get("otp_{$purpose}_{$user->email}");

        if ($user->otp_code !== $code || $cached !== $code) {
            return false;
        }

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        Cache::forget("otp_{$purpose}_{$user->email}");

        return true;
    }

    /**
     * Kirim email OTP.
     */
    protected function sendOtpEmail(User $user, string $code, string $purpose): void
    {
        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $subject = match ($purpose) {
            'register' => 'Kode Verifikasi Pendaftaran SILAGO',
            'reset_password' => 'Kode Reset Kata Sandi SILAGO',
            default => 'Kode OTP Login SILAGO',
        };

        $body = "Halo {$user->name},\n\n"
            . "Kode OTP Anda untuk {$purpose} adalah: {$code}\n\n"
            . "Kode berlaku selama " . config('services.otp.expiry_minutes', 15) . " menit.\n"
            . "Jangan bagikan kode ini kepada siapa pun.\n\n"
            . "Salam,\nTim SILAGO";

        Mail::raw($body, function ($message) use ($user, $subject) {
            $message->to($user->email)
                ->subject($subject);
        });
    }

    /**
     * Hapus OTP user.
     */
    public function clear(User $user): void
    {
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        Cache::forget("otp_login_{$user->email}");
        Cache::forget("otp_register_{$user->email}");
        Cache::forget("otp_reset_password_{$user->email}");
    }
}
