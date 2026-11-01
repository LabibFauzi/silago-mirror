@extends('admin.layout.app')
@section('title', 'Barang')

@section('content')
@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp
<div x-data="stockManager()" class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Barang</h1>
        <p class="mt-1 text-sm text-muted">Daftar seluruh barang yang ada di SILAGO</p>
    </div>

    {{-- Kartu Ringkasan Barang --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
            <h3 class="text-sm font-medium text-muted mb-2">Total Semua Barang</h3>
            <div class="text-3xl font-bold text-ink">{{ number_format($allCount ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
            <h3 class="text-sm font-medium text-muted mb-2">Barang Tersedia</h3>
            <div class="text-3xl font-bold text-green-600">{{ number_format($availableCount ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
            <h3 class="text-sm font-medium text-muted mb-2">Barang Terjual</h3>
            <div class="text-3xl font-bold text-red-600">{{ number_format($outCount ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>

    {{-- Tabel Barang --}}
    <div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
        <div class="border-b border-line px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium {{ (!request('status') || request('status') === 'all') ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Semua</a>
                <a href="?status=available" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'available' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Tersedia</a>
                <a href="?status=reserved" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'reserved' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Dipesan</a>
                <a href="?status=out" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'out' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Terjual</a>
                <a href="?status=pending" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'pending' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Pending</a>
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
                        <th class="px-6 py-4">Harga</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Verifikasi</th>
                        <th class="px-6 py-4">Tanggal Posting</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products ?? [] as $product)
                    <tr class="border-b border-line hover:bg-page transition-colors">
                        <td class="px-6 py-4">
                            @php
                                $productImages = $product->images ?? collect();
                                $coverUrl = $product->image_url;
                            @endphp
                            <div class="flex items-center">
                                <button type="button" @click="openDetail({{ $product->id }})"
                                        class="relative mr-3 shrink-0 group" title="Lihat detail barang">
                                    @if($coverUrl)
                                        <img src="{{ $coverUrl }}"
                                             alt="Foto {{ $product->name }}"
                                             class="w-12 h-12 rounded-md object-cover border border-line bg-gray-50 group-hover:opacity-80 transition-opacity"
                                             onerror="this.style.display='none'">
                                    @else
                                        <span class="flex h-12 w-12 items-center justify-center rounded-md border border-line bg-gray-50 text-faint">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25z"/></svg>
                                        </span>
                                    @endif
                                    @if($productImages->count() > 1)
                                        <span class="absolute -bottom-1.5 -right-1.5 rounded-full bg-brand px-1.5 py-0.5 text-[10px] font-bold text-white shadow">
                                            +{{ $productImages->count() - 1 }}
                                        </span>
                                    @endif
                                </button>
                                <div>
                                    <div class="font-medium text-ink">{{ $product->name }}</div>
                                    <div class="text-[11px] text-faint">ID: #{{ $product->id }}{{ $productImages->count() > 0 ? ' · ' . $productImages->count() . ' foto' : '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-ink">{{ $product->seller->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-ink">{{ $product->category->name ?? '-' }}</td>
                        <td class="px-6 py-4 font-semibold text-ink">Rp{{ number_format((float) ($product->price ?? 0), 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @php
                                $statusCls = match($product->status) {
                                    'available' => 'bg-green-100 text-green-700',
                                    'reserved' => 'bg-amber-100 text-amber-700',
                                    'sold' => 'bg-blue-100 text-blue-700',
                                    'hidden' => 'bg-gray-100 text-gray-700',
                                    'archived' => 'bg-yellow-100 text-yellow-700',
                                    default => 'bg-gray-100 text-gray-700'
                                };
                            @endphp
                            <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusCls }}">{{ ucfirst($product->status) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $verifCls = match($product->verification_status) {
                                    'verified' => 'bg-green-100 text-green-700',
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    default => 'bg-gray-100 text-gray-700'
                                };
                            @endphp
                            <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $verifCls }}">{{ ucfirst($product->verification_status) }}</span>
                        </td>
                        <td class="px-6 py-4 text-muted text-xs">{{ $product->created_at ? $product->created_at->format('d M Y H:i') : '-' }}</td>
                        
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <button @click="openDetail({{ $product->id }})" class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-green-700/20 transition hover:bg-green-700 hover:shadow-md active:scale-95"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Detail</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-muted">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-10 w-10 text-faint" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <span>Tidak ada data barang</span>
                            </div>
                        </td>
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

    {{-- ═══════════ Modal Detail Barang (preview foto + informasi lengkap) ═══════════ --}}
    <div x-show="showDetailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="showDetailModal" @click="closeDetail()" class="fixed inset-0 bg-black/60 transition-opacity"></div>

            <div x-show="showDetailModal" x-transition
                 class="relative z-10 flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">

                {{-- Header --}}
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-line px-6 py-4">
                    <div class="min-w-0">
                        <h3 class="truncate text-lg font-bold text-ink" x-text="detail.name || 'Detail Barang'"></h3>
                        <p class="text-xs text-muted">
                            ID #<span x-text="detail.id || '-'"></span>
                            <template x-if="detail.photos_count">
                                <span> · <span x-text="detail.photos_count"></span> foto</span>
                            </template>
                        </p>
                    </div>
                    <button type="button" @click="closeDetail()" class="shrink-0 rounded-lg p-1.5 text-faint transition-colors hover:bg-gray-100 hover:text-ink">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    {{-- Loading --}}
                    <div x-show="detailLoading" class="flex flex-col items-center justify-center gap-3 py-24 text-muted">
                        <svg class="h-8 w-8 animate-spin text-brand" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        <span class="text-xs">Memuat detail barang...</span>
                    </div>

                    <div x-show="!detailLoading" class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-2">
                        {{-- Galeri foto --}}
                        <div>
                            <div class="relative aspect-[4/3] w-full overflow-hidden rounded-xl border border-line bg-gray-100">
                                <template x-if="detailImages.length > 0">
                                    <img :src="detailImages[detailIndex]?.url" :alt="detail.name"
                                         class="h-full w-full object-contain">
                                </template>

                                <template x-if="detailImages.length === 0">
                                    <div class="flex h-full w-full flex-col items-center justify-center gap-2 text-faint">
                                        <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/></svg>
                                        <span class="text-xs">Belum ada foto</span>
                                    </div>
                                </template>

                                <template x-if="detailImages.length > 1">
                                    <div>
                                        <button type="button" @click="detailPrev()"
                                                class="absolute left-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/50 text-white transition hover:bg-black/70">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                        </button>
                                        <button type="button" @click="detailNext()"
                                                class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/50 text-white transition hover:bg-black/70">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                        <div class="absolute bottom-2 right-2 rounded-full bg-black/60 px-2 py-0.5 text-[11px] font-semibold text-white">
                                            <span x-text="detailIndex + 1"></span>/<span x-text="detailImages.length"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Thumbnail --}}
                            <template x-if="detailImages.length > 1">
                                <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                                    <template x-for="(img, idx) in detailImages" :key="img.id">
                                        <button type="button" @click="detailIndex = idx"
                                                class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border-2 transition-colors"
                                                :class="detailIndex === idx ? 'border-brand' : 'border-line hover:border-brand/60'"
                                                :title="'Foto ' + (idx + 1)">
                                            <img :src="img.url" class="h-full w-full object-cover" alt="">
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Informasi barang --}}
                        <div class="space-y-5">
                            <div>
                                <div class="text-2xl font-bold text-brand">
                                    Rp<span x-text="formatNumber(detail.price)"></span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                          :class="statusClass(detail.status)"
                                          x-text="statusLabel(detail.status)"></span>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                          :class="verifClass(detail.verification_status)"
                                          x-text="verifLabel(detail.verification_status)"></span>
                                    <span x-show="detail.condition"
                                          class="rounded-full bg-page px-2.5 py-0.5 text-xs font-medium text-muted"
                                          x-text="detail.condition"></span>
                                </div>
                            </div>

                            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                                <div>
                                    <dt class="text-[11px] uppercase tracking-wide text-faint">Penjual</dt>
                                    <dd class="font-medium text-ink" x-text="detail.seller?.name || '-'"></dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] uppercase tracking-wide text-faint">Kategori</dt>
                                    <dd class="font-medium text-ink" x-text="detail.category?.name || '-'"></dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] uppercase tracking-wide text-faint">Email Penjual</dt>
                                    <dd class="truncate font-medium text-ink" x-text="detail.seller?.email || '-'"></dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] uppercase tracking-wide text-faint">Tanggal Posting</dt>
                                    <dd class="font-medium text-ink" x-text="formatDate(detail.created_at)"></dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] uppercase tracking-wide text-faint">Kedaluwarsa</dt>
                                    <dd class="font-medium text-ink" x-text="formatDate(detail.expires_at)"></dd>
                                </div>
                            </dl>

                            <template x-if="detail.description">
                                <div>
                                    <h4 class="text-[11px] font-semibold uppercase tracking-wide text-faint">Deskripsi</h4>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink" x-text="detail.description"></p>
                                </div>
                            </template>

                            <template x-if="detail.specification">
                                <div>
                                    <h4 class="text-[11px] font-semibold uppercase tracking-wide text-faint">Spesifikasi</h4>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink" x-text="detail.specification"></p>
                                </div>
                            </template>

                            <template x-if="detail.completeness">
                                <div>
                                    <h4 class="text-[11px] font-semibold uppercase tracking-wide text-faint">Kelengkapan</h4>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink" x-text="detail.completeness"></p>
                                </div>
                            </template>

                            <template x-if="detail.sell_reason">
                                <div>
                                    <h4 class="text-[11px] font-semibold uppercase tracking-wide text-faint">Alasan Dijual</h4>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink" x-text="detail.sell_reason"></p>
                                </div>
                            </template>

                            <template x-if="detail.rejection_note">
                                <div class="rounded-lg border border-red-200 bg-red-50 p-3">
                                    <h4 class="text-xs font-semibold text-red-700">Catatan Penolakan</h4>
                                    <p class="mt-1 whitespace-pre-line text-xs text-red-700" x-text="detail.rejection_note"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex shrink-0 justify-end gap-2 border-t border-line bg-gray-50/80 px-6 py-3.5">
                    <button type="button" @click="closeDetail()" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-muted transition-colors hover:bg-gray-200">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function stockManager() {
    const hasErrors = {{ ($errors->any() || session('error')) && old('_form_action') ? 'true' : 'false' }};
    const wasEdit = {{ old('_method') === 'PUT' ? 'true' : 'false' }};
    const oldFormAction = @json(old('_form_action', ''));
    const oldInputs = @json(old());

    delete oldInputs._token;
    delete oldInputs._method;
    delete oldInputs._form_action;

    return {
        showModal: hasErrors,
        showDeleteModal: false,
        isEdit: wasEdit,
        formAction: oldFormAction || (wasEdit ? '' : @json(route('admin.stock.store'))),
        formData: hasErrors ? oldInputs : { status: 'available', verification_status: 'verified' },
        photoPreview: null,
        deleteAction: '',
        deleteLabel: '',

        // State modal detail barang
        showDetailModal: false,
        detail: {},
        detailIndex: 0,
        detailLoading: false,

        get detailImages() {
            return this.detail && this.detail.images ? this.detail.images : [];
        },

        openCreate() {
            this.isEdit = false;
            this.formAction = @json(route('admin.stock.store'));
            this.formData = { status: 'available', verification_status: 'verified' };
            this.photoPreview = null;
            document.querySelectorAll('input[type="file"]').forEach(input => input.value = '');
            this.showModal = true;
        },

        async openEdit(id) {
            this.isEdit = true;
            this.formAction = @json(url('admin/stock')) + '/' + id;
            this.formData = {};
            this.photoPreview = null;
            document.querySelectorAll('input[type="file"]').forEach(input => input.value = '');

            try {
                const res = await fetch(@json(url('admin/stock')) + '/' + id + '/json');
                if (res.ok) {
                    const data = await res.json();
                    this.formData = data;
                }
            } catch (e) {
                console.error('Gagal memuat data barang:', e);
            }

            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.formData = {};
            this.photoPreview = null;
        },

        previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                this.photoPreview = URL.createObjectURL(file);
            } else {
                this.photoPreview = null;
            }
        },

        async openDetail(id) {
            this.showDetailModal = true;
            this.detailLoading = true;
            this.detail = {};
            this.detailIndex = 0;

            try {
                const res = await fetch(@json(url('admin/stock')) + '/' + id + '/json');
                if (res.ok) {
                    this.detail = await res.json();
                }
            } catch (e) {
                console.error('Gagal memuat detail barang:', e);
            }

            this.detailLoading = false;
        },

        closeDetail() {
            this.showDetailModal = false;
            this.detail = {};
            this.detailIndex = 0;
        },

        detailPrev() {
            const total = this.detailImages.length;
            if (total < 2) return;
            this.detailIndex = (this.detailIndex - 1 + total) % total;
        },

        detailNext() {
            const total = this.detailImages.length;
            if (total < 2) return;
            this.detailIndex = (this.detailIndex + 1) % total;
        },

        formatNumber(value) {
            return Number(value || 0).toLocaleString('id-ID');
        },

        formatDate(value) {
            if (!value) return '-';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '-';
            return date.toLocaleString('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short',
            });
        },

        statusLabel(value) {
            return {
                available: 'Tersedia',
                reserved: 'Dipesan',
                sold: 'Terjual',
                hidden: 'Disembunyikan',
                archived: 'Diarsipkan',
            }[value] || value || '-';
        },

        statusClass(value) {
            return {
                available: 'bg-green-100 text-green-700',
                reserved: 'bg-amber-100 text-amber-700',
                sold: 'bg-blue-100 text-blue-700',
                hidden: 'bg-gray-100 text-gray-700',
                archived: 'bg-yellow-100 text-yellow-700',
            }[value] || 'bg-gray-100 text-gray-700';
        },

        verifLabel(value) {
            return {
                verified: 'Terverifikasi',
                pending: 'Menunggu Verifikasi',
                rejected: 'Ditolak',
            }[value] || value || '-';
        },

        verifClass(value) {
            return {
                verified: 'bg-green-100 text-green-700',
                pending: 'bg-yellow-100 text-yellow-700',
                rejected: 'bg-red-100 text-red-700',
            }[value] || 'bg-gray-100 text-gray-700';
        },

        confirmDelete(id, label) {
            this.deleteAction = @json(url('admin/stock')) + '/' + id;
            this.deleteLabel = label;
            this.showDeleteModal = true;
        }
    };
}
</script>
@endpush
@endsection