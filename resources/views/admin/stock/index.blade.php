@extends('admin.layout.app')
@section('title', 'Stok Barang')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <h3 class="text-sm font-medium text-muted mb-2">Total Barang</h3>
        <div class="text-3xl font-bold text-ink">{{ number_format($allCount ?? 0, 0, ',', '.') }}</div>
    </div>
    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <h3 class="text-sm font-medium text-muted mb-2">Tersedia</h3>
        <div class="text-3xl font-bold text-green-600">{{ number_format($availableCount ?? 0, 0, ',', '.') }}</div>
    </div>
    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <h3 class="text-sm font-medium text-muted mb-2">Habis</h3>
        <div class="text-3xl font-bold text-red-600">{{ number_format($outCount ?? 0, 0, ',', '.') }}</div>
    </div>
</div>

<div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
    <div class="border-b border-line px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium {{ (!request('status') || request('status') === 'all') ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Semua</a>
            <a href="?status=available" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'available' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Tersedia</a>
            <a href="?status=out" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'out' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Habis</a>
        </div>
        <form method="GET" action="" class="w-full sm:w-72">
            <input type="hidden" name="status" value="{{ request('status', '') }}">
            <input type="text" name="q" placeholder="Cari barang / penjual / kategori..." class="w-full px-4 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" value="{{ request('q', '') }}">
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-muted uppercase bg-page border-b border-line">
                <tr>
                    <th class="px-6 py-4">Barang</th>
                    <th class="px-6 py-4">Penjual</th>
                    <th class="px-6 py-4">Kategori</th>
                    <th class="px-6 py-4">Deskripsi</th>
                    <th class="px-6 py-4">Harga</th>
                    <th class="px-6 py-4">Tanggal Posting</th>
                    <th class="px-6 py-4">Stok</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products ?? [] as $product)
                <tr class="border-b border-line hover:bg-page">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img src="{{ $product->image_url ?? 'https://via.placeholder.com/40' }}" alt="Foto" class="w-10 h-10 rounded-md object-cover mr-3">
                            <div class="font-medium text-ink">{{ $product->name }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">{{ $product->seller->name ?? '-' }}</td>
                    <td class="px-6 py-4">{{ $product->category->name ?? '-' }}</td>
                    <td class="px-6 py-4 max-w-xs truncate">{{ $product->description ?? '-' }}</td>
                    <td class="px-6 py-4">Rp{{ number_format($product->price ?? 0, 0, ',', '.') }}</td>
                    <td class="px-6 py-4">{{ $product->created_at ? $product->created_at->format('d M Y') : '-' }}</td>
                    <td class="px-6 py-4 font-bold {{ $product->stock == 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $product->stock }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-muted">Tidak ada barang</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if(isset($products) && method_exists($products, 'links'))
    <div class="px-6 py-4 border-t border-line">
        {{ $products->links() }}
    </div>
    @endif
</div>
@endsection