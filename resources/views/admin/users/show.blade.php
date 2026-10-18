@extends('admin.layout.app')
@section('title', 'Detail Pengguna')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.users.index') }}" class="text-brand hover:underline text-sm">&larr; Kembali ke Daftar Pengguna</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="col-span-1 bg-white rounded-xl border border-line shadow-sm p-6 text-center">
        <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name ?? 'User') }}" alt="Avatar" class="w-24 h-24 rounded-full object-cover mx-auto mb-4 border-4 border-page">
        <h2 class="text-xl font-bold text-ink mb-1">{{ $user->name ?? '-' }}</h2>
        <div class="text-muted text-sm mb-3">{{ $user->email ?? '-' }}</div>
        
        <div class="flex justify-center gap-2 mb-6">
            @if(isset($user->status))
                @if($user->status === 'active')
                    <span class="bg-green-100 text-green-700 py-1 px-3 rounded-full text-xs font-medium">Aktif</span>
                @elseif($user->status === 'suspended')
                    <span class="bg-red-100 text-red-700 py-1 px-3 rounded-full text-xs font-medium">Disuspend</span>
                @endif
            @endif

            @if(isset($user->email_verified_at) && $user->email_verified_at)
                <span class="bg-blue-100 text-blue-700 py-1 px-3 rounded-full text-xs font-medium">Terverifikasi</span>
            @endif
        </div>

        <div class="border-t border-line pt-4 space-y-2 text-sm text-left">
            <div class="flex justify-between">
                <span class="text-muted">Username</span>
                <span class="font-medium text-ink">{{ $user->username ? '@' . $user->username : '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">NIM</span>
                <span class="font-medium text-ink">{{ $user->nim ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">No. HP / WA</span>
                <span class="font-medium text-ink">{{ $user->phone ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">Peran</span>
                <span class="font-semibold text-ink uppercase text-xs">{{ $user->role ?? 'user' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">Bergabung</span>
                <span class="font-medium text-ink">{{ isset($user->created_at) ? $user->created_at->format('d M Y') : '-' }}</span>
            </div>
        </div>
        
        @if(isset($user->status) && $user->status === 'suspended')
        <div class="mt-4 p-3 bg-red-50 text-red-700 text-sm rounded-lg text-left">
            <span class="font-semibold block mb-1">Alasan Suspend:</span>
            {{ $user->suspend_reason ?? '-' }}
        </div>
        @endif
    </div>

    <div class="col-span-1 md:col-span-2">
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
                <h3 class="text-sm font-medium text-muted mb-2">Avg Rating</h3>
                <div class="text-2xl font-bold text-ink">{{ number_format($user->avg_rating ?? 0, 1) }} ⭐</div>
            </div>
            <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
                <h3 class="text-sm font-medium text-muted mb-2">Barang Aktif</h3>
                <div class="text-2xl font-bold text-ink">{{ $user->active_products_count ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
                <h3 class="text-sm font-medium text-muted mb-2">Total Terjual</h3>
                <div class="text-2xl font-bold text-ink">{{ $user->sales_count ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
                <h3 class="text-sm font-medium text-muted mb-2">Total Dibeli</h3>
                <div class="text-2xl font-bold text-ink">{{ $user->purchases_count ?? 0 }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-line shadow-sm p-6">
            <h3 class="text-lg font-bold text-ink mb-4">Aksi Pengelola</h3>
            <div class="flex gap-3">
                @if(isset($user->status))
                    @if($user->status !== 'suspended')
                        <form method="POST" action="{{ route('admin.users.suspend', $user->id ?? 0) }}" class="flex-1" x-data="{ showForm: false }">
                            @csrf
                            <button x-show="!showForm" @click="showForm = true" type="button" class="w-full bg-red-100 hover:bg-red-200 text-red-700 font-medium py-2 px-4 rounded-lg">Suspend Pengguna</button>
                            <div x-show="showForm" style="display: none;" class="mt-2 p-4 border border-red-200 rounded-lg bg-red-50">
                                <label class="block text-sm font-medium text-ink mb-1">Alasan Suspend</label>
                                <textarea name="suspend_reason" rows="2" class="w-full p-2 border border-line rounded-lg text-sm mb-2" required></textarea>
                                <div class="flex gap-2">
                                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg text-sm">Konfirmasi Suspend</button>
                                    <button type="button" @click="showForm = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg text-sm">Batal</button>
                                </div>
                            </div>
                        </form>
                    @endif
                    
                    @if($user->status === 'suspended')
                        <form method="POST" action="{{ route('admin.users.activate', $user->id ?? 0) }}" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">Aktifkan Kembali</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
