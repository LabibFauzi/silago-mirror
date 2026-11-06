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
                        <td class="px-6 py-4 text-muted">{{ $category->slug }}</td>
                        <td class="px-6 py-4">
                            <span class="bg-soft text-brand py-1 px-2 rounded-md text-xs font-medium">{{ $category->products_count ?? 0 }}</span>
                        </td>
                        <td class="px-6 py-4 text-muted">{{ $category->created_at ? $category->created_at->format('d M Y') : '-' }}</td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button @click="openDetail({{ $category->id }}, '{{ addslashes($category->name) }}')" class="text-brand hover:underline font-medium text-xs">Detail</button>
                            <button @click="selectedCategory = {{ json_encode($category) }}; showCatModal = true" class="text-blue-600 hover:underline font-medium text-xs">Ubah</button>
                            @if(($category->products_count ?? 0) == 0)
                            <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}" class="inline" onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline font-medium text-xs">Hapus</button>
                            </form>
                            @else
                            <span class="text-faint font-medium text-xs cursor-not-allowed" title="Tidak bisa dihapus, masih ada {{ $category->products_count }} barang">Hapus</span>
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
                                <th class="px-4 py-3">Stok</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="product in detailProducts" :key="product.id">
                                <tr class="border-b border-line hover:bg-page">
                                    <td class="px-4 py-3 font-medium text-ink" x-text="product.name"></td>
                                    <td class="px-4 py-3 text-muted" x-text="formatPrice(product.price)"></td>
                                    <td class="px-4 py-3">
                                        <span class="bg-soft text-brand py-1 px-2 rounded-md text-xs font-medium" x-text="product.stock"></span>
                                    </td>
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
