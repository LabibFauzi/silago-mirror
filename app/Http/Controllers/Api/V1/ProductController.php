<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ContentScanner;
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
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
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
     * Update produk.
     */
    public function update(UpdateProductRequest $request, Product $product, ContentScanner $scanner)
    {
        if ($request->user()->id !== $product->seller_id) {
            abort(403, 'Anda tidak berhak mengubah produk ini.');
        }

        // Cek deal (asumsi ada model Deal atau join ke DB)
        $hasAgreedDeal = DB::table('deals')
            ->where('product_id', $product->id)
            ->where('status', 'agreed')
            ->exists();
            
        if ($hasAgreedDeal) {
            abort(409, 'Tidak dapat mengubah produk karena sudah ada kesepakatan.');
        }

        $needsVerification = $request->has('name') || $request->has('description') || $request->hasFile('photos');

        // Rescan konten jika nama atau deskripsi berubah
        if ($needsVerification && ($request->has('name') || $request->has('description'))) {
            [$scanResult, $scanNote] = $scanner->scan(
                $request->input('name', $product->name),
                $request->input('description', $product->description)
            );
        } else {
            [$scanResult, $scanNote] = [$product->scan_result, $product->scan_note];
        }

        $updatedProduct = DB::transaction(function () use ($request, $product, $needsVerification, $scanResult, $scanNote) {
            // Lock baris untuk update agar aman
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->first();
            
            $lockedProduct->fill($request->validated());
            
            if ($needsVerification) {
                $lockedProduct->verification_status = 'pending';
                $lockedProduct->scan_result = $scanResult;
                $lockedProduct->scan_note = $scanNote;
            }
            
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

        return new ProductResource($updatedProduct);
    }

    /**
     * Hapus produk.
     */
    public function destroy(Request $request, Product $product)
    {
        if ($request->user()->id !== $product->seller_id) {
            abort(403, 'Anda tidak berhak menghapus produk ini.');
        }

        $hasAgreedDeal = DB::table('deals')
            ->where('product_id', $product->id)
            ->where('status', 'agreed')
            ->exists();
            
        if ($hasAgreedDeal) {
            abort(409, 'Tidak dapat menghapus produk karena sudah ada kesepakatan.');
        }

        DB::transaction(function () use ($product) {
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->first();
            
            // Hapus foto dari storage
            $images = $lockedProduct->images()->get();
            foreach ($images as $img) {
                Storage::disk('public')->delete($img->image_path);
            }
            
            // Delete gambar dari db dan sembunyikan atau hapus produk
            $lockedProduct->images()->delete();
            $lockedProduct->status = 'hidden';
            $lockedProduct->save();
            // Alternatif: $lockedProduct->delete(); tapi instruksi menyebutkan "Delete atau soft-hide"
        });

        return response()->json(['message' => 'Produk berhasil dihapus.']);
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
