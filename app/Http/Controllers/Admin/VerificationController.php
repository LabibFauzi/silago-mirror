<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class VerificationController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request): View
    {
        $status = $request->input('status') ?: 'all';
        $search = $request->input('q');

        $products = Product::with(['seller', 'category', 'images'])
            ->when($status && $status !== 'all', fn($q) => $q->where('verification_status', $status))
            ->when($search, function ($q) use ($search) {
                $searchTerm = "%{$search}%";
                $q->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm)
                      ->orWhereHas('seller', fn($q) => $q->where('name', 'like', $searchTerm));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $pendingCount = Product::where('verification_status', 'pending')->count();
        $totalApprovedCount = Product::where('verification_status', 'verified')->count();
        $totalRejectedCount = Product::where('verification_status', 'rejected')->count();

        return view('admin.verification.index', compact('products', 'status', 'pendingCount', 'totalApprovedCount', 'totalRejectedCount'));
    }

    public function approve(Request $request, Product $product): RedirectResponse
    {
        try {
            DB::transaction(function () use ($product) {
                // Jangan gunakan $fillable, gunakan instance save atau update jika diperlukan. Di sini kita menggunakan $product->update tapi pastikan atribut tidak dibatasi $fillable jika kita force.
                $product->forceFill([
                    'verification_status' => 'verified',
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ])->saveQuietly();

                $this->auditLogService->logProduct('Setujui barang', $product->name, $product->id);
            });

            $this->notificationService->postDisetujui($product->seller_id, $product->id);

            return back()->with('success', 'Barang berhasil disetujui dan penjual diberitahu.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyetujui barang: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'rejection_note' => ['required', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($request, $product) {
                $product->forceFill([
                    'verification_status' => 'rejected',
                    'rejection_note' => $request->input('rejection_note'),
                ])->saveQuietly();

                $this->auditLogService->logProduct('Tolak barang', $product->name, $product->id, ['note' => $request->input('rejection_note')]);
            });

            $this->notificationService->postDitolak($product->seller_id, $product->id, $request->input('rejection_note'));

            return back()->with('success', 'Barang ditolak dan penjual diberitahu.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menolak barang: ' . $e->getMessage());
        }
    }
}
