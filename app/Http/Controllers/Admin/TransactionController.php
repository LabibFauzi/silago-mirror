<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceDeal;
use App\Models\Report;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TransactionController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly NotificationService $notificationService,
        private readonly SettingService $settingService
    ) {}

    public function index(Request $request): View
    {
        $status = $request->input('status') ?: 'all';
        $search = $request->input('q');

        $deals = MarketplaceDeal::with(['buyer', 'seller', 'product', 'reports'])
            ->when($status && $status !== 'all', function ($q) use ($status) {
                if ($status === 'berjalan') {
                    $q->where('status', 'active');
                } elseif ($status === 'selesai') {
                    $q->where('status', 'completed');
                } elseif ($status === 'canceled') {
                    $q->where('status', 'canceled');
                } elseif ($status === 'dispute') {
                    $q->where('status', 'dispute');
                }
            })
            ->when($search, function ($q) use ($search) {
                $searchLower = strtolower($search);
                $q->where(function ($q) use ($searchLower) {
                    $q->where('id', 'like', "%{$searchLower}%")
                      ->orWhereHas('buyer', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]))
                      ->orWhereHas('seller', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]))
                      ->orWhereHas('product', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $isMaintenance = $this->settingService->getBool('maintenance_mode', false);

        return view('admin.transactions.index', compact('deals', 'status', 'isMaintenance'));
    }

    public function show(Request $request, MarketplaceDeal $deal): View
    {
        $deal->load(['buyer', 'seller', 'product', 'reports', 'cancelledBy']);
        
        return view('admin.transactions.show', compact('deal'));
    }

    public function supportBuyer(Request $request, MarketplaceDeal $deal): RedirectResponse
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $report = Report::where('marketplace_deal_id', $deal->id)
            ->whereIn('status', ['pending', 'reviewed'])
            ->first();

        if (!$report) {
            return back()->with('error', 'Tidak ada laporan sengketa aktif untuk transaksi ini.');
        }

        try {
            DB::transaction(function () use ($request, $deal, $report) {
                $report->forceFill([
                    'status' => 'resolved',
                    'resolution' => 'support_buyer',
                    'admin_note' => $request->input('admin_note'),
                ])->saveQuietly();

                $dealIdFormatted = '#TRX-' . str_pad($deal->id, 4, '0', STR_PAD_LEFT);
                $this->auditLogService->logTransaction('Dukung pembeli', $dealIdFormatted, $deal->id, ['note' => $request->input('admin_note')]);
            });

            if (method_exists($this->notificationService, 'sengketaSelesai')) {
                $this->notificationService->sengketaSelesai($deal->buyer_id, $deal->id, 'Pihak pembeli didukung');
                $this->notificationService->sengketaSelesai($deal->seller_id, $deal->id, 'Pihak pembeli didukung');
            }

            return back()->with('success', 'Keputusan tersimpan. Kedua pihak akan diberitahu.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses keputusan: ' . $e->getMessage());
        }
    }

    public function supportSeller(Request $request, MarketplaceDeal $deal): RedirectResponse
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $report = Report::where('marketplace_deal_id', $deal->id)
            ->whereIn('status', ['pending', 'reviewed'])
            ->first();

        if (!$report) {
            return back()->with('error', 'Tidak ada laporan sengketa aktif untuk transaksi ini.');
        }

        try {
            DB::transaction(function () use ($request, $deal, $report) {
                $report->forceFill([
                    'status' => 'resolved',
                    'resolution' => 'support_seller',
                    'admin_note' => $request->input('admin_note'),
                ])->saveQuietly();

                $dealIdFormatted = '#TRX-' . str_pad($deal->id, 4, '0', STR_PAD_LEFT);
                $this->auditLogService->logTransaction('Dukung penjual', $dealIdFormatted, $deal->id, ['note' => $request->input('admin_note')]);
            });
            
            if (method_exists($this->notificationService, 'sengketaSelesai')) {
                $this->notificationService->sengketaSelesai($deal->buyer_id, $deal->id, 'Pihak penjual didukung');
                $this->notificationService->sengketaSelesai($deal->seller_id, $deal->id, 'Pihak penjual didukung');
            }

            return back()->with('success', 'Keputusan tersimpan. Kedua pihak akan diberitahu.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses keputusan: ' . $e->getMessage());
        }
    }

    /**
     * Hapus transaksi saat mode maintenance aktif.
     */
    public function destroy(MarketplaceDeal $deal): RedirectResponse
    {
        if (! $this->settingService->getBool('maintenance_mode', false)) {
            return back()->with('error', 'Penghapusan transaksi hanya dapat dilakukan saat Mode Maintenance aktif.');
        }

        try {
            DB::transaction(function () use ($deal) {
                $dealId = $deal->id;
                $deal->delete();
                $this->auditLogService->log(
                    'marketplace_deal',
                    'Hapus Transaksi (Maintenance)',
                    "Transaksi #{$dealId}",
                    'Penghapusan transaksi saat mode maintenance'
                );
            });

            return redirect()
                ->route('admin.transactions.index')
                ->with('success', 'Transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }
}
