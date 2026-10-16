@extends('admin.layout.app')
@section('title', 'Pengguna')

@section('content')
<div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden" x-data="{ showModal: false, selectedUser: null }">
    <div class="border-b border-line px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium {{ (!request('status') || request('status') === 'all') ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Semua</a>
            <a href="?status=active" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'active' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Aktif</a>
            <a href="?status=suspended" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'suspended' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Disuspend</a>
        </div>
        <form method="GET" action="" class="w-full sm:w-72">
            <input type="hidden" name="status" value="{{ request('status', '') }}">
            <input type="text" name="q" placeholder="Cari nama / email..." class="w-full px-4 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" value="{{ request('q', '') }}">
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
                            <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}" alt="Avatar" class="w-8 h-8 rounded-full object-cover mr-3">
                            <div class="font-medium text-ink">{{ $user->name }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">{{ $user->email }}</td>
                    <td class="px-6 py-4">{{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}</td>
                    <td class="px-6 py-4">{{ $user->products_count ?? 0 }}</td>
                    <td class="px-6 py-4">
                        @if($user->status === 'active')
                            <span class="bg-green-100 text-green-700 py-1 px-2 rounded-md text-xs">Aktif</span>
                        @elseif($user->status === 'suspended')
                            <span class="bg-red-100 text-red-700 py-1 px-2 rounded-md text-xs">Disuspend</span>
                        @else
                            <span class="bg-gray-100 text-gray-600 py-1 px-2 rounded-md text-xs">{{ $user->status ?? 'Aktif' }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('admin.users.show', $user->id) }}" class="text-brand hover:underline font-medium text-xs">Lihat</a>
                        @if($user->status !== 'suspended')
                        <button @click="selectedUser = {{ json_encode($user) }}; showModal = true" class="text-red-600 hover:underline font-medium text-xs">Suspend</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-muted">Tidak ada pengguna</td>
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

    <!-- Modal Suspend -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50"></div>
            
            <div x-show="showModal" class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-bold text-ink">Suspend Pengguna</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <p class="text-sm text-muted mb-4">Anda akan mensuspend pengguna <strong x-text="selectedUser?.name"></strong>. Masukkan alasan suspend.</p>

                <form method="POST" :action="`/admin/users/${selectedUser?.id}/suspend`">
                    @csrf
                    <div class="mb-4">
                        <textarea name="suspend_reason" rows="3" class="w-full p-2 border border-line rounded-lg text-sm" placeholder="Alasan suspend..." required></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg text-sm">Suspend Sekarang</button>
                        <button type="button" @click="showModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
