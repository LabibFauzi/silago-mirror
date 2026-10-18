@extends('admin.layout.app')
@section('title', 'Pengguna')

@section('content')
@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

<div x-data="userManager()" class="space-y-6">

    {{-- Banner Notifikasi Mode Maintenance --}}
    @if(!empty($isMaintenance))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 text-lg">🛠️</span>
            <div>
                <p class="text-sm font-bold text-red-800">Mode Maintenance Aktif — CRUD Pengguna Terbuka</p>
                <p class="text-xs text-red-700">Anda dapat menambah, mengubah, dan menghapus data pengguna secara langsung di halaman ini.</p>
            </div>
        </div>
        <button type="button" @click="openCreate()" class="flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Pengguna
        </button>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
        <div class="border-b border-line px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium {{ (!request('status') || request('status') === 'all') ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Semua</a>
                <a href="?status=active" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'active' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Aktif</a>
                <a href="?status=suspended" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'suspended' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Disuspend</a>
            </div>
            <form method="GET" action="" class="w-full sm:w-72">
                <input type="hidden" name="status" value="{{ request('status', '') }}">
                <input type="text" name="q" placeholder="Cari nama / email / NIM..." class="w-full px-4 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" value="{{ request('q', '') }}">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-muted uppercase bg-page border-b border-line">
                    <tr>
                        <th class="px-6 py-4">Pengguna</th>
                        <th class="px-6 py-4">Email / NIM</th>
                        <th class="px-6 py-4">Peran</th>
                        <th class="px-6 py-4">Bergabung</th>
                        <th class="px-6 py-4">Barang</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users ?? [] as $user)
                    <tr class="border-b border-line hover:bg-page">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}" alt="Avatar" class="w-9 h-9 rounded-full object-cover mr-3 border border-line">
                                <div>
                                    <div class="font-medium text-ink">{{ $user->name }}</div>
                                    @if($user->username)
                                        <div class="text-xs text-muted font-mono">{{ '@' . $user->username }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-ink font-medium">{{ $user->email }}</div>
                            <div class="text-xs text-muted">{{ $user->nim ?: '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->role === 'admin')
                                <span class="bg-purple-100 text-purple-700 py-1 px-2.5 rounded-md text-xs font-semibold">Admin</span>
                            @elseif($user->role === 'moderator')
                                <span class="bg-blue-100 text-blue-700 py-1 px-2.5 rounded-md text-xs font-semibold">Moderator</span>
                            @else
                                <span class="bg-gray-100 text-gray-700 py-1 px-2.5 rounded-md text-xs">Pengguna</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-muted">{{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}</td>
                        <td class="px-6 py-4 font-medium text-ink">{{ $user->products_count ?? 0 }}</td>
                        <td class="px-6 py-4">
                            @if($user->status === 'active')
                                <span class="bg-green-100 text-green-700 py-1 px-2 rounded-md text-xs font-medium">Aktif</span>
                            @elseif($user->status === 'suspended')
                                <span class="bg-red-100 text-red-700 py-1 px-2 rounded-md text-xs font-medium">Disuspend</span>
                            @elseif($user->status === 'monitored')
                                <span class="bg-yellow-100 text-yellow-800 py-1 px-2 rounded-md text-xs font-medium">Dipantau</span>
                            @else
                                <span class="bg-gray-100 text-gray-600 py-1 px-2 rounded-md text-xs">{{ $user->status ?? 'Aktif' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <a href="{{ route('admin.users.show', $user->id) }}" class="text-brand hover:underline font-medium text-xs">Lihat</a>
                            
                            @if(!empty($isMaintenance))
                                <button type="button" @click="openEdit({{ $user->id }})" class="text-blue-600 hover:underline font-medium text-xs">Ubah</button>
                                <button type="button" @click="confirmDelete({{ $user->id }}, '{{ addslashes($user->name) }}')" class="text-red-600 hover:underline font-medium text-xs">Hapus</button>
                            @else
                                @if($user->status !== 'suspended')
                                    <button @click="selectedUser = {{ json_encode($user) }}; showSuspendModal = true" class="text-red-600 hover:underline font-medium text-xs">Suspend</button>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-muted">Tidak ada pengguna</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($users) && method_exists($users, 'links'))
        <div class="px-6 py-4 border-t border-line">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    {{-- Modal Tambah / Ubah Pengguna (Maintenance Mode) --}}
    @if(!empty($isMaintenance))
    <div x-show="showUserModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showUserModal" @click="closeUserModal()" class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50"></div>
            
            <div x-show="showUserModal" class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10">
                <div class="flex justify-between items-center mb-5 pb-3 border-b border-line">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-soft text-brand text-sm font-bold">👤</span>
                        <h3 class="text-lg font-bold text-ink" x-text="isEdit ? 'Ubah Data Pengguna' : 'Tambah Pengguna Baru'"></h3>
                    </div>
                    <button type="button" @click="closeUserModal()" class="text-muted hover:text-ink">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Alert Error Validasi --}}
                @if($errors->any() && old('_form_action'))
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-700">
                        <p class="font-bold mb-1">Terjadi kesalahan validasi:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" :action="formAction" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="_form_action" :value="formAction">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Nama Lengkap --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-ink mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="formData.name" required class="w-full px-3 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" placeholder="Contoh: Budi Santoso">
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" x-model="formData.email" required class="w-full px-3 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" placeholder="budi@example.com">
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- NIM --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">NIM (Nomor Induk Mahasiswa)</label>
                            <input type="text" name="nim" x-model="formData.nim" class="w-full px-3 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" placeholder="Contoh: 2110511001">
                            @error('nim')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Username --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Username</label>
                            <input type="text" name="username" x-model="formData.username" class="w-full px-3 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" placeholder="budisantoso">
                            @error('username')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- No. HP / WhatsApp --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">No. HP / WhatsApp</label>
                            <input type="text" name="phone" x-model="formData.phone" class="w-full px-3 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" placeholder="081234567890">
                            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Password --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-ink mb-1">
                                Password 
                                <span x-show="!isEdit" class="text-red-500">*</span>
                                <span x-show="isEdit" class="text-xs text-muted font-normal">(Kosongkan jika tidak ingin mengubah password)</span>
                            </label>
                            <input type="password" name="password" x-model="formData.password" :required="!isEdit" minlength="8" class="w-full px-3 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" placeholder="Minimal 8 karakter">
                            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Peran / Role --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Peran (Role) <span class="text-red-500">*</span></label>
                            <select name="role" x-model="formData.role" required class="w-full px-3 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand bg-white">
                                <option value="user">User (Mahasiswa/Umum)</option>
                                <option value="moderator">Moderator</option>
                                <option value="admin">Administrator</option>
                            </select>
                            @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Status Akun --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink mb-1">Status Akun <span class="text-red-500">*</span></label>
                            <select name="status" x-model="formData.status" required class="w-full px-3 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand bg-white">
                                <option value="active">Aktif</option>
                                <option value="monitored">Dipantau (Monitored)</option>
                                <option value="suspended">Disuspend (Ditangguhkan)</option>
                            </select>
                            @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Alasan Suspend (Hanya jika status == suspended) --}}
                        <div class="sm:col-span-2" x-show="formData.status === 'suspended'">
                            <label class="block text-xs font-semibold text-ink mb-1">Alasan Penangguhan (Suspend Reason)</label>
                            <input type="text" name="suspend_reason" x-model="formData.suspend_reason" class="w-full px-3 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" placeholder="Contoh: Terlibat penipuan barang...">
                            @error('suspend_reason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Foto Profil --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-ink mb-1">Foto Profil</label>
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-full border border-line bg-page overflow-hidden flex items-center justify-center shrink-0">
                                    <template x-if="photoPreview || formData.photo_url">
                                        <img :src="photoPreview || formData.photo_url" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!photoPreview && !formData.photo_url">
                                        <svg class="w-6 h-6 text-muted" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </template>
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="profile_photo" accept="image/*" @change="previewPhoto($event)" class="text-xs text-muted file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-soft file:text-brand hover:file:bg-green-100">
                                    <p class="text-[11px] text-muted mt-1">Format: JPG, PNG, WEBP. Maks 5 MB.</p>
                                </div>
                            </div>
                            @error('profile_photo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-line">
                        <button type="button" @click="closeUserModal()" class="px-4 py-2 border border-line rounded-lg text-sm font-medium text-muted hover:bg-gray-50">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-brand hover:bg-green-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors" x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Pengguna'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus Pengguna --}}
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showDeleteModal" @click="showDeleteModal = false" class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50"></div>
            
            <div x-show="showDeleteModal" class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10">
                <div class="flex items-center gap-3 mb-4 text-red-600">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <h3 class="text-lg font-bold text-ink">Hapus Pengguna</h3>
                </div>

                <p class="text-sm text-muted mb-6">
                    Apakah Anda yakin ingin menghapus pengguna <strong class="text-ink" x-text="deleteLabel"></strong>? Semua data terkait (barang, transaksi, review) akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.
                </p>

                <form method="POST" :action="deleteAction" class="flex justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="showDeleteModal = false" class="rounded-lg bg-gray-100 px-5 py-2 text-sm font-medium text-muted hover:bg-gray-200">Batal</button>
                    <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Suspend (Mode Normal) --}}
    <div x-show="showSuspendModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showSuspendModal" @click="showSuspendModal = false" class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50"></div>
            
            <div x-show="showSuspendModal" class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-bold text-ink">Suspend Pengguna</h3>
                    <button type="button" @click="showSuspendModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <p class="text-sm text-muted mb-4">Anda akan mensuspend pengguna <strong x-text="selectedUser?.name"></strong>. Masukkan alasan suspend.</p>

                <form method="POST" :action="`/admin/users/${selectedUser?.id}/suspend`">
                    @csrf
                    <div class="mb-4">
                        <textarea name="suspend_reason" rows="3" class="w-full p-2 border border-line rounded-lg text-sm" placeholder="Alasan suspend..." required></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg text-sm">Suspend Sekarang</button>
                        <button type="button" @click="showSuspendModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function userManager() {
    const hasErrors = {{ ($errors->any() || session('error')) && old('_form_action') ? 'true' : 'false' }};
    const wasEdit = {{ old('_method') === 'PUT' ? 'true' : 'false' }};
    const oldFormAction = @json(old('_form_action', ''));
    const oldInputs = @json(old());

    delete oldInputs._token;
    delete oldInputs._method;
    delete oldInputs._form_action;

    return {
        showUserModal: hasErrors,
        showSuspendModal: false,
        showDeleteModal: false,
        selectedUser: null,
        isEdit: wasEdit,
        formAction: oldFormAction || (wasEdit ? '' : @json(route('admin.users.store'))),
        formData: hasErrors ? oldInputs : { role: 'user', status: 'active' },
        photoPreview: null,
        deleteAction: '',
        deleteLabel: '',

        openCreate() {
            this.isEdit = false;
            this.formAction = @json(route('admin.users.store'));
            this.formData = { role: 'user', status: 'active' };
            this.photoPreview = null;
            document.querySelectorAll('input[type="file"]').forEach(input => input.value = '');
            this.showUserModal = true;
        },

        async openEdit(id) {
            this.isEdit = true;
            this.formAction = @json(url('admin/users')) + '/' + id;
            this.formData = {};
            this.photoPreview = null;
            document.querySelectorAll('input[type="file"]').forEach(input => input.value = '');

            try {
                const res = await fetch(@json(url('admin/users')) + '/' + id + '/json');
                if (res.ok) {
                    const data = await res.json();
                    this.formData = data;
                }
            } catch (e) {
                console.error('Gagal memuat data pengguna:', e);
            }

            this.showUserModal = true;
        },

        closeUserModal() {
            this.showUserModal = false;
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

        confirmDelete(id, label) {
            this.deleteAction = @json(url('admin/users')) + '/' + id;
            this.deleteLabel = label;
            this.showDeleteModal = true;
        }
    };
}
</script>
@endpush
@endsection
