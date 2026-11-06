@extends('admin.layout.app')
@section('title', 'Kategori')

@section('content')
<div x-data="categoryPage()" x-init="init()">
    <div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
        <div class="border-b border-line px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <h3 class="text-lg font-semibold text-ink">Kategori Barang</h3>
            <button @click="selectedCategory = null; showCatModal = true" class="bg-brand text-white text-sm font-medium py-1.5 px-3 rounded-lg hover:bg-green-700">+ Tambah</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-muted uppercase bg-page border-b border-line">
                    <tr>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4">Slug</th>
                        <th class="px-6 py-4">Jml Barang</th>
                        <th class="px-6 py-4">Dibuat</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories ?? [] as $category)
                    <tr class="border-b border-line hover:bg-page">
                        <td class="px-6 py-4 font-medium text-ink">{{ $category->name }}</td>
                        <td class="px-6 py-4 text-muted whitespace-nowrap">{{ $category->slug }}</td>
                        <td class="px-6 py-4">
                            <span class="bg-soft text-brand py-1 px-2 rounded-md text-xs font-medium">{{ $category->products_count ?? 0 }}</span>
                        </td>
                        <td class="px-6 py-4 text-muted">{{ $category->created_at ? $category->created_at->format('d M Y') : '-' }}</td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button @click="openDetail({{ $category->id }}, '{{ addslashes($category->name) }}')" class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-green-700/20 transition hover:bg-green-700 hover:shadow-md active:scale-95"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Detail</button>
                            <button @click="selectedCategory = {{ json_encode($category) }}; showCatModal = true" class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-blue-700/20 transition hover:bg-blue-700 hover:shadow-md active:scale-95"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>Ubah</button>
                            @if(($category->products_count ?? 0) == 0)
                            <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}" class="inline" onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-red-700/20 transition hover:bg-red-700 hover:shadow-md active:scale-95"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>Hapus</button>
                            </form>
                            @else
                            <span class="text-gray-400 font-medium text-xs cursor-not-allowed" title="Tidak bisa dihapus, masih ada {{ $category->products_count }} barang">Hapus</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-muted">Belum ada kategori</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Detail Kategori (list barang) -->
    <div x-show="showDetailModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showDetailModal" @click="showDetailModal = false" class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50"></div>
            
            <div x-show="showDetailModal" class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10">
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-line">
                    <div>
                        <h3 class="text-lg font-bold text-ink" x-text="detailCategory?.name"></h3>
                        <p class="text-xs text-muted mt-0.5" x-text="detailProducts.length + ' barang dalam kategori ini'"></p>
                    </div>
                    <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div x-show="loadingDetail" class="py-12 text-center text-muted text-sm">Memuat...</div>

                <div x-show="!loadingDetail && detailProducts.length === 0" class="py-12 text-center text-muted text-sm">Tidak ada barang dalam kategori ini</div>

                <div x-show="!loadingDetail && detailProducts.length > 0" class="max-h-96 overflow-y-auto -mx-2">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-muted uppercase bg-page sticky top-0">
                            <tr>
                                <th class="px-4 py-3">Nama Barang</th>
                                <th class="px-4 py-3">Harga</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="product in detailProducts" :key="product.id">
                                <tr class="border-b border-line hover:bg-page">
                                    <td class="px-4 py-3 font-medium text-ink" x-text="product.name"></td>
                                    <td class="px-4 py-3 text-muted" x-text="formatPrice(product.price)"></td>
                                    <td class="px-4 py-3">
                                        <span class="py-1 px-2 rounded-md text-xs"
                                              :class="product.verification_status === 'approved' ? 'bg-green-100 text-green-700' : (product.verification_status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700')"
                                              x-text="product.verification_status"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" @click="showDetailModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Ubah Kategori -->
    <div x-show="showCatModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showCatModal" @click="showCatModal = false" class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50"></div>
            
            <div x-show="showCatModal" class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10">
                <h3 class="text-lg font-bold text-ink mb-5" x-text="selectedCategory ? 'Ubah Kategori' : 'Tambah Kategori'"></h3>
                
                <form method="POST" :action="selectedCategory ? `/admin/categories/${selectedCategory.id}` : '{{ route('admin.categories.store') ?? '#' }}'">
                    @csrf
                    <template x-if="selectedCategory">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-ink mb-1">Nama Kategori</label>
                        <input type="text" name="name" :value="selectedCategory?.name || ''" class="w-full p-2 border border-line rounded-lg" required>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-brand hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg text-sm">Simpan</button>
                        <button type="button" @click="showCatModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function categoryPage() {
    return {
        showCatModal: false,
        selectedCategory: null,
        showDetailModal: false,
        detailCategory: null,
        detailProducts: [],
        loadingDetail: false,
        init() {},
        async openDetail(id, name) {
            this.detailCategory = { id, name };
            this.detailProducts = [];
            this.loadingDetail = true;
            this.showDetailModal = true;
            try {
                const res = await fetch(`/admin/categories/${id}/products`);
                const data = await res.json();
                this.detailProducts = data.products || [];
            } catch (e) {
                this.detailProducts = [];
            } finally {
                this.loadingDetail = false;
            }
        },
        formatPrice(price) {
            if (!price) return 'Rp 0';
            return 'Rp ' + Number(price).toLocaleString('id-ID');
        }
    }
}
</script>
@endsection
