@extends('admin.layout.app')
@section('title', 'Pengguna')

@section('content')
<div class="space-y-6" x-data="{ selectedUser: null, showSuspendModal: false }">

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Pengguna</h1>
        <p class="mt-1 text-sm text-muted">Kelola akun pengguna SILAGO</p>
    </div>

    <div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
        <div class="border-b border-line px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium {{ (!request('status') || request('status') === 'all') ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Semua</a>
                <a href="?status=active" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'active' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Aktif</a>
                <a href="?status=monitored" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'monitored' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Dipantau</a>
                <a href="?status=suspended" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'suspended' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Disuspend</a>
            </div>
            <form method="GET" action="" class="w-full sm:w-72">
                <input type="hidden" name="status" value="{{ request('status', '') }}">
                <input type="text" name="q" placeholder="Cari nama / email / NIM..." class="w-full px-4 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" value="{{ request('q', '') }}">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-muted uppercase bg-page border-b border-line">
                    <tr>
                        <th class="px-6 py-4">Pengguna</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Bergabung</th>
                        <th class="px-6 py-4">Barang</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users ?? [] as $user)
                    <tr class="border-b border-line hover:bg-page">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}" alt="Avatar" class="w-9 h-9 rounded-full object-cover mr-3 border border-line">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-ink">{{ $user->name }}</span>
                                        @if($user->role === 'admin')
                                            <span class="rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-purple-700">Admin</span>
                                        @elseif($user->role === 'moderator')
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-blue-700">Moderator</span>
                                        @endif
                                    </div>
                                    @if($user->username || $user->nim)
                                        <div class="text-xs text-muted">{{ $user->username ? '@'.$user->username : $user->nim }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-ink">{{ $user->email }}</div>
                            <div class="text-xs text-muted">{{ $user->nim ?: '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-muted">{{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}</td>
                        <td class="px-6 py-4 font-medium text-ink">{{ $user->products_count ?? 0 }}</td>
                        <td class="px-6 py-4">
                            @if($user->status === 'active')
                                <span class="inline-block rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Aktif</span>
                            @elseif($user->status === 'suspended')
                                <span class="inline-block rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">Disuspend</span>
                            @elseif($user->status === 'monitored')
                                <span class="inline-block rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Dipantau</span>
                            @else
                                <span class="inline-block rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">{{ $user->status ?? 'Aktif' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-green-700/20 transition hover:bg-green-700 hover:shadow-md active:scale-95"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Lihat Profil</a>
                                @if($user->status === 'suspended')
                                    <form method="POST" action="{{ route('admin.users.activate', $user->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-green-700/20 transition hover:bg-green-700 hover:shadow-md active:scale-95"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Aktifkan</button>
                                    </form>
                                @elseif($user->role === 'user')
                                    <button type="button" @click="selectedUser = {{ json_encode(['id' => $user->id, 'name' => $user->name]) }}; showSuspendModal = true" class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-red-700/20 transition hover:bg-red-700 hover:shadow-md active:scale-95"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>Suspend</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-muted">Tidak ada pengguna</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($users) && method_exists($users, 'links'))
        <div class="px-6 py-4 border-t border-line">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    {{-- Modal Suspend --}}
    <div x-show="showSuspendModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="showSuspendModal" @click="showSuspendModal = false" class="fixed inset-0 bg-black/60 transition-opacity"></div>

            <div x-show="showSuspendModal" x-transition class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-ink">Suspend Pengguna</h3>
                    <button type="button" @click="showSuspendModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <p class="mb-4 text-sm text-muted">Anda akan mensuspend pengguna <strong class="text-ink" x-text="selectedUser?.name"></strong>. Masukkan alasan suspend.</p>

                <form method="POST" :action="`{{ url('admin/users') }}/${selectedUser?.id}/suspend`">
                    @csrf
                    <div class="mb-4">
                        <textarea name="suspend_reason" rows="3" class="w-full rounded-lg border border-line p-2 text-sm" placeholder="Alasan suspend..." required></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Suspend Sekarang</button>
                        <button type="button" @click="showSuspendModal = false" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
