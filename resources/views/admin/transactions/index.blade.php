@extends('admin.layout.app')
@section('title', 'Transaksi & Sengketa')

@section('content')
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
                    <td class="px-6 py-4">Rp{{ number_format($deal->agreed_price ?? 0, 0, ',', '.') }}</td>
                    <td class="px-6 py-4">
                        @if($deal->status === 'canceled')
                            <span class="bg-red-100 text-red-700 py-1 px-2 rounded-md text-xs">Canceled</span>
                        @elseif($deal->status === 'completed')
                            <span class="bg-green-100 text-green-700 py-1 px-2 rounded-md text-xs">Selesai</span>
                        @elseif($deal->status === 'dispute')
                            <span class="bg-orange-100 text-orange-700 py-1 px-2 rounded-md text-xs">Sengketa</span>
                        @else
                            <span class="bg-blue-100 text-blue-700 py-1 px-2 rounded-md text-xs">Berjalan</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('admin.transactions.show', $deal->id) }}" class="text-brand hover:underline font-medium text-xs">Detail</a>
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
@endsection