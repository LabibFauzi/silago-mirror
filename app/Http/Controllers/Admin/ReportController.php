<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request): View
    {
        $status = $request->input('status') ?: 'all';
        $search = $request->input('q');

        $reports = Report::with(['reporter', 'reportedUser', 'product', 'marketplaceDeal'])
            ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $searchTerm = strtolower($search);
                $q->where(function ($q) use ($searchTerm) {
                    $q->where('id', 'like', "%{$searchTerm}%")
                      ->orWhereHas('reporter', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.index', compact('reports', 'status'));
    }

    public function resolve(Request $request, Report $report): RedirectResponse
    {
        if ($report->status !== 'pending') {
            return back()->with('error', 'Laporan tidak bisa diselesaikan.');
        }

        $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($request, $report) {
                $report->forceFill([
                    'status' => 'resolved',
                    'admin_note' => $request->input('admin_note'),
                ])->saveQuietly();

                $reportIdFormatted = '#L-' . str_pad($report->id, 4, '0', STR_PAD_LEFT);
                $this->auditLogService->logReport('Selesaikan laporan', $reportIdFormatted, $report->id);
            });

            $this->notificationService->laporanSelesai($report->reporter_id, $report->id);

            return back()->with('success', 'Laporan berhasil diselesaikan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyelesaikan laporan: ' . $e->getMessage());
        }
    }

    public function dismiss(Request $request, Report $report): RedirectResponse
    {
        if ($report->status !== 'pending') {
            return back()->with('error', 'Laporan tidak bisa ditolak.');
        }

        $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($request, $report) {
                $report->forceFill([
                    'status' => 'dismissed',
                    'admin_note' => $request->input('admin_note'),
                ])->saveQuietly();

                $reportIdFormatted = '#L-' . str_pad($report->id, 4, '0', STR_PAD_LEFT);
                $this->auditLogService->logReport('Tolak laporan', $reportIdFormatted, $report->id, ['note' => $request->input('admin_note')]);
            });

            return back()->with('success', 'Laporan ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menolak laporan: ' . $e->getMessage());
        }
    }
}
