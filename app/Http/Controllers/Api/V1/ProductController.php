<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ContentScanner;
use App\Services\NotificationService;
use App\Services\ProductLifecycleService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class ProductController extends Controller
{
    /**
     * Tampilkan daftar produk (hanya yang aktif dan terverifikasi).
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['seller', 'category', 'images' => function ($q) {
                $q->orderBy('sort_order', 'asc');
            }, 'favorites'])
            ->where('status', 'available')
            ->where('verification_status', 'verified')
            ->whereHas('seller', function ($q) {
                $q->where('status', '!=', 'suspended');
            })
            ->where(function ($q) {
                $q->where('expires_at', '>', now())
                  ->orWhereNull('expires_at');
            });

        // Filter kategori
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Pencarian
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            });
        }

        // Pengurutan
        $sort = $request->get('sort', 'newest');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            default => $query->latest(), // newest
        };

        $perPage = (int) $request->get('per_page', 15);
        $perPage = max(1, min($perPage, 100));
        $products = $query->paginate($perPage);

        return ProductListResource::collection($products);
    }

    /**
     * Tampilkan detail produk.
     */
    public function show(Request $request, Product $product)
    {
        $user = $request->user();
        $isOwner = $user && $user->id === $product->seller_id;
        
        $isVisible = ($product->status === 'available' && $product->verification_status === 'verified' && ($product->expires_at === null || $product->expires_at > now())) || $isOwner;

        if (!$isVisible) {
            abort(404, 'Produk tidak ditemukan atau tidak tersedia.');
        }

        $product->load(['seller', 'category', 'images' => function ($q) {
            $q->orderBy('sort_order', 'asc');
        }, 'favorites']);

        return new ProductResource($product);
    }

    /**
     * Simpan produk baru.
     */
    public function store(StoreProductRequest $request, ContentScanner $scanner, SettingService $settingService)
    {
        $activeDays = $settingService->getInt('listing_active_days', 30);
        
        [$scanResult, $scanNote] = $scanner->scan($request->name, $request->description);

        $product = DB::transaction(function () use ($request, $activeDays, $scanResult, $scanNote) {
            $prod = new Product($request->validated());
            $prod->seller_id = $request->user()->id;
            $prod->status = 'available';
            $prod->verification_status = 'pending';
            $prod->expires_at = now()->addDays($activeDays);
            
            // Simpan hasil scan dengan langsung mengisi properti karena mungkin tidak ada di fillable
            $prod->scan_result = $scanResult;
            $prod->scan_note = $scanNote;
            
            $prod->save();

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $index => $file) {
                    $extension = $file->getClientOriginalExtension() ?: 'webp';
                    $filename = Str::uuid() . '.' . $extension;
                    $path = $file->storeAs('products', $filename, 'public');

                    $prod->images()->create([
                        'image_path' => $path,
                        'sort_order' => $index,
                    ]);
                }
            }

            return $prod;
        });

        $product->load(['seller', 'category', 'images']);

        return (new ProductResource($product))
            ->additional(['message' => 'Barang berhasil dikirim dan menunggu verifikasi'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update produk. Hanya pemilik yang boleh mengedit.
     *
     * Setiap perubahan akan mengembalikan status verifikasi ke `pending`
     * sehingga admin meninjau ulang. Harga tidak boleh diubah bila masih ada
     * transaksi aktif, tetapi deskripsi/foto tetap boleh diperbarui.
     */
    public function update(
        UpdateProductRequest $request,
        Product $product,
        ContentScanner $scanner,
        ProductLifecycleService $lifecycle,
    ) {
        if ((int) $request->user()->id !== (int) $product->seller_id) {
            abort(403, 'Anda tidak berhak mengubah produk ini.');
        }

        // Barang hanya boleh diedit saat menunggu verifikasi atau tersedia.
        $canEdit = $product->verification_status === 'pending'
            || $product->status === 'available';

        if (! $canEdit) {
            abort(
                409,
                'Barang hanya bisa diedit saat menunggu verifikasi atau tersedia.',
            );
        }

        $priceChanged = $request->has('price')
            && abs((float) $request->input('price') - (float) $product->price) > 0.005;

        if ($priceChanged && $lifecycle->hasActiveDeal($product->id)) {
            abort(
                409,
                'Harga tidak dapat diubah karena masih ada transaksi aktif. '
                .'Selesaikan atau batalkan transaksinya dulu.',
            );
        }

        // Setiap edit tetap dipindai ulang kontennya.
        [$scanResult, $scanNote] = $scanner->scan(
            $request->input('name', $product->name),
            $request->input('description', $product->description),
        );

        $updatedProduct = DB::transaction(function () use ($request, $product, $scanResult, $scanNote) {
            // Lock baris untuk update agar aman
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->first();

            $lockedProduct->fill($request->validated());

            // Verifikasi ulang ke admin setiap kali barang diedit.
            $lockedProduct->verification_status = 'pending';
            $lockedProduct->verified_by = null;
            $lockedProduct->verified_at = null;
            $lockedProduct->rejection_note = null;
            $lockedProduct->scan_result = $scanResult;
            $lockedProduct->scan_note = $scanNote;

            $lockedProduct->save();

            if ($request->hasFile('photos')) {
                // Hapus foto lama
                $oldImages = $lockedProduct->images()->get();
                foreach ($oldImages as $img) {
                    Storage::disk('public')->delete($img->image_path);
                    $img->delete();
                }

                // Simpan foto baru
                foreach ($request->file('photos') as $index => $file) {
                    $extension = $file->getClientOriginalExtension() ?: 'webp';
                    $filename = Str::uuid() . '.' . $extension;
                    $path = $file->storeAs('products', $filename, 'public');

                    $lockedProduct->images()->create([
                        'image_path' => $path,
                        'sort_order' => $index,
                    ]);
                }
            }

            return $lockedProduct;
        });

        $updatedProduct->load(['seller', 'category', 'images', 'favorites']);

        return (new ProductResource($updatedProduct))
            ->additional([
                'message' => 'Perubahan barang menunggu verifikasi ulang admin.',
            ]);
    }

    /**
     * Hapus produk (soft delete).
     *
     * Pemilik atau admin/moderator boleh menghapus. Barang dengan transaksi
     * aktif tidak boleh dihapus. Foto tetap disimpan untuk riwayat.
     */
    public function destroy(
        Request $request,
        Product $product,
        ProductLifecycleService $lifecycle,
        NotificationService $notificationService,
    ) {
        $user = $request->user();
        $isOwner = (int) $user->id === (int) $product->seller_id;
        $isAdmin = in_array($user->role, ['admin', 'moderator'], true);

        if (! $isOwner && ! $isAdmin) {
            abort(403, 'Anda tidak berhak menghapus produk ini.');
        }

        // Pemilik hanya boleh menghapus barang tersedia/terjual, atau menarik
        // pengajuan yang masih pending (batal penambahan barang).
        if ($isOwner && ! $isAdmin) {
            $isPending = $product->verification_status === 'pending';
            $canDelete = $isPending
                || in_array($product->status, ['available', 'sold'], true);

            if (! $canDelete) {
                abort(
                    409,
                    'Barang ini tidak dapat dihapus.',
                );
            }
        }

        $productName = $product->name;
        $productId = $product->id;

        try {
            $recipients = $lifecycle->deleteProduct($product, (int) $user->id);
        } catch (\RuntimeException $e) {
            abort($e->getCode() ?: 409, $e->getMessage());
        }

        foreach ($recipients as $recipient) {
            $notificationService->productUnavailable(
                $recipient['user_id'],
                $recipient['conversation_id'],
                $productName,
                $productId,
            );
        }

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }

    /**
     * Tandai barang terjual / nonaktifkan / aktifkan kembali.
     */
    public function updateStatus(
        Request $request,
        Product $product,
        ProductLifecycleService $lifecycle,
        SettingService $settingService,
    ) {
        if ((int) $request->user()->id !== (int) $product->seller_id) {
            abort(403, 'Anda tidak berhak mengubah status produk ini.');
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:available,sold,inactive'],
        ]);

        $wasSold = $product->status === 'sold';

        $product = $lifecycle->updateStatus($product, $data['status']);

        // Jual lagi barang yang sudah terjual: segarkan masa tayang agar
        // kembali muncul di etalase.
        if ($wasSold && $data['status'] === 'available') {
            $activeDays = $settingService->getInt('listing_active_days', 30);
            $product->expires_at = now()->addDays($activeDays);
            $product->save();
        }

        $product->load(['seller', 'category', 'images', 'favorites']);

        return (new ProductResource($product))->additional([
            'message' => 'Status barang berhasil diperbarui.',
        ]);
    }

    /**
     * Tampilkan produk milik user (semua status).
     */
    public function myProducts(Request $request)
    {
        $query = Product::query()
            ->with(['category', 'images' => function ($q) {
                $q->orderBy('sort_order', 'asc');
            }])
            ->where('seller_id', $request->user()->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        $perPage = (int) $request->get('per_page', 15);
        $perPage = max(1, min($perPage, 100));
        $products = $query->latest()->paginate($perPage);

        return ProductListResource::collection($products);
    }

    /**
     * Perbarui masa tayang produk.
     */
    public function renew(Request $request, Product $product, SettingService $settingService)
    {
        if ($request->user()->id !== $product->seller_id) {
            abort(403, 'Anda tidak berhak memperbarui produk ini.');
        }
        
        if ($product->status !== 'archived') {
            abort(400, 'Hanya produk yang diarsipkan yang dapat diperbarui masa tayangnya.');
        }

        $activeDays = $settingService->getInt('listing_active_days', 30);

        $product->status = 'available';
        $product->verification_status = 'pending';
        $product->expires_at = now()->addDays($activeDays);
        $product->save();

        return response()->json([
            'message' => 'Masa tayang produk berhasil diperbarui dan menunggu verifikasi.',
            'data' => new ProductResource($product->fresh(['category', 'images'])),
        ]);
    }
}
