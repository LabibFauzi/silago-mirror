<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\ProductLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ProductLifecycleService $productLifecycle,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): View
    {
        $filter = $request->input('status') ?: 'all';
        $search = $request->input('q');

        // Menampilkan semua barang agar barang yang baru dibuat admin langsung muncul
        $query = Product::query();
        
        $allCount = (clone $query)->count();
        $availableCount = (clone $query)->where('status', 'available')->count();
        $outCount = (clone $query)->where('status', 'sold')->count();

        $products = $query->with(['seller', 'category', 'images' => fn ($q) => $q->orderBy('sort_order')])
            ->when($filter && $filter !== 'all', function ($q) use ($filter) {
                if ($filter === 'available') {
                    $q->where('status', 'available');
                } elseif ($filter === 'reserved') {
                    $q->where('status', 'reserved');
                } elseif ($filter === 'out') {
                    $q->where('status', 'sold');
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
            ->paginate(10)
            ->withQueryString();

        $sellers = User::orderBy('name')->get(['id', 'name', 'email', 'nim']);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.stock.index', compact(
            'products', 'allCount', 'availableCount', 'outCount', 'filter',
            'sellers', 'categories'
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
     * Tambah barang baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'seller_id'           => 'required|exists:users,id',
            'category_id'         => 'required|exists:categories,id',
            'name'                => 'required|string|max:150',
            'price'               => 'required|numeric|min:0',
            'condition'           => 'nullable|string|max:50',
            'status'              => 'required|in:available,reserved,sold,hidden,archived',
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
                    'Penambahan barang oleh admin'
                );
            });

            return redirect()
                ->route('admin.stock.index')
                ->with('success', 'Barang berhasil ditambahkan dan langsung tersedia di katalog.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan barang: ' . $e->getMessage());
        }
    }

    /**
     * Ubah data barang.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $rules = [
            'seller_id'           => 'required|exists:users,id',
            'category_id'         => 'required|exists:categories,id',
            'name'                => 'required|string|max:150',
            'price'               => 'required|numeric|min:0',
            'condition'           => 'nullable|string|max:50',
            'status'              => 'required|in:available,reserved,sold,hidden,archived',
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
                    'Perubahan barang oleh admin'
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
     * Hapus barang.
     */
    public function destroy(Product $product): RedirectResponse
    {
        // Admin/moderator boleh menghapus barang siapa saja untuk moderasi.
        $name = $product->name;
        $id = $product->id;
        $actorId = (int) (auth()->id() ?? 0);

        try {
            $recipients = $this->productLifecycle->deleteProduct($product, $actorId);

            foreach ($recipients as $recipient) {
                $this->notificationService->productUnavailable(
                    $recipient['user_id'],
                    $recipient['conversation_id'],
                    $name,
                    $id,
                );
            }

            $this->auditLogService->log(
                'stock',
                'Hapus Barang',
                "Barang #{$id} ({$name})",
                'Produk dihapus (soft delete) oleh admin. Riwayat transaksi tetap disimpan.'
            );

            return redirect()
                ->route('admin.stock.index')
                ->with('success', 'Barang berhasil dihapus.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
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
