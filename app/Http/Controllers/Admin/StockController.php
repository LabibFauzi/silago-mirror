<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ProductImageService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SettingService $settingService,
        private readonly ProductImageService $imageService
    ) {}

    public function index(Request $request): View
    {
        $filter = $request->input('status') ?: 'all';
        $search = $request->input('q');

        // Menampilkan semua barang agar barang yang baru dibuat admin langsung muncul
        $query = Product::query();
        
        $allCount = (clone $query)->count();
        $availableCount = (clone $query)->where('stock', '>', 0)->count();
        $outCount = (clone $query)->where('stock', 0)->count();

        $products = $query->with(['seller', 'category', 'images' => fn ($q) => $q->orderBy('sort_order')])
            ->when($filter && $filter !== 'all', function ($q) use ($filter) {
                if ($filter === 'available') {
                    $q->where('stock', '>', 0);
                } elseif ($filter === 'out') {
                    $q->where('stock', 0);
                } elseif ($filter === 'pending') {
                    $q->where('verification_status', 'pending');
                }
            })
            ->when($search, function ($q) use ($search) {
                $searchLower = strtolower($search);
                $q->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereHas('seller', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]))
                      ->orWhereHas('category', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]));
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $isMaintenance = $this->settingService->getBool('maintenance_mode', false);
        $sellers = User::orderBy('name')->get(['id', 'name', 'email', 'nim']);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.stock.index', compact(
            'products', 'allCount', 'availableCount', 'outCount', 'filter',
            'isMaintenance', 'sellers', 'categories'
        ));
    }

    /**
     * Ambil data JSON barang untuk modal ubah.
     */
    public function showJson(Product $product): JsonResponse
    {
        $product->load([
            'seller:id,name,email,nim',
            'category:id,name,slug',
            'images' => fn ($q) => $q->orderBy('sort_order', 'asc'),
        ]);

        $data = $product->toArray();

        // URL publik semua foto (relatif /storage/...), dipakai modal detail.
        $data['images'] = $product->images->map(function (ProductImage $image) {
            return [
                'id' => $image->id,
                'url' => $image->url,
                'sort_order' => $image->sort_order,
            ];
        })->values()->all();

        $data['photo_url'] = $data['images'][0]['url'] ?? null;
        $data['photos_count'] = count($data['images']);

        return response()->json($data);
    }

    /**
     * Tambah barang baru langsung saat mode maintenance aktif.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $this->settingService->getBool('maintenance_mode', false)) {
            return back()->with('error', 'Penambahan barang langsung hanya dapat dilakukan saat Mode Maintenance aktif.');
        }

        $rules = [
            'seller_id'           => 'required|exists:users,id',
            'category_id'         => 'required|exists:categories,id',
            'name'                => 'required|string|max:150',
            'price'               => 'required|numeric|min:0',
            'stock'               => 'required|integer|min:0',
            'condition'           => 'nullable|string|max:50',
            'status'              => 'required|in:available,sold,hidden,archived',
            'verification_status' => 'required|in:verified,pending,rejected',
            'description'         => 'nullable|string',
            'specification'       => 'nullable|string',
            'completeness'        => 'nullable|string|max:100',
            'sell_reason'         => 'nullable|string',
            'photos'              => 'nullable|array|max:5',
            'photos.*'            => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ];

        $validator = validator(
            $request->all(),
            $rules,
            $this->validationMessages(),
            $this->customAttributes()
        );

        $validated = $validator->validate();

        try {
            DB::transaction(function () use ($request, $validated) {
                $productData = collect($validated)->except(['photos'])->all();
                
                foreach ($productData as $k => $v) {
                    if ($v === '') $productData[$k] = null;
                }



                $product = new Product();
                $product->forceFill($productData)->save();

                if ($request->hasFile('photos')) {
                    foreach ($request->file('photos') as $index => $file) {
                        $this->imageService->create($product, $file, $index);
                    }
                }

                $this->auditLogService->log(
                    'stock',
                    'Tambah Barang (Maintenance)',
                    "Barang #{$product->id} ({$product->name})",
                    'Penambahan barang langsung saat maintenance mode'
                );
            });

            return redirect()
                ->route('admin.stock.index')
                ->with('success', 'Barang berhasil ditambahkan dan langsung tersedia di stok.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan barang: ' . $e->getMessage());
        }
    }

    /**
     * Ubah data barang saat mode maintenance aktif.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        if (! $this->settingService->getBool('maintenance_mode', false)) {
            return back()->with('error', 'Perubahan barang langsung hanya dapat dilakukan saat Mode Maintenance aktif.');
        }

        $rules = [
            'seller_id'           => 'required|exists:users,id',
            'category_id'         => 'required|exists:categories,id',
            'name'                => 'required|string|max:150',
            'price'               => 'required|numeric|min:0',
            'stock'               => 'required|integer|min:0',
            'condition'           => 'nullable|string|max:50',
            'status'              => 'required|in:available,sold,hidden,archived',
            'verification_status' => 'required|in:verified,pending,rejected',
            'description'         => 'nullable|string',
            'specification'       => 'nullable|string',
            'completeness'        => 'nullable|string|max:100',
            'sell_reason'         => 'nullable|string',
            'photos'              => 'nullable|array|max:5',
            'photos.*'            => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ];

        $validator = validator(
            $request->all(),
            $rules,
            $this->validationMessages(),
            $this->customAttributes()
        );

        $validated = $validator->validate();

        try {
            DB::transaction(function () use ($request, $product, $validated) {
                $productData = collect($validated)->except(['photos'])->all();
                
                foreach ($productData as $k => $v) {
                    if ($v === '') $productData[$k] = null;
                }

                $product->forceFill($productData)->save();

                if ($request->hasFile('photos')) {
                    foreach ($product->images()->get() as $oldImg) {
                        $this->imageService->delete($oldImg);
                    }

                    foreach ($request->file('photos') as $index => $file) {
                        $this->imageService->create($product, $file, $index);
                    }
                }

                $this->auditLogService->log(
                    'stock',
                    'Ubah Barang (Maintenance)',
                    "Barang #{$product->id} ({$product->name})",
                    'Perubahan barang langsung saat maintenance mode'
                );
            });

            return redirect()
                ->route('admin.stock.index')
                ->with('success', 'Barang berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui barang: ' . $e->getMessage());
        }
    }

    /**
     * Hapus barang saat mode maintenance aktif.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if (! $this->settingService->getBool('maintenance_mode', false)) {
            return back()->with('error', 'Penghapusan barang langsung hanya dapat dilakukan saat Mode Maintenance aktif.');
        }

        try {
            DB::transaction(function () use ($product) {
                $name = $product->name;
                $id = $product->id;

                foreach ($product->images()->get() as $img) {
                    $this->imageService->delete($img);
                }

                $product->delete();

                $this->auditLogService->log(
                    'stock',
                    'Hapus Barang (Maintenance)',
                    "Barang #{$id} ({$name})",
                    'Penghapusan barang langsung saat maintenance mode'
                );
            });

            return redirect()
                ->route('admin.stock.index')
                ->with('success', 'Barang berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus barang: ' . $e->getMessage());
        }
    }

    private function validationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'exists'   => ':attribute yang dipilih tidak valid atau tidak ditemukan.',
            'min'      => ':attribute minimal :min.',
            'numeric'  => ':attribute harus berupa angka.',
            'integer'  => ':attribute harus berupa bilangan bulat.',
            'image'    => ':attribute harus berupa file gambar.',
            'mimes'    => ':attribute harus berformat :values.',
        ];
    }

    private function customAttributes(): array
    {
        return [
            'seller_id'           => 'Penjual',
            'category_id'         => 'Kategori Barang',
            'name'                => 'Nama Barang',
            'price'               => 'Harga Barang',
            'stock'               => 'Stok Barang',
            'condition'           => 'Kondisi Barang',
            'status'              => 'Status Barang',
            'verification_status' => 'Status Verifikasi',
            'description'         => 'Deskripsi',
            'specification'       => 'Spesifikasi',
            'completeness'        => 'Kelengkapan',
            'sell_reason'         => 'Alasan Dijual',
            'photos'              => 'Foto Barang',
        ];
    }
}
