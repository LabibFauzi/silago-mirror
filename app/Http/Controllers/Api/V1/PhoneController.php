<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Nomor WhatsApp manual (tanpa OTP).
 *
 * Pengguna cukup mengisi nomor, lalu bisa mengaktifkan tampil/tidak.
 * Tombol "Hubungi via WhatsApp" di aplikasi memakai deep link wa.me,
 * jadi tidak butuh gateway atau API berbayar.
 */
class PhoneController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'min:8', 'max:20'],
        ]);

        $phone = $this->normalize($data['phone']);

        if (strlen($phone) < 10 || strlen($phone) > 15) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor tidak valid. Contoh: 0812xxxxxxx.',
            ]);
        }

        $user = $request->user();
        
        // Cek nomor tidak dipakai user lain
        $exists = \App\Models\User::where('phone', $phone)
            ->where('id', '!=', $user->id)
            ->exists();
        
        if ($exists) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor sudah terdaftar di akun lain.',
            ]);
        }

        // Nomor yang baru pertama kali diisi langsung ditampilkan agar
        // pengguna lain bisa menghubungi lewat WhatsApp. Pengguna tetap bisa
        // menyembunyikannya lewat toggle.
        $isFirstPhone = empty($user->phone);
        $user->update([
            'phone' => $phone,
            'show_phone' => $isFirstPhone ? true : (bool) $user->show_phone,
        ]);

        return response()->json([
            'message' => 'Nomor WhatsApp disimpan.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function updateVisibility(Request $request)
    {
        $data = $request->validate([
            'show_phone' => ['required', 'boolean'],
        ]);

        $user = $request->user();

        if ($data['show_phone'] && empty($user->phone)) {
            throw ValidationException::withMessages([
                'show_phone' => 'Isi nomor WhatsApp dulu sebelum ditampilkan.',
            ]);
        }

        $user->update(['show_phone' => (bool) $data['show_phone']]);

        return response()->json([
            'message' => 'Preferensi nomor diperbarui.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function destroy(Request $request)
    {
        $request->user()->update([
            'phone' => null,
            'show_phone' => false,
        ]);

        return response()->json([
            'message' => 'Nomor WhatsApp dihapus.',
            'user' => new UserResource($request->user()->fresh()),
        ]);
    }

    /** Normalisasi ke format internasional tanpa simbol (62xxxx). */
    private function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '62')) {
            return $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }
}