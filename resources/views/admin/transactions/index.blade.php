@extends('admin.layout.app')
@section('title', 'Transaksi & Sengketa')

@section('content')
<div x-data="{ showDeleteModal: false, deleteAction: '', deleteLabel: '' }" class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Transaksi &amp; Sengketa</h1>
        <p class="mt-1 text-sm text-muted">Pantau transaksi dan sengketa antar pengguna</p>
    </div>

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
                            <a href="{{ route('admin.transactions.show', $deal->id) }}" class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-green-700/20 transition hover:bg-green-700 hover:shadow-md active:scale-95"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Detail</a>
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

</div>
@endsection