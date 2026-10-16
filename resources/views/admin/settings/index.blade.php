@extends('admin.layout.app')
@section('title', 'Pengaturan')

@section('content')
<div class="space-y-6">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
        @csrf
        @method('PUT')
        
        <div class="p-6">
            <h3 class="text-lg font-bold text-ink mb-4 pb-2 border-b border-line">Pengaturan Umum</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-ink mb-1">Nama Aplikasi</label>
                    <input type="text" name="app_name" value="{{ $settings['app_name'] ?? 'SILAGO' }}" class="w-full p-2 border border-line rounded-lg focus:ring-brand focus:border-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink mb-1">Email Dukungan</label>
                    <input type="email" name="support_email" value="{{ $settings['support_email'] ?? 'support@silago.id' }}" class="w-full p-2 border border-line rounded-lg focus:ring-brand focus:border-brand">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Maksimal Foto Barang</label>
                        <input type="number" name="max_photos" value="{{ $settings['max_photos'] ?? 5 }}" min="1" max="10" class="w-full p-2 border border-line rounded-lg focus:ring-brand focus:border-brand">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Durasi Barang Aktif (Hari)</label>
                        <input type="number" name="listing_active_days" value="{{ $settings['listing_active_days'] ?? 30 }}" min="1" class="w-full p-2 border border-line rounded-lg focus:ring-brand focus:border-brand">
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-line flex justify-end">
            <button type="submit" class="bg-brand hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg shadow-sm transition-colors">
                Simpan Pengaturan
            </button>
        </div>
    </form>

    <!-- Moderasi Konten -->
    <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
        @csrf
        @method('PUT')
        
        <div class="p-6">
            <h3 class="text-lg font-bold text-ink mb-4 pb-2 border-b border-line">Moderasi Konten</h3>
            <div class="space-y-4">
                <div class="flex items-start">
                    <input type="checkbox" name="banned_words_enabled" value="1" id="banned_words_enabled" {{ ($settings['banned_words_enabled'] ?? '0') === '1' ? 'checked' : '' }} class="mt-1 h-4 w-4 text-brand border-line rounded focus:ring-brand">
                    <div class="ml-3">
                        <label for="banned_words_enabled" class="text-sm font-medium text-ink">Aktifkan Filter Kata Terlarang</label>
                        <p class="text-xs text-muted">Blokir postingan yang mengandung kata tidak pantas</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink mb-1">Daftar Kata Terlarang</label>
                    <p class="text-xs text-muted mb-2">Pisahkan dengan koma. Contoh: judi, narkoba, penipuan</p>
                    <textarea name="banned_words" rows="3" class="w-full p-2 border border-line rounded-lg focus:ring-brand focus:border-brand" placeholder="kata1, kata2, kata3">{{ $settings['banned_words'] ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-line flex justify-end">
            <button type="submit" class="bg-brand hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg shadow-sm transition-colors">
                Simpan Pengaturan
            </button>
        </div>
    </form>

    <!-- Session Timeout -->
    <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
        @csrf
        @method('PUT')
        
        <div class="p-6">
            <h3 class="text-lg font-bold text-ink mb-4 pb-2 border-b border-line">Keamanan</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-ink mb-1">Waktu Sesi (Menit)</label>
                    <p class="text-xs text-muted mb-2">Pengguna akan logout otomatis setelah tidak aktif</p>
                    <input type="number" name="session_timeout_minutes" value="{{ $settings['session_timeout_minutes'] ?? 120 }}" min="1" class="w-full p-2 border border-line rounded-lg focus:ring-brand focus:border-brand">
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-line flex justify-end">
            <button type="submit" class="bg-brand hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg shadow-sm transition-colors">
                Simpan Pengaturan
            </button>
        </div>
    </form>

    <!-- Mode Maintenance -->
    <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
        @csrf
        @method('PUT')
        
        <div class="p-6">
            <h3 class="text-lg font-bold text-ink mb-4 pb-2 border-b border-line">Mode Sistem</h3>
            <div class="space-y-4">
                <div class="flex items-start">
                    <input type="checkbox" name="maintenance_mode" value="1" id="maintenance_mode" {{ ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' }} class="mt-1 h-4 w-4 text-brand border-line rounded focus:ring-brand">
                    <div class="ml-3">
                        <label for="maintenance_mode" class="text-sm font-medium text-ink">Mode Maintenance</label>
                        <p class="text-xs text-muted">Aktifkan untuk menonaktifkan akses pengguna sementara</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-line flex justify-end">
            <button type="submit" class="bg-brand hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg shadow-sm transition-colors">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
