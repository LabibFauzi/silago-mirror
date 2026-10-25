@extends('admin.layout.app')
@section('title', 'Stok Barang')

@section('content')
@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp
<div x-data="stockManager()" class="space-y-6">

    {{-- Banner Notifikasi Mode Maintenance --}}
    @if(!empty($isMaintenance))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 text-lg">🛠️</span>
            <div>
                <p class="text-sm font-bold text-red-800">Mode Maintenance Aktif — CRUD Barang Terbuka</p>
                <p class="text-xs text-red-700">Anda dapat menambah, mengubah, dan menghapus data barang secara langsung di halaman ini.</p>
            </div>
        </div>
        <button type="button" @click="openCreate()" class="flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Barang
        </button>
    </div>
    @endif

    {{-- Kartu Ringkasan Stok --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
            <h3 class="text-sm font-medium text-muted mb-2">Total Semua Barang</h3>
            <div class="text-3xl font-bold text-ink">{{ number_format($allCount ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
            <h3 class="text-sm font-medium text-muted mb-2">Stok Tersedia</h3>
            <div class="text-3xl font-bold text-green-600">{{ number_format($availableCount ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
            <h3 class="text-sm font-medium text-muted mb-2">Stok Habis</h3>
            <div class="text-3xl font-bold text-red-600">{{ number_format($outCount ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>

    {{-- Tabel Barang --}}
    <div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
        <div class="border-b border-line px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium {{ (!request('status') || request('status') === 'all') ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Semua</a>
                <a href="?status=available" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'available' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Tersedia</a>
                <a href="?status=out" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'out' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Habis</a>
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
                        <th class="px-6 py-4">Stok</th>
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
                        <td class="px-6 py-4 font-bold {{ $product->stock == 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $product->stock }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusCls = match($product->status) {
                                    'available' => 'bg-green-100 text-green-700',
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
                        
                        @if(!empty($isMaintenance))
                        <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                            <button @click="openDetail({{ $product->id }})" class="text-brand hover:underline text-xs font-semibold">Detail</button>
                            <button @click="openEdit({{ $product->id }})" class="text-blue-600 hover:underline text-xs font-semibold">Ubah</button>
                            <button @click="confirmDelete({{ $product->id }}, '{{ addslashes($product->name) }}')" class="text-red-600 hover:underline text-xs font-semibold">Hapus</button>
                        </td>
                        @else
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <button @click="openDetail({{ $product->id }})" class="text-brand hover:underline text-xs font-semibold">Detail</button>
                        </td>
                        @endif
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

    {{-- ═══════════ Modal Tambah / Ubah Barang (Mode Maintenance) ═══════════ --}}
    @if(!empty($isMaintenance))
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="showModal" @click="closeModal()" class="fixed inset-0 bg-black/50 transition-opacity"></div>

            <div x-show="showModal" x-transition
                 class="relative z-10 w-full max-w-3xl rounded-2xl bg-white shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

                <form :action="formAction" method="POST" enctype="multipart/form-data" class="flex flex-col h-full max-h-[90vh]">
                    @csrf
                    <input type="hidden" name="_form_action" :value="formAction">
                    <input type="hidden" name="_method" value="PUT" x-bind:disabled="!isEdit">

                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between border-b border-line px-6 py-4 bg-white shrink-0">
                        <div>
                            <h3 class="text-lg font-bold text-ink" x-text="isEdit ? 'Ubah Data Barang' : 'Tambah Barang Baru'"></h3>
                            <p class="text-xs text-muted">Kelola data stok barang langsung saat Mode Maintenance aktif.</p>
                        </div>
                        <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-faint hover:bg-gray-100 hover:text-ink transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">

                        {{-- Alert Error Validasi --}}
                        @if($errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                            <div class="flex items-center gap-2 font-semibold text-red-800 text-sm mb-1.5">
                                <svg class="h-5 w-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Gagal menyimpan data! Mohon periksa {{ $errors->count() }} kesalahan input:</span>
                            </div>
                            <ul class="list-disc pl-7 text-xs text-red-700 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {{-- 1. Penjual (User Select) --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Penjual (User) <span class="text-red-500">*</span></label>
                                <select name="seller_id" x-model="formData.seller_id"
                                        class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error('seller_id') border-red-500 bg-red-50/20 @else border-line @enderror">
                                    <option value="">— Pilih Penjual —</option>
                                    @foreach($sellers as $s)
                                        <option value="{{ $s->id }}">#{{ $s->id }} - {{ $s->name }} ({{ $s->email }}{{ $s->nim ? ' / ' . $s->nim : '' }})</option>
                                    @endforeach
                                </select>
                                @error('seller_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 2. Kategori (Category Select) --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Kategori Barang <span class="text-red-500">*</span></label>
                                <select name="category_id" x-model="formData.category_id"
                                        class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error('category_id') border-red-500 bg-red-50/20 @else border-line @enderror">
                                    <option value="">— Pilih Kategori —</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">#{{ $c->id }} - {{ $c->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 3. Nama Barang --}}
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Nama Barang <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="formData.name" placeholder="Contoh: Modul Kuliah Algoritma & Pemrograman"
                                       class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error('name') border-red-500 bg-red-50/20 @else border-line @enderror">
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 4. Harga Barang --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Harga Barang (Rp) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-faint">Rp</span>
                                    <input type="number" min="0" step="100" name="price" x-model="formData.price" placeholder="0"
                                           class="w-full rounded-lg border bg-white py-2 pl-9 pr-3 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error('price') border-red-500 bg-red-50/20 @else border-line @enderror">
                                </div>
                                @error('price')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 5. Stok Barang --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Stok Barang <span class="text-red-500">*</span></label>
                                <input type="number" min="0" step="1" name="stock" x-model="formData.stock" placeholder="1"
                                       class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error('stock') border-red-500 bg-red-50/20 @else border-line @enderror">
                                @error('stock')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 6. Kondisi Barang --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Kondisi Barang</label>
                                <select name="condition" x-model="formData.condition"
                                        class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand border-line">
                                    <option value="">— Pilih Kondisi —</option>
                                    <option value="Baru (BNIB)">Baru (BNIB - Segel Pabrik)</option>
                                    <option value="Baru">Baru (Buka Segel / Belum Dipakai)</option>
                                    <option value="Bekas Layak">Bekas Layak Pakai</option>
                                    <option value="Bekas">Bekas (Minus Ringan / Pemakaian)</option>
                                </select>
                            </div>

                            {{-- 7. Status Barang --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Status Barang <span class="text-red-500">*</span></label>
                                <select name="status" x-model="formData.status"
                                        class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error('status') border-red-500 bg-red-50/20 @else border-line @enderror">
                                    <option value="available">Available (Tersedia)</option>
                                    <option value="sold">Sold (Terjual)</option>
                                    <option value="hidden">Hidden (Disembunyikan)</option>
                                    <option value="archived">Archived (Diarsipkan)</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 8. Status Verifikasi --}}
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Status Verifikasi <span class="text-red-500">*</span></label>
                                <select name="verification_status" x-model="formData.verification_status"
                                        class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error('verification_status') border-red-500 bg-red-50/20 @else border-line @enderror">
                                    <option value="verified">Verified (Terverifikasi & Langsung Tayang)</option>
                                    <option value="pending">Pending (Menunggu Verifikasi)</option>
                                    <option value="rejected">Rejected (Ditolak)</option>
                                </select>
                                @error('verification_status')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 9. Foto Barang (Multiple: max 5) --}}
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Foto Barang <span class="text-muted">(Maksimal 5 foto)</span></label>
                                <div>
                                    <input type="file" name="photos[]" accept="image/*" multiple @change="previewPhotos($event)"
                                           class="block w-full text-xs text-muted file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-soft file:text-brand hover:file:bg-green-100 cursor-pointer border border-line rounded-lg p-1.5 bg-white">
                                    <span class="text-[11px] text-faint mt-1 block">JPG, PNG, WEBP (Maksimal 5MB per file, hingga 5 file)</span>
                                    
                                    {{-- Preview thumbnails --}}
                                    <div x-show="photoPreviews.length > 0" class="mt-3 flex gap-2 flex-wrap">
                                        <template x-for="(preview, idx) in photoPreviews" :key="idx">
                                            <div class="relative h-16 w-16 rounded-lg border-2 border-line bg-gray-50 overflow-hidden shrink-0 shadow-sm">
                                                <img :src="preview" class="h-full w-full object-cover" alt="">
                                                <div class="absolute bottom-0 right-0 bg-black/60 px-1 py-0.5 text-[10px] font-bold text-white">
                                                    <span x-text="idx + 1"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    {{-- Existing images (when editing) --}}
                                    <div x-show="isEdit && formData.images && formData.images.length > 0" class="mt-3">
                                        <p class="text-xs font-medium text-muted mb-2">Foto saat ini:</p>
                                        <div class="flex gap-2 flex-wrap">
                                            <template x-for="img in formData.images" :key="img.id">
                                                <img :src="img.url" class="h-16 w-16 rounded-lg border border-line object-cover" alt="">
                                            </template>
                                        </div>
                                        <p class="text-[11px] text-faint mt-1">Upload foto baru akan menggantikan semua foto lama.</p>
                                    </div>
                                </div>
                            </div>

                            {{-- 10. Deskripsi --}}
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Deskripsi Lengkap</label>
                                <textarea name="description" x-model="formData.description" rows="3" placeholder="Jelaskan detail barang..." class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand"></textarea>
                            </div>

                            {{-- 11. Spesifikasi --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Spesifikasi (Opsional)</label>
                                <textarea name="specification" x-model="formData.specification" rows="2" placeholder="Contoh: RAM 8GB, SSD 256GB..." class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand"></textarea>
                            </div>

                            {{-- 12. Kelengkapan --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Kelengkapan (Opsional)</label>
                                <input type="text" name="completeness" x-model="formData.completeness" placeholder="Contoh: Dus, Charger, Unit" class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
                            </div>

                            {{-- 13. Alasan Dijual --}}
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold text-ink">Alasan Dijual (Opsional)</label>
                                <input type="text" name="sell_reason" x-model="formData.sell_reason" placeholder="Contoh: Sudah lulus / tidak terpakai lagi" class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex justify-end gap-2 border-t border-line px-6 py-3.5 bg-gray-50/80 rounded-b-2xl shrink-0">
                        <button type="button" @click="closeModal()" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-muted hover:bg-gray-200 transition-colors">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 transition-colors" x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Barang'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="showDeleteModal" @click="showDeleteModal = false" class="fixed inset-0 bg-black/50 transition-opacity"></div>
            <div x-show="showDeleteModal" x-transition class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex flex-col items-center text-center">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-7 w-7 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-ink">Hapus Barang?</h3>
                    <p class="mt-2 text-sm text-muted">
                        Anda akan menghapus barang <strong x-text="deleteLabel" class="text-ink"></strong>.
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
                <form :action="deleteAction" method="POST" class="mt-6 flex justify-center gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="showDeleteModal = false" class="rounded-lg bg-gray-100 px-5 py-2 text-sm font-medium text-muted hover:bg-gray-200">Batal</button>
                    <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
    @endif

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
                                    <dt class="text-[11px] uppercase tracking-wide text-faint">Stok</dt>
                                    <dd class="font-medium text-ink" x-text="detail.stock ?? '-'"></dd>
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
                    @if(!empty($isMaintenance))
                    <button type="button" @click="closeDetail(); openEdit(detail.id)"
                            class="rounded-lg bg-brand px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-green-700">
                        Ubah Barang
                    </button>
                    @endif
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
        photoPreviews: [],
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
            this.photoPreviews = [];
            document.querySelectorAll('input[type="file"]').forEach(input => input.value = '');
            this.showModal = true;
        },

        async openEdit(id) {
            this.isEdit = true;
            this.formAction = @json(url('admin/stock')) + '/' + id;
            this.formData = {};
            this.photoPreviews = [];
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
            this.photoPreviews = [];
        },

        previewPhotos(event) {
            const files = event.target.files;
            this.photoPreviews = [];
            if (files && files.length > 0) {
                const maxFiles = Math.min(files.length, 5);
                for (let i = 0; i < maxFiles; i++) {
                    this.photoPreviews.push(URL.createObjectURL(files[i]));
                }
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
                sold: 'Terjual',
                hidden: 'Disembunyikan',
                archived: 'Diarsipkan',
            }[value] || value || '-';
        },

        statusClass(value) {
            return {
                available: 'bg-green-100 text-green-700',
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