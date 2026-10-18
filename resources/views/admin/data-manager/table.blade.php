@extends('admin.layout.app')
@section('title', 'Data Manager — ' . $config['label'])

@section('content')
@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp
<div x-data="dataManager()" class="space-y-4">

    {{-- Breadcrumb + Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.data-manager.index') }}" class="text-brand hover:underline font-medium">Data Manager</a>
            <svg class="h-4 w-4 text-faint" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="font-semibold text-ink">{{ $config['icon'] }} {{ $config['label'] }}</span>
            <span class="rounded-full bg-page px-2 py-0.5 text-xs text-muted">{{ $records->total() }} data</span>
        </div>
        <div class="flex items-center gap-2">
            {{-- Navigasi cepat tabel --}}
            <select onchange="if(this.value) window.location.href=this.value"
                    class="rounded-lg border border-line bg-white px-3 py-2 text-sm text-muted focus:border-brand focus:ring-1 focus:ring-brand">
                <option value="">Pindah tabel…</option>
                @foreach($tables as $t)
                    <option value="{{ route('admin.data-manager.table', $t['slug']) }}" {{ $t['slug'] === $table ? 'selected' : '' }}>
                        {{ $t['icon'] }} {{ $t['label'] }}
                    </option>
                @endforeach
            </select>

            <button @click="openCreate()"
                    class="flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Tambah Data
            </button>
        </div>
    </div>

    {{-- Pencarian --}}
    @if(count($config['searchable']) > 0)
    <form method="GET" action="{{ route('admin.data-manager.table', $table) }}" class="flex gap-2">
        <div class="relative flex-1 max-w-md">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
            <input type="text" name="q" value="{{ $search ?? '' }}"
                   placeholder="Cari di {{ implode(', ', $config['searchable']) }}…"
                   class="w-full rounded-lg border border-line bg-white py-2 pl-10 pr-4 text-sm text-ink placeholder-faint focus:border-brand focus:ring-1 focus:ring-brand">
        </div>
        <button type="submit" class="rounded-lg bg-page px-4 py-2 text-sm font-medium text-muted hover:bg-gray-200 border border-line">Cari</button>
        @if($search)
            <a href="{{ route('admin.data-manager.table', $table) }}" class="rounded-lg bg-page px-4 py-2 text-sm font-medium text-muted hover:bg-gray-200 border border-line">Reset</a>
        @endif
    </form>
    @endif

    {{-- Tabel data --}}
    <div class="overflow-hidden rounded-xl border border-line bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="border-b border-line bg-page text-xs uppercase text-muted">
                    <tr>
                        @foreach($config['columns'] as $col)
                        <th class="px-4 py-3 whitespace-nowrap">
                            <a href="{{ route('admin.data-manager.table', array_merge(['table' => $table], request()->except('sort','dir'), ['sort' => $col, 'dir' => ($sortCol === $col && $sortDir === 'asc') ? 'desc' : 'asc'])) }}"
                               class="inline-flex items-center gap-1 hover:text-ink">
                                {{ $col }}
                                @if($sortCol === $col)
                                    <svg class="h-3 w-3 {{ $sortDir === 'asc' ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                @endif
                            </a>
                        </th>
                        @endforeach
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                    <tr class="border-b border-line hover:bg-page/50 transition-colors">
                        @foreach($config['columns'] as $col)
                        <td class="px-4 py-3 max-w-[220px] truncate whitespace-nowrap"
                            title="{{ is_string($record->{$col}) ? $record->{$col} : '' }}">
                            @if($col === 'status' || $col === 'verification_status' || $col === 'offer_status' || $col === 'role')
                                @php
                                    $val = $record->{$col};
                                    $cls = match($val) {
                                        'active','verified','completed','admin','resolved' => 'bg-green-100 text-green-700',
                                        'pending','monitored','reviewed','agreed','moderator' => 'bg-yellow-100 text-yellow-700',
                                        'suspended','rejected','cancelled','dismissed' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $cls }}">{{ $val ?? '-' }}</span>
                            @elseif($col === 'is_active' || $col === 'is_read' || $col === 'is_negotiable')
                                @if($record->{$col})
                                    <span class="inline-block rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Ya</span>
                                @else
                                    <span class="inline-block rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Tidak</span>
                                @endif
                            @elseif(in_array($col, ['image_path', 'profile_photo', 'avatar']) || ($col === 'icon' && $record->{$col} && (str_contains($record->{$col}, '/') || str_contains($record->{$col}, '.'))))
                                @if($record->{$col})
                                    <div class="flex items-center gap-2">
                                        <img src="{{ str_starts_with($record->{$col}, 'http') ? $record->{$col} : \Illuminate\Support\Facades\Storage::url($record->{$col}) }}" 
                                             alt="" 
                                             class="h-9 w-9 rounded-lg object-cover border border-line bg-gray-50 shrink-0"
                                             onerror="this.style.display='none'">
                                        <span class="text-xs text-muted truncate max-w-[120px]">{{ $record->{$col} }}</span>
                                    </div>
                                @else
                                    <span class="text-faint text-xs">-</span>
                                @endif
                            @elseif($col === 'price' || $col === 'agreed_price' || $col === 'offer_price')
                                <span class="font-medium">Rp{{ number_format((float) $record->{$col}, 0, ',', '.') }}</span>
                            @elseif($col === 'seller_id' && $record->seller)
                                <div class="leading-tight">
                                    <span class="font-medium text-ink block">{{ $record->seller->name }}</span>
                                    <span class="text-[11px] text-faint">#{{ $record->seller_id }}</span>
                                </div>
                            @elseif($col === 'buyer_id' && $record->buyer)
                                <div class="leading-tight">
                                    <span class="font-medium text-ink block">{{ $record->buyer->name }}</span>
                                    <span class="text-[11px] text-faint">#{{ $record->buyer_id }}</span>
                                </div>
                            @elseif($col === 'user_id' && $record->user)
                                <div class="leading-tight">
                                    <span class="font-medium text-ink block">{{ $record->user->name }}</span>
                                    <span class="text-[11px] text-faint">#{{ $record->user_id }}</span>
                                </div>
                            @elseif($col === 'category_id' && $record->category)
                                <span class="font-medium text-ink">{{ $record->category->name }}</span>
                            @elseif($col === 'product_id' && $record->product)
                                <div class="leading-tight">
                                    <span class="font-medium text-ink block truncate max-w-[150px]">{{ $record->product->name }}</span>
                                    <span class="text-[11px] text-faint">#{{ $record->product_id }}</span>
                                </div>
                            @elseif($col === 'reporter_id' && $record->reporter)
                                <div class="leading-tight">
                                    <span class="font-medium text-ink block">{{ $record->reporter->name }}</span>
                                    <span class="text-[11px] text-faint">#{{ $record->reporter_id }}</span>
                                </div>
                            @elseif($col === 'reported_user_id' && $record->reportedUser)
                                <div class="leading-tight">
                                    <span class="font-medium text-ink block">{{ $record->reportedUser->name }}</span>
                                    <span class="text-[11px] text-faint">#{{ $record->reported_user_id }}</span>
                                </div>
                            @elseif($col === 'created_by' && $record->creator)
                                <div class="leading-tight">
                                    <span class="font-medium text-ink block">{{ $record->creator->name }}</span>
                                    <span class="text-[11px] text-faint">#{{ $record->created_by }}</span>
                                </div>
                            @elseif(str_ends_with($col, '_at') && $record->{$col})
                                <span class="text-muted text-xs">{{ \Carbon\Carbon::parse($record->{$col})->format('d/m/Y H:i') }}</span>
                            @elseif($col === 'password')
                                <span class="text-faint text-xs">***</span>
                            @else
                                {{ $record->{$col} ?? '-' }}
                            @endif
                        </td>
                        @endforeach
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button @click="openEdit('{{ $record->{$pk} }}')"
                                    class="text-blue-600 hover:underline text-xs font-medium mr-2">Ubah</button>
                            <button @click="confirmDelete('{{ $record->{$pk} }}', '{{ addslashes($record->name ?? $record->title ?? $record->key ?? '#'.$record->{$pk}) }}')"
                                    class="text-red-600 hover:underline text-xs font-medium">Hapus</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($config['columns']) + 1 }}" class="px-6 py-12 text-center text-muted">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-10 w-10 text-faint" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <span>Tidak ada data di tabel ini</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
        <div class="border-t border-line px-4 py-3">
            {{ $records->links() }}
        </div>
        @endif
    </div>

    {{-- ═══════════ Modal Tambah / Ubah ═══════════ --}}
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
                            <h3 class="text-lg font-bold text-ink" x-text="isEdit ? 'Ubah Data' : 'Tambah Data Baru'"></h3>
                            <p class="text-xs text-muted">Tabel: <strong class="text-ink">{{ $config['label'] }}</strong> ({{ $table }})</p>
                        </div>
                        <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-faint hover:bg-gray-100 hover:text-ink transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Modal Body (Scrollable) --}}
                    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">

                        {{-- Alert Error Validasi --}}
                        @if($errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                            <div class="flex items-center gap-2 font-semibold text-red-800 text-sm mb-1.5">
                                <svg class="h-5 w-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Gagal menyimpan data! Mohon perbaiki {{ $errors->count() }} kesalahan input:</span>
                            </div>
                            <ul class="list-disc pl-7 text-xs text-red-700 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        {{-- Alert Error Sistem --}}
                        @if(session('error') && old('_form_action'))
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-700 font-medium flex items-start gap-2">
                            <svg class="h-4 w-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                        @endif

                        @php
                            $fieldLabels = [
                                'name'                => 'Nama',
                                'email'               => 'Alamat Email',
                                'nim'                 => 'NIM (Nomor Induk Mahasiswa)',
                                'username'            => 'Username',
                                'password'            => 'Kata Sandi',
                                'phone'               => 'Nomor Telepon / WhatsApp',
                                'role'                => 'Peran Pengguna (Role)',
                                'status'              => 'Status',
                                'profile_photo'       => 'Foto Profil',
                                'suspend_reason'      => 'Alasan Suspend',
                                'slug'                => 'Slug URL',
                                'icon'                => 'Ikon Kategori',
                                'seller_id'           => 'Penjual (User)',
                                'buyer_id'            => 'Pembeli (User)',
                                'user_id'             => 'Pengguna (User)',
                                'category_id'         => 'Kategori Barang',
                                'product_id'          => 'Barang Terkait',
                                'conversation_id'     => 'Percakapan Terkait',
                                'marketplace_deal_id' => 'Transaksi Deal',
                                'accepted_message_id' => 'Pesan Deal Terkait',
                                'parent_offer_id'     => 'Penawaran Induk',
                                'sender_id'           => 'Pengirim Pesan',
                                'reviewer_id'         => 'Pemberi Ulasan (Reviewer)',
                                'reviewee_id'         => 'Penerima Ulasan (Reviewee)',
                                'reporter_id'         => 'Pelapor (Reporter)',
                                'reported_user_id'    => 'Pengguna Terlapor',
                                'reviewed_by'         => 'Ditinjau Oleh',
                                'verified_by'         => 'Diverifikasi Oleh (Admin)',
                                'cancelled_by'        => 'Dibatalkan Oleh',
                                'admin_id'            => 'Admin Terkait',
                                'created_by'          => 'Dibuat Oleh',
                                'description'         => 'Deskripsi Lengkap',
                                'condition'           => 'Kondisi Barang',
                                'specification'       => 'Spesifikasi Barang',
                                'completeness'        => 'Kelengkapan',
                                'sell_reason'         => 'Alasan Dijual',
                                'price'               => 'Harga Barang',
                                'stock'               => 'Stok Barang',
                                'is_negotiable'       => 'Bisa Nego?',
                                'verification_status' => 'Status Verifikasi',
                                'rejection_note'      => 'Catatan Penolakan Verifikasi',
                                'scan_result'         => 'Hasil Scan AI / Keamanan',
                                'scan_note'           => 'Catatan Hasil Scan',
                                'expires_at'          => 'Waktu Kedaluwarsa',
                                'image_path'          => 'File Foto',
                                'sort_order'          => 'Urutan Tampilan',
                                'address'             => 'Alamat Titik COD',
                                'lat'                 => 'Latitude Koordinat',
                                'lng'                 => 'Longitude Koordinat',
                                'is_active'           => 'Status Aktif',
                                'type'                => 'Tipe Pesan',
                                'message'             => 'Isi Pesan',
                                'offer_price'         => 'Harga Penawaran',
                                'offer_status'        => 'Status Penawaran',
                                'location_lat'        => 'Latitude Lokasi',
                                'location_lng'        => 'Longitude Lokasi',
                                'location_name'       => 'Nama Tempat / Lokasi',
                                'location_address'    => 'Alamat Lokasi',
                                'read_at'             => 'Waktu Dibaca',
                                'quantity'            => 'Jumlah Kuantitas',
                                'agreed_price'        => 'Harga Disepakati',
                                'payment_method'      => 'Metode Pembayaran',
                                'agreed_at'           => 'Waktu Disepakati',
                                'completed_at'        => 'Waktu Selesai',
                                'cancelled_at'        => 'Waktu Dibatalkan',
                                'cancel_reason'       => 'Alasan Pembatalan',
                                'cancel_note'         => 'Catatan Pembatalan',
                                'rating'              => 'Rating (Bintang 1-5)',
                                'comment'             => 'Komentar / Ulasan',
                                'reason'              => 'Alasan Laporan',
                                'resolution'          => 'Resolusi Masalah',
                                'admin_note'          => 'Catatan Internal Admin',
                                'reviewed_at'         => 'Waktu Ditinjau',
                                'title'               => 'Judul',
                                'body'                => 'Isi Konten / Pesan',
                                'reference_id'        => 'ID Referensi Terkait',
                                'is_read'             => 'Status Sudah Dibaca?',
                                'category'            => 'Kategori Log',
                                'action'              => 'Aksi Log',
                                'target'              => 'Target Log',
                                'note'                => 'Catatan',
                                'meta'                => 'Metadata JSON',
                                'key'                 => 'Kunci Pengaturan (Key)',
                                'value'               => 'Nilai Pengaturan (Value)',
                                'audience'            => 'Target Penerima (Audience)',
                                'scheduled_at'        => 'Jadwal Kirim',
                                'sent_at'             => 'Waktu Terkirim',
                            ];
                        @endphp

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach($config['editable'] as $field)
                            @php
                                $isRelation = isset($relationOptions[$field]);
                                $isImage    = in_array($field, ['image_path', 'profile_photo', 'avatar']) || ($field === 'icon' && $table === 'categories');
                                $isTextarea = in_array($field, ['description', 'specification', 'sell_reason', 'body', 'admin_note', 'cancel_note', 'message', 'suspend_reason', 'value', 'meta', 'note', 'banned_words', 'rejection_note', 'scan_note']);
                                $isBoolean  = in_array($field, ['is_negotiable', 'is_active', 'is_read', 'email_domain_verified', 'two_factor_enabled']);
                                $isDatetime = str_ends_with($field, '_at') || in_array($field, ['expires_at', 'scheduled_at', 'sent_at']);
                                $isPassword = $field === 'password';
                                $isEmail    = $field === 'email';
                                $isPrice    = in_array($field, ['price', 'agreed_price', 'offer_price']);
                                $isInteger  = in_array($field, ['stock', 'quantity', 'rating', 'sort_order', 'location_accuracy']);
                                $isCoord    = in_array($field, ['lat', 'lng', 'location_lat', 'location_lng', 'completed_lat', 'completed_lng']);

                                $isSelect = false;
                                $options  = [];

                                if ($field === 'role') {
                                    $isSelect = true;
                                    $options = [
                                        'user'      => 'User (Mahasiswa / Pengguna Biasa)',
                                        'moderator' => 'Moderator',
                                        'admin'     => 'Administrator',
                                    ];
                                }
                                if ($field === 'status' && $table === 'users') {
                                    $isSelect = true;
                                    $options = [
                                        'active'    => 'Active (Akun Aktif)',
                                        'monitored' => 'Monitored (Akun Dipantau)',
                                        'suspended' => 'Suspended (Akun Dibekukan)',
                                    ];
                                }
                                if ($field === 'status' && $table === 'products') {
                                    $isSelect = true;
                                    $options = [
                                        'available' => 'Available (Tersedia)',
                                        'sold'      => 'Sold (Terjual)',
                                        'hidden'    => 'Hidden (Disembunyikan)',
                                        'archived'  => 'Archived (Diarsipkan)',
                                    ];
                                }
                                if ($field === 'status' && $table === 'marketplace_deals') {
                                    $isSelect = true;
                                    $options = [
                                        'agreed'    => 'Agreed (Disepakati)',
                                        'completed' => 'Completed (Selesai)',
                                        'cancelled' => 'Cancelled (Dibatalkan)',
                                    ];
                                }
                                if ($field === 'status' && $table === 'reports') {
                                    $isSelect = true;
                                    $options = [
                                        'pending'   => 'Pending (Menunggu Ditinjau)',
                                        'reviewed'  => 'Reviewed (Sedang Ditinjau)',
                                        'resolved'  => 'Resolved (Terselesaikan)',
                                        'dismissed' => 'Dismissed (Ditolak / Ditutup)',
                                    ];
                                }
                                if ($field === 'verification_status') {
                                    $isSelect = true;
                                    $options = [
                                        'pending'  => 'Pending (Menunggu Verifikasi)',
                                        'verified' => 'Verified (Terverifikasi Aman)',
                                        'rejected' => 'Rejected (Verifikasi Ditolak)',
                                    ];
                                }
                                if ($field === 'type' && $table === 'messages') {
                                    $isSelect = true;
                                    $options = [
                                        'text'     => 'Text (Pesan Teks Biasa)',
                                        'offer'    => 'Offer (Penawaran Harga)',
                                        'location' => 'Location (Titik Lokasi COD)',
                                        'system'   => 'System (Pesan Otomatis Sistem)',
                                    ];
                                }
                                if ($field === 'offer_status') {
                                    $isSelect = true;
                                    $options = [
                                        'pending'   => 'Pending (Menunggu Respon)',
                                        'accepted'  => 'Accepted (Tawaran Diterima)',
                                        'rejected'  => 'Rejected (Tawaran Ditolak)',
                                        'countered' => 'Countered (Nego Balik)',
                                    ];
                                }
                                if ($field === 'condition') {
                                    $isSelect = true;
                                    $options = [
                                        'Baru (BNIB)' => 'Baru (BNIB - Segel Pabrik)',
                                        'Baru'        => 'Baru (Buka Segel / Belum Dipakai)',
                                        'Bekas Layak' => 'Bekas Layak Pakai',
                                        'Bekas'       => 'Bekas (Minus Ringan / Pemakaian)',
                                    ];
                                }
                                if ($field === 'audience') {
                                    $isSelect = true;
                                    $options = [
                                        'all'   => 'Semua Pengguna (Umum)',
                                        'user'  => 'Hanya Pengguna Biasa',
                                        'admin' => 'Hanya Admin & Moderator',
                                    ];
                                }
                                if ($field === 'resolution') {
                                    $isSelect = true;
                                    $options = [
                                        'support_buyer'  => 'Menangkan Pembeli',
                                        'support_seller' => 'Menangkan Penjual',
                                        'no_action'      => 'Tidak Ada Tindakan',
                                        'item_removed'   => 'Hapus Barang Melanggar',
                                        'user_warned'    => 'Beri Peringatan ke Pengguna',
                                        'user_suspended' => 'Bekukan Akun Pengguna',
                                    ];
                                }
                                if ($field === 'scan_result') {
                                    $isSelect = true;
                                    $options = [
                                        'clean'   => 'Clean (Aman)',
                                        'flagged' => 'Flagged (Terindikasi Bermasalah)',
                                    ];
                                }
                                if ($field === 'payment_method') {
                                    $isSelect = true;
                                    $options = [
                                        'cod'      => 'COD (Bayar Tunai saat Ketemu)',
                                        'transfer' => 'Transfer Bank / QRIS',
                                        'cash'     => 'Tunai Langsung',
                                    ];
                                }

                                $spanFull = $isTextarea || $isImage;
                            @endphp
                            <div class="{{ $spanFull ? 'sm:col-span-2' : '' }}">
                                <label class="mb-1.5 flex items-center justify-between text-xs font-semibold text-ink">
                                    <span>{{ $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}</span>
                                    <span class="text-[10px] font-mono font-normal text-muted bg-gray-100 px-1.5 py-0.5 rounded">{{ $field }}</span>
                                </label>

                                {{-- 1. Pilihan Relasi Foreign Key --}}
                                @if($isRelation)
                                    <select name="{{ $field }}" x-model="formData.{{ $field }}"
                                            class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error($field) border-red-500 bg-red-50/20 @else border-line @enderror">
                                        <option value="">— Pilih {{ $fieldLabels[$field] ?? $field }} —</option>
                                        @foreach($relationOptions[$field] as $opt)
                                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>

                                {{-- 2. Gambar / Foto Upload --}}
                                @elseif($isImage)
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-3">
                                            <!-- Preview Box -->
                                            <div class="h-16 w-16 rounded-xl border-2 @error($field) border-red-400 @else border-line @enderror bg-gray-50 flex items-center justify-center overflow-hidden shrink-0 shadow-sm relative group">
                                                <template x-if="imagePreviews['{{ $field }}'] || formData['{{ $field }}_url'] || (formData['{{ $field }}'] && (formData['{{ $field }}'].includes('/') || formData['{{ $field }}'].includes('.')))">
                                                    <img :src="imagePreviews['{{ $field }}'] || formData['{{ $field }}_url'] || (formData['{{ $field }}'] ? (formData['{{ $field }}'].startsWith('http') ? formData['{{ $field }}'] : '/storage/' + formData['{{ $field }}']) : '')" 
                                                         class="h-full w-full object-cover" 
                                                         alt="Preview">
                                                </template>
                                                <template x-if="!imagePreviews['{{ $field }}'] && !formData['{{ $field }}_url'] && (!formData['{{ $field }}'] || (!formData['{{ $field }}'].includes('/') && !formData['{{ $field }}'].includes('.')))">
                                                    <svg class="h-6 w-6 text-faint" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                    </svg>
                                                </template>
                                            </div>

                                            <!-- Upload Controls -->
                                            <div class="flex-1 min-w-0">
                                                <input type="file" 
                                                       name="{{ $field }}" 
                                                       id="file_{{ $field }}"
                                                       accept="image/*"
                                                       @change="previewFile($event, '{{ $field }}')"
                                                       class="block w-full text-xs text-muted file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-soft file:text-brand hover:file:bg-green-100 cursor-pointer border @error($field) border-red-500 bg-red-50/20 @else border-line @enderror rounded-lg p-1.5 bg-white">
                                                <div class="flex items-center justify-between text-[11px] text-muted mt-1">
                                                    <span>JPG, PNG, WEBP, SVG (Maks. 5MB)</span>
                                                    <span x-show="isEdit" class="text-faint">Kosongkan jika foto tidak diubah</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                {{-- 3. Boolean Select --}}
                                @elseif($isBoolean)
                                    <select name="{{ $field }}" x-model="formData.{{ $field }}"
                                            class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error($field) border-red-500 bg-red-50/20 @else border-line @enderror">
                                        <option :value="1">Ya (Aktif / True)</option>
                                        <option :value="0">Tidak (Nonaktif / False)</option>
                                    </select>

                                {{-- 4. Enum Select --}}
                                @elseif($isSelect)
                                    <select name="{{ $field }}" x-model="formData.{{ $field }}"
                                            class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error($field) border-red-500 bg-red-50/20 @else border-line @enderror">
                                        <option value="">— Pilih {{ $fieldLabels[$field] ?? $field }} —</option>
                                        @foreach($options as $val => $lbl)
                                            <option value="{{ $val }}">{{ $lbl }}</option>
                                        @endforeach
                                    </select>

                                {{-- 5. Textarea --}}
                                @elseif($isTextarea)
                                    <textarea name="{{ $field }}" x-model="formData.{{ $field }}" rows="3"
                                              class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error($field) border-red-500 bg-red-50/20 @else border-line @enderror"
                                              placeholder="Masukkan {{ strtolower($fieldLabels[$field] ?? $field) }}..."></textarea>

                                {{-- 6. Tanggal dan Waktu (Datetime-local) --}}
                                @elseif($isDatetime)
                                    <input type="datetime-local" name="{{ $field }}" x-model="formData.{{ $field }}"
                                           class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error($field) border-red-500 bg-red-50/20 @else border-line @enderror">

                                {{-- 7. Harga / Mata Uang --}}
                                @elseif($isPrice)
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-faint">Rp</span>
                                        <input type="number" min="0" step="100" name="{{ $field }}" x-model="formData.{{ $field }}"
                                               placeholder="0"
                                               class="w-full rounded-lg border bg-white py-2 pl-9 pr-3 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error($field) border-red-500 bg-red-50/20 @else border-line @enderror">
                                    </div>

                                {{-- 8. Bilangan Bulat (Stock, Quantity, Rating, Sort) --}}
                                @elseif($isInteger)
                                    <input type="number" min="{{ $field === 'quantity' || $field === 'rating' ? 1 : 0 }}" {{ $field === 'rating' ? 'max=5' : '' }} step="1" name="{{ $field }}" x-model="formData.{{ $field }}"
                                           placeholder="0"
                                           class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error($field) border-red-500 bg-red-50/20 @else border-line @enderror">

                                {{-- 9. Koordinat Geografis (Lat/Lng) --}}
                                @elseif($isCoord)
                                    <input type="number" step="any" name="{{ $field }}" x-model="formData.{{ $field }}"
                                           placeholder="Contoh: -6.89148"
                                           class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error($field) border-red-500 bg-red-50/20 @else border-line @enderror">

                                {{-- 10. Kata Sandi --}}
                                @elseif($isPassword)
                                    <input type="password" name="{{ $field }}" x-model="formData.{{ $field }}"
                                           class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error($field) border-red-500 bg-red-50/20 @else border-line @enderror"
                                           placeholder="Minimal 8 karakter..."
                                           autocomplete="new-password">
                                    <span x-show="isEdit" class="text-[11px] text-faint mt-1 block">Biarkan kosong jika tidak ingin mengubah kata sandi</span>

                                {{-- 11. Email --}}
                                @elseif($isEmail)
                                    <input type="email" name="{{ $field }}" x-model="formData.{{ $field }}"
                                           class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error($field) border-red-500 bg-red-50/20 @else border-line @enderror"
                                           placeholder="user@silago.ac.id">

                                {{-- 12. Default Input Teks --}}
                                @else
                                    <input type="text" name="{{ $field }}" x-model="formData.{{ $field }}"
                                           class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand @error($field) border-red-500 bg-red-50/20 @else border-line @enderror"
                                           placeholder="Masukkan {{ strtolower($fieldLabels[$field] ?? $field) }}...">
                                @endif

                                {{-- Pesan Error Inline --}}
                                @error($field)
                                    <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        <span>{{ $message }}</span>
                                    </p>
                                @enderror
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex justify-end gap-2 border-t border-line px-6 py-3.5 bg-gray-50/80 rounded-b-2xl shrink-0">
                        <button type="button" @click="closeModal()"
                                class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-muted hover:bg-gray-200 transition-colors">Batal</button>
                        <button type="submit"
                                class="rounded-lg bg-brand px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 transition-colors"
                                x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Data'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════ Modal Konfirmasi Hapus ═══════════ --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="showDeleteModal" @click="showDeleteModal = false" class="fixed inset-0 bg-black/50 transition-opacity"></div>
            <div x-show="showDeleteModal" x-transition class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex flex-col items-center text-center">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-7 w-7 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-ink">Hapus Data?</h3>
                    <p class="mt-2 text-sm text-muted">
                        Anda akan menghapus <strong x-text="deleteLabel" class="text-ink"></strong> dari tabel <strong>{{ $config['label'] }}</strong>.
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
                <form :action="deleteAction" method="POST" class="mt-6 flex justify-center gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="showDeleteModal = false"
                            class="rounded-lg bg-gray-100 px-5 py-2 text-sm font-medium text-muted hover:bg-gray-200">Batal</button>
                    <button type="submit"
                            class="rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dataManager() {
    const hasErrors = {{ ($errors->any() || session('error')) && old('_form_action') ? 'true' : ($errors->any() ? 'true' : 'false') }};
    const wasEdit = {{ old('_method') === 'PUT' ? 'true' : 'false' }};
    const oldFormAction = @json(old('_form_action', ''));
    const oldInputs = @json(old());

    // Bersihkan token dan field teknis agar tidak bentrok dengan formData
    delete oldInputs._token;
    delete oldInputs._method;
    delete oldInputs._form_action;

    return {
        showModal: hasErrors,
        showDeleteModal: false,
        isEdit: wasEdit,
        formAction: oldFormAction || (wasEdit ? '' : @json(route('admin.data-manager.store', $table))),
        formData: hasErrors ? oldInputs : {},
        imagePreviews: {},
        deleteAction: '',
        deleteLabel: '',

        openCreate() {
            this.isEdit = false;
            this.formAction = @json(route('admin.data-manager.store', $table));
            this.formData = {};
            this.imagePreviews = {};
            document.querySelectorAll('input[type="file"]').forEach(input => input.value = '');
            this.showModal = true;
        },

        async openEdit(id) {
            this.isEdit = true;
            this.formAction = @json(url('admin/data-manager/' . $table)) + '/' + id;
            this.formData = {};
            this.imagePreviews = {};
            document.querySelectorAll('input[type="file"]').forEach(input => input.value = '');

            try {
                const res = await fetch(@json(url('admin/data-manager/' . $table)) + '/' + id + '/json');
                if (res.ok) {
                    const data = await res.json();
                    if (data.password) data.password = '';
                    this.formData = data;
                }
            } catch (e) {
                console.error('Gagal memuat data:', e);
            }

            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.formData = {};
            this.imagePreviews = {};
        },

        previewFile(event, field) {
            const file = event.target.files[0];
            if (file) {
                this.imagePreviews[field] = URL.createObjectURL(file);
            } else {
                delete this.imagePreviews[field];
            }
        },

        confirmDelete(id, label) {
            this.deleteAction = @json(url('admin/data-manager/' . $table)) + '/' + id;
            this.deleteLabel = label;
            this.showDeleteModal = true;
        }
    };
}
</script>
@endpush
@endsection
