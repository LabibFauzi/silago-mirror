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

<div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden" x-data="{
    showModal: false,
    selectedProduct: null,
    galleryIndex: 0,
    get galleryImages() {
        return (this.selectedProduct && this.selectedProduct.image_urls) ? this.selectedProduct.image_urls : [];
    },
    prevImage() {
        const total = this.galleryImages.length;
        if (total < 2) return;
        this.galleryIndex = (this.galleryIndex - 1 + total) % total;
    },
    nextImage() {
        const total = this.galleryImages.length;
        if (total < 2) return;
        this.galleryIndex = (this.galleryIndex + 1) % total;
    }
}">
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
                        @php
                            $verificationImages = $product->images ?? collect();
                        @endphp
                        <div class="flex items-center">
                            <button type="button" @click="selectedProduct = {{ json_encode($product) }}; galleryIndex = 0; showModal = true"
                                    class="relative mr-3 shrink-0" title="Lihat foto barang">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="Foto {{ $product->name }}" class="h-11 w-11 rounded-md border border-line bg-gray-50 object-cover">
                                @else
                                    <span class="flex h-11 w-11 items-center justify-center rounded-md border border-line bg-gray-50 text-faint">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/></svg>
                                    </span>
                                @endif
                                @if($verificationImages->count() > 1)
                                    <span class="absolute -bottom-1.5 -right-1.5 rounded-full bg-brand px-1.5 py-0.5 text-[10px] font-bold text-white shadow">
                                        +{{ $verificationImages->count() - 1 }}
                                    </span>
                                @endif
                            </button>
                            <div>
                                <div class="font-medium text-ink">{{ $product->name }}</div>
                                <div class="text-xs text-muted">Rp{{ number_format($product->price ?? 0, 0, ',', '.') }}{{ $verificationImages->count() > 0 ? ' · ' . $verificationImages->count() . ' foto' : '' }}</div>
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
                        <button @click="selectedProduct = {{ json_encode($product) }}; galleryIndex = 0; showModal = true" class="text-brand hover:underline font-medium">Tinjau</button>
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
                    <div class="relative h-64 w-full overflow-hidden rounded-lg border border-line bg-gray-100">
                        <template x-if="galleryImages.length > 0">
                            <img :src="galleryImages[galleryIndex]" class="h-full w-full object-contain" alt="">
                        </template>
                        <template x-if="galleryImages.length === 0">
                            <div class="flex h-full w-full items-center justify-center text-sm text-muted">Belum ada foto</div>
                        </template>

                        <template x-if="galleryImages.length > 1">
                            <div>
                                <button type="button" @click="prevImage()"
                                        class="absolute left-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button type="button" @click="nextImage()"
                                        class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </button>
                                <div class="absolute bottom-2 right-2 rounded-full bg-black/60 px-2 py-0.5 text-xs font-semibold text-white">
                                    <span x-text="galleryIndex + 1"></span>/<span x-text="galleryImages.length"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <template x-if="galleryImages.length > 1">
                        <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
                            <template x-for="(url, idx) in galleryImages" :key="idx">
                                <button type="button" @click="galleryIndex = idx"
                                        class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border-2 transition-colors"
                                        :class="galleryIndex === idx ? 'border-brand' : 'border-line hover:border-brand/60'">
                                    <img :src="url" class="h-full w-full object-cover" alt="">
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                    <div><span class="text-muted block">Penjual:</span> <span class="font-medium text-ink" x-text="selectedProduct?.seller?.name || '-'"></span></div>
                    <div><span class="text-muted block">Kategori:</span> <span class="font-medium text-ink" x-text="selectedProduct?.category?.name || '-'"></span></div>
                    <div><span class="text-muted block">Harga:</span> <span class="font-medium text-ink" x-text="'Rp ' + (selectedProduct?.price || 0)"></span></div>
                    <div><span class="text-muted block">Stok:</span> <span class="font-medium text-ink" x-text="selectedProduct?.stock || 0"></span></div>
                    <div class="col-span-2"><span class="text-muted block">Hasil Pindai (AI):</span> <span class="font-medium text-ink" x-text="selectedProduct?.scan_result || '-'"></span></div>
                </div>

                {{-- Detail informasi barang --}}
                <div class="mb-6 space-y-3 text-sm">
                    <template x-if="selectedProduct?.description">
                        <div>
                            <span class="text-muted block">Deskripsi</span>
                            <p class="whitespace-pre-line font-medium text-ink" x-text="selectedProduct.description"></p>
                        </div>
                    </template>
                    <template x-if="selectedProduct?.specification">
                        <div>
                            <span class="text-muted block">Spesifikasi</span>
                            <p class="whitespace-pre-line font-medium text-ink" x-text="selectedProduct.specification"></p>
                        </div>
                    </template>
                    <template x-if="selectedProduct?.completeness">
                        <div>
                            <span class="text-muted block">Kelengkapan</span>
                            <p class="whitespace-pre-line font-medium text-ink" x-text="selectedProduct.completeness"></p>
                        </div>
                    </template>
                    <template x-if="selectedProduct?.sell_reason">
                        <div>
                            <span class="text-muted block">Alasan Dijual</span>
                            <p class="whitespace-pre-line font-medium text-ink" x-text="selectedProduct.sell_reason"></p>
                        </div>
                    </template>
                    <template x-if="selectedProduct?.condition">
                        <div>
                            <span class="text-muted block">Kondisi</span>
                            <p class="font-medium text-ink" x-text="selectedProduct.condition"></p>
                        </div>
                    </template>
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
