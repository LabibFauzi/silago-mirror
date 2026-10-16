@extends('admin.layout.app')
@section('title', 'Data Manager — ' . $config['label'])

@section('content')
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
                        <td class="px-4 py-3 max-w-[200px] truncate whitespace-nowrap"
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
        <div class="flex min-h-screen items-center justify-center px-4">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 bg-black/50 transition-opacity"></div>

            <div x-show="showModal" x-transition
                 class="relative z-10 w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl max-h-[90vh] overflow-y-auto">

                <div class="mb-5 flex items-center justify-between border-b border-line pb-4">
                    <h3 class="text-lg font-bold text-ink" x-text="isEdit ? 'Ubah Data' : 'Tambah Data Baru'"></h3>
                    <button @click="showModal = false" class="rounded-lg p-1 text-faint hover:bg-gray-100 hover:text-ink">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="formAction" method="POST" enctype="multipart/form-data">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach($config['editable'] as $field)
                        @php
                            $isPassword = $field === 'password';
                            $isImage    = in_array($field, ['image_path', 'profile_photo', 'avatar']) || ($field === 'icon' && $table === 'categories');
                            $isTextarea = in_array($field, ['description', 'specification', 'sell_reason', 'body', 'admin_note', 'cancel_note', 'message', 'suspend_reason', 'value', 'meta', 'note', 'banned_words']);
                            $isBoolean  = in_array($field, ['is_negotiable', 'is_active', 'is_read', 'email_domain_verified', 'two_factor_enabled']);
                            $isSelect   = false;
                            $options    = [];

                            if ($field === 'role') { $isSelect = true; $options = ['user','moderator','admin']; }
                            if ($field === 'status' && $table === 'users') { $isSelect = true; $options = ['active','monitored','suspended']; }
                            if ($field === 'status' && $table === 'products') { $isSelect = true; $options = ['available','sold','hidden','archived']; }
                            if ($field === 'status' && $table === 'marketplace_deals') { $isSelect = true; $options = ['agreed','completed','cancelled']; }
                            if ($field === 'status' && $table === 'reports') { $isSelect = true; $options = ['pending','reviewed','resolved','dismissed']; }
                            if ($field === 'verification_status') { $isSelect = true; $options = ['pending','verified','rejected']; }
                            if ($field === 'type' && $table === 'messages') { $isSelect = true; $options = ['text','offer','location','system']; }
                            if ($field === 'offer_status') { $isSelect = true; $options = ['pending','accepted','rejected','countered']; }
                            if ($field === 'condition') { $isSelect = true; $options = ['Baru (BNIB)','Baru','Bekas Layak','Bekas']; }
                            if ($field === 'audience') { $isSelect = true; $options = ['all','user','admin']; }
                            if ($field === 'resolution') { $isSelect = true; $options = ['support_buyer','support_seller','no_action','item_removed','user_warned','user_suspended']; }
                            if ($field === 'scan_result') { $isSelect = true; $options = ['clean','flagged']; }

                            $spanFull = $isTextarea || $isImage;
                        @endphp
                        <div class="{{ $spanFull ? 'sm:col-span-2' : '' }}">
                            <label class="mb-1 block text-sm font-medium text-ink">
                                {{ $field }}
                                @if($isPassword && !isset($config['rules_create'][$field]))
                                    <span class="text-faint font-normal">(opsional saat ubah)</span>
                                @elseif($isImage)
                                    <span class="text-brand font-normal text-xs">(Unggah Foto/Gambar)</span>
                                @endif
                            </label>

                            @if($isImage)
                                <div class="space-y-2">
                                    <div class="flex items-center gap-3">
                                        <!-- Preview Box -->
                                        <div class="h-16 w-16 rounded-xl border-2 border-line bg-gray-50 flex items-center justify-center overflow-hidden shrink-0 shadow-sm relative group">
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
                                                   class="block w-full text-xs text-muted file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-soft file:text-brand hover:file:bg-green-100 cursor-pointer border border-line rounded-lg p-1.5 bg-white">
                                            <div class="flex items-center justify-between text-[11px] text-muted mt-1">
                                                <span>JPG, PNG, WEBP, SVG (Maks. 5MB)</span>
                                                <span x-show="isEdit" class="text-faint">Kosongkan jika foto tetap</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif($isBoolean)
                                <select name="{{ $field }}" x-model="formData.{{ $field }}"
                                        class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
                                    <option value="1">Ya</option>
                                    <option value="0">Tidak</option>
                                </select>
                            @elseif($isSelect)
                                <select name="{{ $field }}" x-model="formData.{{ $field }}"
                                        class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
                                    <option value="">— Pilih —</option>
                                    @foreach($options as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                            @elseif($isTextarea)
                                <textarea name="{{ $field }}" x-model="formData.{{ $field }}" rows="3"
                                          class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand"
                                          placeholder="{{ $field }}"></textarea>
                            @elseif($isPassword)
                                <input type="password" name="{{ $field }}" x-model="formData.{{ $field }}"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand"
                                       placeholder="{{ $isPassword && 'isEdit' ? 'Kosongkan jika tidak diubah' : $field }}"
                                       autocomplete="new-password">
                            @else
                                <input type="text" name="{{ $field }}" x-model="formData.{{ $field }}"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand"
                                       placeholder="{{ $field }}">
                            @endif
                        </div>
                        @endforeach
                    </div>

                    @if($errors->any())
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3">
                        <ul class="list-disc pl-4 text-sm text-red-700">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mt-6 flex justify-end gap-2 border-t border-line pt-4">
                        <button type="button" @click="showModal = false"
                                class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-muted hover:bg-gray-200">Batal</button>
                        <button type="submit"
                                class="rounded-lg bg-brand px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700"
                                x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Data'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════ Modal Konfirmasi Hapus ═══════════ --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4">
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
    return {
        showModal: false,
        showDeleteModal: false,
        isEdit: false,
        formAction: '',
        formData: {},
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
                    // Bersihkan password agar tidak muncul
                    if (data.password) data.password = '';
                    this.formData = data;
                }
            } catch (e) {
                console.error('Gagal memuat data:', e);
            }

            this.showModal = true;
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
    }
}
</script>
@endpush
@endsection
