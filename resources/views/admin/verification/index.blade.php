@extends('admin.layout.app')
@section('title', 'Verifikasi Barang')

@section('content')
<div>
    <h1 class="text-2xl font-bold tracking-tight text-ink">Verifikasi Barang</h1>
    <p class="mt-1 text-sm text-muted">Tinjau dan setujui barang yang diajukan penjual</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 my-6">
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
                        <button @click="selectedProduct = {{ json_encode($product) }}; galleryIndex = 0; showModal = true" class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-green-700/20 transition hover:bg-green-700 hover:shadow-md active:scale-95"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Tinjau</button>
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

    <!-- Modal Tinjau Verifikasi -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 bg-black/60 transition-opacity"></div>

            <div x-show="showModal" x-transition
                 class="relative z-10 flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">

                {{-- Header --}}
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-line px-6 py-4">
                    <div class="min-w-0">
                        <div class="mb-1.5 flex flex-wrap items-center gap-2">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-faint">Tinjau Verifikasi</span>
                            <template x-if="selectedProduct?.verification_status === 'pending'">
                                <span class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800">Menunggu Verifikasi</span>
                            </template>
                            <template x-if="selectedProduct?.verification_status === 'verified'">
                                <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Disetujui</span>
                            </template>
                            <template x-if="selectedProduct?.verification_status === 'rejected'">
                                <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">Ditolak</span>
                            </template>
                        </div>
                        <h3 class="truncate text-lg font-bold text-ink" x-text="selectedProduct?.name || 'Detail Barang'"></h3>
                        <p class="text-xs text-muted">ID #<span x-text="selectedProduct?.id || '-'"></span></p>
                    </div>
                    <button @click="showModal = false" class="rounded-lg p-1.5 text-faint transition-colors hover:bg-gray-100 hover:text-ink">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-5">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                        {{-- Gallery --}}
                        <div>
                            <div class="relative h-64 w-full overflow-hidden rounded-xl border border-line bg-gray-100">
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

                        {{-- Details --}}
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-3 rounded-xl border border-line bg-page/60 p-4 text-sm">
                                <div>
                                    <span class="block text-xs text-muted">Penjual</span>
                                    <span class="font-medium text-ink" x-text="selectedProduct?.seller?.name || '-'"></span>
                                </div>
                                <div>
                                    <span class="block text-xs text-muted">Kategori</span>
                                    <span class="font-medium text-ink" x-text="selectedProduct?.category?.name || '-'"></span>
                                </div>
                                <div>
                                    <span class="block text-xs text-muted">Harga</span>
                                    <span class="font-semibold text-ink" x-text="'Rp ' + Number(selectedProduct?.price || 0).toLocaleString('id-ID')"></span>
                                </div>
                                <div>
                                    <span class="block text-xs text-muted">Kondisi</span>
                                    <span class="font-medium text-ink" x-text="selectedProduct?.condition || '-'"></span>
                                </div>
                            </div>

                            {{-- Moderasi Konten --}}
                            <div class="rounded-xl border p-4"
                                 :class="selectedProduct?.scan_result === 'flagged' ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50'">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-bold"
                                        :class="selectedProduct?.scan_result === 'flagged' ? 'text-red-800' : 'text-green-800'">Moderasi Konten</h4>
                                    <template x-if="selectedProduct?.scan_result === 'flagged'">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M4.5 19h15a1.5 1.5 0 001.3-2.25l-7.5-13a1.5 1.5 0 00-2.6 0l-7.5 13A1.5 1.5 0 004.5 19z"/></svg>
                                            Ditandai
                                        </span>
                                    </template>
                                    <template x-if="selectedProduct?.scan_result !== 'flagged'">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-600 px-3 py-1 text-xs font-bold text-white">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            Aman
                                        </span>
                                    </template>
                                </div>
                                <p class="mt-2 text-xs"
                                   :class="selectedProduct?.scan_result === 'flagged' ? 'text-red-700' : 'text-green-700'">
                                    <span x-show="selectedProduct?.scan_result === 'flagged'">Teks barang terindikasi mengandung kata terlarang.</span>
                                    <span x-show="selectedProduct?.scan_result !== 'flagged'">Tidak ada kata terlarang yang terdeteksi pada nama atau deskripsi barang.</span>
                                </p>
                                <template x-if="selectedProduct?.scan_note">
                                    <div class="mt-2 rounded-lg bg-white/70 px-3 py-2 text-xs text-ink">
                                        Kata terdeteksi: <span class="font-bold" x-text="selectedProduct.scan_note"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Detail barang --}}
                            <div class="space-y-3 text-sm">
                                <template x-if="selectedProduct?.description">
                                    <div>
                                        <span class="block text-xs text-muted">Deskripsi</span>
                                        <p class="whitespace-pre-line font-medium text-ink" x-text="selectedProduct.description"></p>
                                    </div>
                                </template>
                                <template x-if="selectedProduct?.specification">
                                    <div>
                                        <span class="block text-xs text-muted">Spesifikasi</span>
                                        <p class="whitespace-pre-line font-medium text-ink" x-text="selectedProduct.specification"></p>
                                    </div>
                                </template>
                                <template x-if="selectedProduct?.completeness">
                                    <div>
                                        <span class="block text-xs text-muted">Kelengkapan</span>
                                        <p class="whitespace-pre-line font-medium text-ink" x-text="selectedProduct.completeness"></p>
                                    </div>
                                </template>
                                <template x-if="selectedProduct?.sell_reason">
                                    <div>
                                        <span class="block text-xs text-muted">Alasan Dijual</span>
                                        <p class="whitespace-pre-line font-medium text-ink" x-text="selectedProduct.sell_reason"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="shrink-0 border-t border-line bg-gray-50/80 px-6 py-4">
                    <template x-if="selectedProduct?.verification_status === 'pending'">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                            <form method="POST" :action="`/admin/verification/${selectedProduct?.id}/approve`" class="sm:flex-1">
                                @csrf
                                <button type="submit" class="w-full rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700">Setujui Barang</button>
                            </form>

                            <div x-data="{ showRejectForm: false }" class="sm:flex-1">
                                <button x-show="!showRejectForm" @click="showRejectForm = true" type="button"
                                        class="w-full rounded-lg border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100">Tolak Barang</button>
                                <form x-show="showRejectForm" method="POST" :action="`/admin/verification/${selectedProduct?.id}/reject`" class="space-y-2">
                                    @csrf
                                    <textarea name="rejection_note" rows="2" class="w-full rounded-lg border border-line p-2 text-sm" placeholder="Alasan penolakan..." required></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Konfirmasi Tolak</button>
                                        <button type="button" @click="showRejectForm = false" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-muted hover:bg-gray-200">Batal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>
                    <template x-if="selectedProduct?.verification_status === 'verified'">
                        <div class="flex items-center gap-2 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Barang ini sudah disetujui.
                        </div>
                    </template>
                    <template x-if="selectedProduct?.verification_status === 'rejected'">
                        <div class="flex items-center gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Barang ini sudah ditolak.
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
