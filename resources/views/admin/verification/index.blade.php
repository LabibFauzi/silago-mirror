@extends('admin.layout.app')
@section('title', 'Verifikasi Barang')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <h3 class="text-sm font-medium text-muted mb-2">Menunggu Verifikasi</h3>
        <div class="text-3xl font-bold text-ink">{{ number_format($pendingCount ?? 0, 0, ',', '.') }}</div>
    </div>
    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <h3 class="text-sm font-medium text-muted mb-2">Disetujui</h3>
        <div class="text-3xl font-bold text-ink">{{ number_format($totalApprovedCount ?? 0, 0, ',', '.') }}</div>
    </div>
    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <h3 class="text-sm font-medium text-muted mb-2">Ditolak</h3>
        <div class="text-3xl font-bold text-ink">{{ number_format($totalRejectedCount ?? 0, 0, ',', '.') }}</div>
    </div>
</div>

<div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden" x-data="{ showModal: false, selectedProduct: null }">
    <div class="border-b border-line px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium {{ (!request('status') || request('status') === 'all') ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Semua</a>
            <a href="?status=pending" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'pending' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Menunggu</a>
            <a href="?status=verified" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'verified' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Disetujui</a>
            <a href="?status=rejected" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'rejected' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Ditolak</a>
        </div>
        <form method="GET" action="" class="w-full sm:w-72">
            <input type="hidden" name="status" value="{{ request('status', '') }}">
            <input type="text" name="q" placeholder="Cari barang / penjual..." class="w-full px-4 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" value="{{ request('q', '') }}">
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-muted uppercase bg-page border-b border-line">
                <tr>
                    <th class="px-6 py-4">Barang</th>
                    <th class="px-6 py-4">Penjual</th>
                    <th class="px-6 py-4">Kategori</th>
                    <th class="px-6 py-4">Waktu</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products ?? [] as $product)
                <tr class="border-b border-line hover:bg-page">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img src="{{ $product->image_url ?? 'https://via.placeholder.com/40' }}" alt="Foto" class="w-10 h-10 rounded-md object-cover mr-3">
                            <div>
                                <div class="font-medium text-ink">{{ $product->name }}</div>
                                <div class="text-xs text-muted">Rp{{ number_format($product->price ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">{{ $product->seller->name ?? '-' }}</td>
                    <td class="px-6 py-4">{{ $product->category->name ?? '-' }}</td>
                    <td class="px-6 py-4">{{ $product->created_at ? $product->created_at->format('d M Y, H:i') : '-' }}</td>
                    <td class="px-6 py-4">
                        @if($product->verification_status === 'pending')
                            <span class="bg-yellow-100 text-yellow-700 py-1 px-2 rounded-md text-xs">Menunggu</span>
                        @elseif($product->verification_status === 'verified')
                            <span class="bg-green-100 text-green-700 py-1 px-2 rounded-md text-xs">Disetujui</span>
                        @elseif($product->verification_status === 'rejected')
                            <span class="bg-red-100 text-red-700 py-1 px-2 rounded-md text-xs">Ditolak</span>
                        @else
                            <span class="bg-gray-100 text-gray-600 py-1 px-2 rounded-md text-xs">{{ $product->verification_status ?? '-' }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button @click="selectedProduct = {{ json_encode($product) }}; showModal = true" class="text-brand hover:underline font-medium">Tinjau</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-muted">Tidak ada barang</td>
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

    <!-- Modal Tinjau -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50"></div>
            
            <div x-show="showModal" class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-bold text-ink" x-text="selectedProduct?.name"></h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="mb-4">
                    <img :src="selectedProduct?.image_url || 'https://via.placeholder.com/800x400'" class="w-full h-64 object-cover rounded-lg">
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                    <div><span class="text-muted block">Penjual:</span> <span class="font-medium text-ink" x-text="selectedProduct?.seller?.name || '-'"></span></div>
                    <div><span class="text-muted block">Kategori:</span> <span class="font-medium text-ink" x-text="selectedProduct?.category?.name || '-'"></span></div>
                    <div><span class="text-muted block">Harga:</span> <span class="font-medium text-ink" x-text="'Rp ' + (selectedProduct?.price || 0)"></span></div>
                    <div><span class="text-muted block">Stok:</span> <span class="font-medium text-ink" x-text="selectedProduct?.stock || 0"></span></div>
                    <div class="col-span-2"><span class="text-muted block">Hasil Pindai (AI):</span> <span class="font-medium text-ink" x-text="selectedProduct?.scan_result || '-'"></span></div>
                </div>

                <template x-if="selectedProduct?.verification_status === 'pending'">
                    <div class="flex gap-4">
                        <form method="POST" :action="`/admin/verification/${selectedProduct?.id}/approve`" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">Setujui Barang</button>
                        </form>
                        
                        <div x-data="{ showRejectForm: false }" class="flex-1">
                            <button x-show="!showRejectForm" @click="showRejectForm = true" type="button" class="w-full bg-red-100 hover:bg-red-200 text-red-700 font-medium py-2 px-4 rounded-lg">Tolak...</button>
                            
                            <form x-show="showRejectForm" method="POST" :action="`/admin/verification/${selectedProduct?.id}/reject`" class="mt-2">
                                @csrf
                                <textarea name="rejection_note" rows="2" class="w-full p-2 border border-line rounded-lg text-sm mb-2" placeholder="Alasan penolakan..." required></textarea>
                                <div class="flex gap-2">
                                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg text-sm">Konfirmasi Tolak</button>
                                    <button type="button" @click="showRejectForm = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg text-sm">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
                <template x-if="selectedProduct?.verification_status !== 'pending'">
                    <div class="text-center p-4 bg-gray-50 rounded-lg text-sm text-muted">
                        Barang ini sudah <span x-text="selectedProduct?.verification_status" class="font-bold"></span>.
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
