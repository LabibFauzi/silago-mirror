<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request): View
    {
        $status = $request->input('status') ?: 'all';
        $search = $request->input('q');

        $users = User::where('role', 'user')
            ->withCount(['products', 'marketplaceDeals as deals_count'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $searchLower = strtolower($search);
                $q->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('email', 'like', "%{$searchLower}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'status'));
    }

    public function show(User $user): View
    {
        $user->loadCount([
            'products as active_products' => fn($q) => $q->where('status', 'available'),
            'marketplaceDeals as active_deals' => fn($q) => $q->where('status', 'active')
        ]);
        
        return view('admin.users.show', compact('user'));
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'user') {
            return back()->with('error', 'Hanya pengguna biasa yang bisa disuspend.');
        }

        $request->validate([
            'suspend_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($request, $user) {
                $user->forceFill([
                    'status' => 'suspended',
                    'suspend_reason' => $request->input('suspend_reason'),
                    'suspended_at' => now(),
                ])->saveQuietly();

                $user->tokens()->delete();

                $this->auditLogService->logUser('Suspend pengguna', $user->name, $user->id, ['reason' => $request->input('suspend_reason')]);
            });

            $this->notificationService->akunDisuspend($user->id);

            return back()->with('success', 'Akun berhasil ditangguhkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menangguhkan pengguna: ' . $e->getMessage());
        }
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        try {
            DB::transaction(function () use ($user) {
                $user->forceFill([
                    'status' => 'active',
                    'suspend_reason' => null,
                    'suspended_at' => null,
                ])->saveQuietly();

                $this->auditLogService->logUser('Aktifkan pengguna', $user->name, $user->id);
            });

            return back()->with('success', 'Akun berhasil diaktifkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengaktifkan pengguna: ' . $e->getMessage());
        }
    }
}
