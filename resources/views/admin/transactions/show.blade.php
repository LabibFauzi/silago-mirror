@extends('admin.layout.app')
@section('title', 'Detail Transaksi')

@section('content')
<div>
    <a href="{{ route('admin.transactions.index') }}" class="text-brand hover:underline mb-4 inline-block">← Kembali ke Transaksi</a>
    
    <div class="bg-white rounded-xl border border-line shadow-sm p-6 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold text-ink">#TRX-{{ str_pad($deal->id, 4, '0', STR_PAD_LEFT) }}</h1>
                <p class="text-sm text-muted mt-1">{{ $deal->created_at?->format('d M Y H:i') ?? '-' }}</p>
            </div>
            <div>
                @if($deal->status === 'canceled')
                    <span class="inline-block bg-red-100 text-red-700 py-2 px-3 rounded-md text-sm font-medium">Dibatalkan</span>
                @elseif($deal->status === 'completed')
                    <span class="inline-block bg-green-100 text-green-700 py-2 px-3 rounded-md text-sm font-medium">Selesai</span>
                @elseif($deal->status === 'dispute')
                    <span class="inline-block bg-orange-100 text-orange-700 py-2 px-3 rounded-md text-sm font-medium">Sengketa</span>
                @else
                    <span class="inline-block bg-blue-100 text-blue-700 py-2 px-3 rounded-md text-sm font-medium">Berjalan</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
            <div class="flex items-start gap-3">
                <img src="{{ $deal->buyer->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($deal->buyer->name ?? 'Buyer') }}" alt="Avatar Pembeli" class="w-12 h-12 rounded-full object-cover border border-line">
                <div>
                    <h3 class="text-sm font-medium text-muted mb-1">Informasi Pembeli</h3>
                    <div class="space-y-1 text-sm">
                        <div><span class="text-ink font-medium">{{ $deal->buyer->name ?? '-' }}</span></div>
                        <div class="text-xs text-muted">{{ $deal->buyer->email ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <img src="{{ $deal->seller->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($deal->seller->name ?? 'Seller') }}" alt="Avatar Penjual" class="w-12 h-12 rounded-full object-cover border border-line">
                <div>
                    <h3 class="text-sm font-medium text-muted mb-1">Informasi Penjual</h3>
                    <div class="space-y-1 text-sm">
                        <div><span class="text-ink font-medium">{{ $deal->seller->name ?? '-' }}</span></div>
                        <div class="text-xs text-muted">{{ $deal->seller->email ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-line pt-6">
            <h3 class="text-sm font-medium text-muted mb-3">Informasi Barang</h3>
            <div class="flex items-center gap-4">
                <img src="{{ $deal->product->image_url ?? 'https://via.placeholder.com/80' }}" alt="Produk" class="w-20 h-20 rounded-md object-cover">
                <div>
                    <p class="font-medium text-ink">{{ $deal->product->name ?? '-' }}</p>
                    <p class="text-sm text-muted mt-1">Kategori: {{ $deal->product->category->name ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-line shadow-sm p-6">
            <h3 class="text-sm font-medium text-muted mb-4">Detail Transaksi</h3>
            <div class="space-y-3 text-sm">
                <div><span class="text-muted">Jumlah:</span> <span class="text-ink font-medium">{{ $deal->quantity ?? 0 }} unit</span></div>
                <div><span class="text-muted">Harga Disepakati:</span> <span class="text-ink font-medium">{{ $deal->agreed_price ? 'Rp'.number_format($deal->agreed_price, 0, ',', '.') : '-' }}</span></div>
                @if($deal->status === 'negotiating')
                    <div><span class="text-muted">Penawaran Terakhir:</span> <span class="text-ink font-medium">{{ $deal->current_offer_price ? 'Rp'.number_format($deal->current_offer_price, 0, ',', '.') : '-' }}</span></div>
                @endif
                <div>
                    <span class="text-muted">Metode Pembayaran:</span> 
                    @include('admin.partials.payment-method', ['paymentMethod' => $deal->payment_method])
                </div>
                <div><span class="text-muted">Tanggal Disepakati:</span> <span class="text-ink font-medium">{{ $deal->agreed_at?->format('d M Y H:i') ?? '-' }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-line shadow-sm p-6">
            <h3 class="text-sm font-medium text-muted mb-4">Status Transaksi</h3>
            <div class="space-y-3 text-sm">
                @if($deal->status === 'negotiating')
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
                        <span class="text-ink font-medium">Sedang negosiasi harga</span>
                    </div>
                    <div><span class="text-muted">Penawaran terakhir:</span> <span class="text-ink font-medium">{{ $deal->current_offer_price ? 'Rp'.number_format($deal->current_offer_price, 0, ',', '.') : '-' }}</span></div>
                    <div><span class="text-muted">Menunggu:</span> <span class="text-ink font-medium">Kesepakatan kedua pihak</span></div>
                @elseif(in_array($deal->status, ['agreed', 'active'], true))
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                        <span class="text-ink font-medium">Transaksi disepakati</span>
                    </div>
                    <div><span class="text-muted">Disepakati sejak:</span> <span class="text-ink font-medium">{{ $deal->agreed_at?->format('d M Y H:i') ?? '-' }}</span></div>
                    <div><span class="text-muted">Menunggu:</span> <span class="text-ink font-medium">Penyelesaian transaksi lewat QR</span></div>
                @elseif($deal->status === 'dispute')
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></span>
                        <span class="text-ink font-medium">Dalam sengketa</span>
                    </div>
                    <div><span class="text-muted">Status:</span> <span class="text-ink font-medium">Menunggu keputusan admin</span></div>
                @elseif($deal->status === 'completed')
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        <span class="text-ink font-medium">Transaksi selesai</span>
                    </div>
                    <div><span class="text-muted">Selesai pada:</span> <span class="text-ink font-medium">{{ $deal->completed_at?->format('d M Y H:i') ?? '-' }}</span></div>
                @elseif(in_array($deal->status, ['cancelled', 'canceled'], true))
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        <span class="text-ink font-medium">Transaksi dibatalkan</span>
                    </div>
                    <div><span class="text-muted">Dibatalkan pada:</span> <span class="text-ink font-medium">{{ $deal->cancelled_at?->format('d M Y H:i') ?? '-' }}</span></div>
                    <div><span class="text-muted">Dibatalkan oleh:</span> <span class="text-ink font-medium">{{ $deal->cancelledBy->name ?? 'Sistem' }}</span></div>
                    <div><span class="text-muted">Alasan:</span> <span class="text-ink font-medium">{{ $deal->cancel_reason ?? '-' }}</span></div>
                @endif
            </div>
        </div>
    </div>

    @if($deal->status === 'dispute' && count($deal->reports ?? []) > 0)
    <div class="bg-white rounded-xl border border-line shadow-sm p-6 mb-6">
        <h3 class="text-sm font-medium text-muted mb-4">Laporan Sengketa</h3>
        <div class="space-y-4">
            @foreach($deal->reports as $report)
            <div class="bg-page p-4 rounded-lg">
                <div class="flex justify-between mb-2">
                    <span class="font-medium text-ink">{{ $report->type ?? 'Laporan' }}</span>
                    <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded">{{ $report->status ?? 'pending' }}</span>
                </div>
                <p class="text-sm text-muted">{{ $report->description ?? '-' }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
