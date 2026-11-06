<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService
{
    /**
     * Validasi email adalah @mhs.polines.ac.id.
     */
    protected function validatePolinesEmail(string $email): void
    {
        $emailDomain = strtolower(substr(strrchr($email, '@'), 1));
        if ($emailDomain !== 'mhs.polines.ac.id') {
            throw new \Exception('Email harus menggunakan email mahasiswa @mhs.polines.ac.id.');
        }
    }

    /**
     * Handle callback dari Google OAuth.
     */
    public function handleCallback(string $accessToken): array
    {
        try {
            $provider = Socialite::driver('google')->stateless();
            $provider->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
            $googleUser = $provider->userFromToken($accessToken);

            $email = $googleUser->getEmail();
            $name = $googleUser->getName();
            $googleId = $googleUser->getId();
            $avatar = $googleUser->getAvatar();

            // Validasi domain
            $this->validatePolinesEmail($email);

            $user = User::where('google_id', $googleId)
                ->orWhere('email', $email)
                ->first();

            if ($user) {
                // Update data Google jika belum ada
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleId]);
                }
                if (!$user->avatar && $avatar) {
                    $user->update(['avatar' => $avatar]);
                }
            } else {
                // Buat user baru
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                    'role' => 'user',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'password' => bcrypt(str()->random(32)),
                ]);
            }

            // Cek akun ditangguhkan
            if ($user->status === 'suspended') {
                throw new \Exception('Akun Anda telah ditangguhkan.');
            }

            // Set email_domain_verified
            if ($user->email_domain_verified === null) {
                $user->update(['email_domain_verified' => true]);
            }

            return [
                'user' => $user,
                'token' => $user->createToken('silago_api')->plainTextToken,
            ];
        } catch (\Exception $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
