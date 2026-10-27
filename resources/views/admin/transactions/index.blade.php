@extends('admin.layout.app')
@section('title', 'Transaksi & Sengketa')

@section('content')
<div x-data="{ showDeleteModal: false, deleteAction: '', deleteLabel: '' }" class="space-y-6">

    {{-- Banner Notifikasi Mode Maintenance --}}
    @if(!empty($isMaintenance))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 text-lg">🛠️</span>
            <div>
                <p class="text-sm font-bold text-red-800">Mode Maintenance Aktif — Manajemen Transaksi Terbuka</p>
                <p class="text-xs text-red-700">Anda dapat melakukan pembersihan / penghapusan data transaksi secara langsung saat mode maintenance.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
        <div class="border-b border-line px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium {{ (!request('status') || request('status') === 'all') ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Semua</a>
                <a href="?status=berjalan" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'berjalan' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Berjalan</a>
                <a href="?status=selesai" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'selesai' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Selesai</a>
                <a href="?status=canceled" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'canceled' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Dibatalkan</a>
            </div>
            <form method="GET" action="" class="w-full sm:w-72">
                <input type="hidden" name="status" value="{{ request('status', '') }}">
                <input type="text" name="q" placeholder="Cari ID / pembeli / penjual / barang..." class="w-full px-4 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" value="{{ request('q', '') }}">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-muted uppercase bg-page border-b border-line">
                    <tr>
                        <th class="px-6 py-4">ID Transaksi</th>
                        <th class="px-6 py-4">Pembeli</th>
                        <th class="px-6 py-4">Penjual</th>
                        <th class="px-6 py-4">Barang</th>
                        <th class="px-6 py-4">Nilai</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deals ?? [] as $deal)
                    <tr class="border-b border-line hover:bg-page">
                        <td class="px-6 py-4 font-medium text-ink">#TRX-{{ str_pad($deal->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-6 py-4">{{ $deal->buyer->name ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $deal->seller->name ?? '-' }}</td>
                        <td class="px-6 py-4 truncate max-w-xs">{{ $deal->product->name ?? '-' }}</td>
                        <td class="px-6 py-4">Rp{{ number_format($deal->agreed_price ?? $deal->current_offer_price ?? 0, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if(in_array($deal->status, ['cancelled', 'canceled'], true))
                                <span class="bg-red-100 text-red-700 py-1 px-2 rounded-md text-xs">Dibatalkan</span>
                            @elseif($deal->status === 'completed')
                                <span class="bg-green-100 text-green-700 py-1 px-2 rounded-md text-xs">Selesai</span>
                            @elseif($deal->status === 'dispute')
                                <span class="bg-orange-100 text-orange-700 py-1 px-2 rounded-md text-xs">Sengketa</span>
                            @elseif($deal->status === 'negotiating')
                                <span class="bg-amber-100 text-amber-700 py-1 px-2 rounded-md text-xs">Negosiasi</span>
                            @else
                                <span class="bg-blue-100 text-blue-700 py-1 px-2 rounded-md text-xs">Berjalan</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <a href="{{ route('admin.transactions.show', $deal->id) }}" class="text-brand hover:underline font-medium text-xs">Detail</a>
                            @if(!empty($isMaintenance))
                                <button type="button" @click="deleteAction = '{{ route('admin.transactions.destroy', $deal->id) }}'; deleteLabel = '#TRX-{{ str_pad($deal->id, 4, '0', STR_PAD_LEFT) }}'; showDeleteModal = true" class="text-red-600 hover:underline font-medium text-xs">Hapus</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-muted">Tidak ada transaksi</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(isset($deals) && method_exists($deals, 'links'))
        <div class="px-6 py-4 border-t border-line">
            {{ $deals->links() }}
        </div>
        @endif
    </div>

    {{-- Modal Konfirmasi Hapus Transaksi (Mode Maintenance) --}}
    @if(!empty($isMaintenance))
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showDeleteModal" @click="showDeleteModal = false" class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50"></div>
            
            <div x-show="showDeleteModal" class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10">
                <div class="flex items-center gap-3 mb-4 text-red-600">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <h3 class="text-lg font-bold text-ink">Hapus Transaksi</h3>
                </div>

                <p class="text-sm text-muted mb-6">
                    Apakah Anda yakin ingin menghapus transaksi <strong class="text-ink" x-text="deleteLabel"></strong>? Tindakan ini tidak dapat dibatalkan.
                </p>

                <form method="POST" :action="deleteAction" class="flex justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="showDeleteModal = false" class="rounded-lg bg-gray-100 px-5 py-2 text-sm font-medium text-muted hover:bg-gray-200">Batal</button>
                    <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection