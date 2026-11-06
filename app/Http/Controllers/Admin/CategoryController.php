<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CodPoint;
use App\Models\Product;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function index(): View
    {
        $categories = Category::withCount('products')->latest('created_at')->get();
        $codPoints = CodPoint::latest('created_at')->get();
        return view('admin.categories.index', compact('categories', 'codPoints'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories'],
        ]);

        try {
            DB::transaction(function () use ($request) {
                $category = new Category();
                $category->name = $request->input('name');
                $category->slug = Str::slug($request->input('name'));
                $category->save();

                $this->auditLogService->logSystem('Tambah kategori', $category->name);
            });

            return back()->with('success', 'Kategori berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambahkan kategori: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
        ]);

        try {
            DB::transaction(function () use ($request, $category) {
                $category->name = $request->input('name');
                $category->slug = Str::slug($request->input('name'));
                $category->save();

                $this->auditLogService->logSystem('Ubah kategori', $category->name);
            });

            return back()->with('success', 'Kategori berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui kategori: ' . $e->getMessage());
        }
    }

    public function products(Category $category): JsonResponse
    {
        $products = $category->products()
            ->select(['id', 'name', 'price', 'stock', 'verification_status', 'status'])
            ->latest('created_at')
            ->get();

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'products' => $products,
        ]);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $productCount = $category->products()->count();

        if ($productCount > 0) {
            return back()->with('error', "Kategori tidak bisa dihapus karena masih memiliki {$productCount} barang.");
        }

        try {
            DB::transaction(function () use ($category) {
                $name = $category->name;
                $category->delete();
                $this->auditLogService->logSystem('Hapus kategori', $name);
            });

            return back()->with('success', 'Kategori berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus kategori: ' . $e->getMessage());
        }
    }
}
