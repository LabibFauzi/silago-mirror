<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\GoogleAuthService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected OtpService $otpService;
    protected GoogleAuthService $googleAuthService;

    public function __construct(OtpService $otpService, GoogleAuthService $googleAuthService)
    {
        $this->otpService = $otpService;
        $this->googleAuthService = $googleAuthService;
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'nim' => $request->nim,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'status' => 'active',
        ]);

        // Set email_domain_verified untuk email @mhs.polines.ac.id
        $emailDomain = strtolower(substr(strrchr($request->email, '@'), 1));
        if ($emailDomain === 'mhs.polines.ac.id') {
            $user->update(['email_domain_verified' => true]);
        }

        $code = rand(100000, 999999);
        Cache::put('email_verify_' . $user->email, $code, now()->addMinutes(15));

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan cek email untuk kode verifikasi.',
            'user' => new UserResource($user)
        ], 201);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required'
        ]);

        $cachedCode = Cache::get('email_verify_' . $request->email);

        if (!$cachedCode || $cachedCode != $request->code) {
            return response()->json(['message' => 'Kode verifikasi tidak valid atau kedaluwarsa.', 'errors' => ['code' => ['Kode tidak valid']]], 400);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['email_verified_at' => now()]);
        Cache::forget('email_verify_' . $request->email);

        return response()->json([
            'message' => 'Email berhasil diverifikasi',
            'user' => new UserResource($user)
        ]);
    }

    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $key = 'resend_verification_' . $request->email;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return response()->json(['message' => 'Terlalu banyak percobaan. Tunggu beberapa saat.'], 429);
        }
        RateLimiter::hit($key, 60);

        $user = User::where('email', $request->email)->firstOrFail();
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email sudah diverifikasi.'], 400);
        }

        $code = rand(100000, 999999);
        Cache::put('email_verify_' . $user->email, $code, now()->addMinutes(15));

        return response()->json(['message' => 'Kode verifikasi telah dikirim ulang.']);
    }

    public function login(LoginRequest $request)
    {
        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Terlalu banyak percobaan login. Silakan coba lagi nanti.', 'errors' => []], 429);
        }

        $loginInput = $request->login;
        $attempt = false;

        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $attempt = Auth::attempt(['email' => $loginInput, 'password' => $request->password]);
        } else {
            $attempt = Auth::attempt(['nim' => $loginInput, 'password' => $request->password]);
            if (!$attempt) {
                $attempt = Auth::attempt(['username' => $loginInput, 'password' => $request->password]);
            }
        }

        if (!$attempt) {
            RateLimiter::hit($key);
            return response()->json([
                'message' => 'Email/NIM atau kata sandi tidak cocok.',
                'errors' => ['login' => ['Email/NIM atau kata sandi salah.']]
            ], 401);
        }

        $user = Auth::user();
        if ($user->status === 'suspended') {
            Auth::logout();
            return response()->json(['message' => 'Akun ditangguhkan.', 'errors' => []], 401);
        }

        RateLimiter::clear($key);

        $token = $user->createToken('silago_api')->plainTextToken;

        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Verifikasi OTP untuk login 2FA.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        if (!$this->otpService->verify($user, $request->code, 'login')) {
            return response()->json([
                'message' => 'Kode OTP tidak valid atau kedaluwarsa.',
                'errors' => ['code' => ['Kode tidak valid']]
            ], 400);
        }

        $token = $user->createToken('silago_api')->plainTextToken;

        return response()->json([
            'message' => 'Verifikasi berhasil',
            'token' => $token,
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Kirim ulang OTP.
     */
    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->firstOrFail();

        $key = 'resend_otp_' . $request->email;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return response()->json(['message' => 'Terlalu banyak percobaan. Tunggu beberapa saat.'], 429);
        }
        RateLimiter::hit($key, 60);

        $this->otpService->generateAndSend($user, 'login');

        return response()->json(['message' => 'Kode OTP telah dikirim ulang.']);
    }

    /**
     * Login dengan Google OAuth.
     */
    public function loginGoogle(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            $result = $this->googleAuthService->handleCallback($request->access_token);
            $user = $result['user'];

            if ($user->status === 'suspended') {
                throw new \Exception('Akun Anda telah ditangguhkan.');
            }

            if ($request->filled('fcm_token')) {
                $user->update(['fcm_token' => $request->fcm_token]);
            }

            return response()->json([
                'token' => $result['token'],
                'user' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['google' => [$e->getMessage()]]
            ], 400);
        }
    }

    /**
     * Toggle 2FA/OTP untuk user.
     */
    public function toggleTwoFactor(Request $request)
    {
        $user = $request->user();
        $user->update(['two_factor_enabled' => !$user->two_factor_enabled]);

        return response()->json([
            'message' => 'Status 2FA berhasil diperbarui.',
            'two_factor_enabled' => $user->two_factor_enabled,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        // Lepas token FCM agar perangkat tidak lagi menerima push akun ini.
        $user->update(['fcm_token' => null]);
        $user->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Email tidak terdaftar di sistem.',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        // Rate limiting
        $key = 'forgot_password_' . $request->email;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan. Tunggu beberapa saat.'
            ], 429);
        }
        RateLimiter::hit($key, 120);

        // Generate OTP reset password
        $this->otpService->generateAndSend($user, 'reset_password');

        return response()->json([
            'message' => 'Kode reset password telah dikirim ke email Anda.',
            'email' => $user->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:' . config('services.otp.length', 6),
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        if (!$this->otpService->verify($user, $request->code, 'reset_password')) {
            return response()->json([
                'message' => 'Kode reset password tidak valid atau kedaluwarsa.',
                'errors' => ['code' => ['Kode tidak valid']]
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Hapus semua token yang ada
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Kata sandi berhasil direset. Silakan login dengan kata sandi baru.',
        ]);
    }
}
