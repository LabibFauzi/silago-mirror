<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
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

        $users = User::withCount(['products', 'marketplaceDeals as deals_count'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $searchLower = strtolower($search);
                $q->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('email', 'like', "%{$searchLower}%")
                      ->orWhere('nim', 'like', "%{$searchLower}%")
                      ->orWhere('username', 'like', "%{$searchLower}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $isMaintenance = $this->settingService->getBool('maintenance_mode', false);

        return view('admin.users.index', compact('users', 'status', 'isMaintenance'));
    }

    public function show(User $user): View
    {
        $user->loadCount([
            'products as active_products' => fn($q) => $q->where('status', 'available'),
            'marketplaceDeals as active_deals' => fn($q) => $q->whereIn('status', ['negotiating', 'agreed'])
        ]);
        
        return view('admin.users.show', compact('user'));
    }

    /**
     * Ambil data JSON pengguna untuk modal ubah.
     */
    public function showJson(User $user): JsonResponse
    {
        $data = $user->toArray();
        $data['photo_url'] = $user->profile_photo_url ?: ($user->avatar ? $user->avatar_url : null);
        $data['password'] = '';

        return response()->json($data);
    }

    /**
     * Tambah pengguna baru saat mode maintenance aktif.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $this->settingService->getBool('maintenance_mode', false)) {
            return back()->with('error', 'Penambahan pengguna langsung hanya dapat dilakukan saat Mode Maintenance aktif.');
        }

        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'email'          => 'required|email|unique:users,email',
            'nim'            => 'nullable|string|max:20|unique:users,nim',
            'username'       => 'nullable|string|max:50|unique:users,username',
            'password'       => 'required|string|min:8',
            'phone'          => 'nullable|string|max:30',
            'role'           => 'required|in:user,moderator,admin',
            'status'         => 'required|in:active,monitored,suspended',
            'profile_photo'  => 'nullable|image|max:5120',
            'suspend_reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $validated) {
                $userData = collect($validated)->except(['profile_photo'])->all();
                $userData['password'] = Hash::make($userData['password']);

                if ($request->hasFile('profile_photo')) {
                    $file = $request->file('profile_photo');
                    $ext = $file->getClientOriginalExtension() ?: 'webp';
                    $filename = Str::uuid() . '.' . $ext;
                    $path = $file->storeAs('profile-photos', $filename, 'public');
                    $userData['profile_photo'] = $path;
                }

                $user = new User();
                $user->forceFill($userData)->save();

                $this->auditLogService->log(
                    'user',
                    'Tambah Pengguna (Maintenance)',
                    "Pengguna #{$user->id} ({$user->name})",
                    'Penambahan pengguna saat mode maintenance'
                );
            });

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Pengguna berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan pengguna: ' . $e->getMessage());
        }
    }

    /**
     * Ubah data pengguna saat mode maintenance aktif.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $this->settingService->getBool('maintenance_mode', false)) {
            return back()->with('error', 'Perubahan pengguna langsung hanya dapat dilakukan saat Mode Maintenance aktif.');
        }

        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'email'          => "required|email|unique:users,email,{$user->id}",
            'nim'            => "nullable|string|max:20|unique:users,nim,{$user->id}",
            'username'       => "nullable|string|max:50|unique:users,username,{$user->id}",
            'password'       => 'nullable|string|min:8',
            'phone'          => 'nullable|string|max:30',
            'role'           => 'required|in:user,moderator,admin',
            'status'         => 'required|in:active,monitored,suspended',
            'profile_photo'  => 'nullable|image|max:5120',
            'suspend_reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $user, $validated) {
                $userData = collect($validated)->except(['profile_photo', 'password'])->all();

                if (!empty($validated['password'])) {
                    $userData['password'] = Hash::make($validated['password']);
                }

                if ($request->hasFile('profile_photo')) {
                    if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                        Storage::disk('public')->delete($user->profile_photo);
                    }
                    $file = $request->file('profile_photo');
                    $ext = $file->getClientOriginalExtension() ?: 'webp';
                    $filename = Str::uuid() . '.' . $ext;
                    $path = $file->storeAs('profile-photos', $filename, 'public');
                    $userData['profile_photo'] = $path;
                }

                $user->forceFill($userData)->save();

                $this->auditLogService->log(
                    'user',
                    'Ubah Pengguna (Maintenance)',
                    "Pengguna #{$user->id} ({$user->name})",
                    'Perubahan pengguna saat mode maintenance'
                );
            });

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Data pengguna berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui pengguna: ' . $e->getMessage());
        }
    }

    /**
     * Hapus pengguna saat mode maintenance aktif.
     */
    public function destroy(User $user): RedirectResponse
    {
        if (! $this->settingService->getBool('maintenance_mode', false)) {
            return back()->with('error', 'Penghapusan pengguna langsung hanya dapat dilakukan saat Mode Maintenance aktif.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        try {
            DB::transaction(function () use ($user) {
                $name = $user->name;
                $id = $user->id;

                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }

                $user->tokens()->delete();
                $user->delete();

                $this->auditLogService->log(
                    'user',
                    'Hapus Pengguna (Maintenance)',
                    "Pengguna #{$id} ({$name})",
                    'Penghapusan pengguna saat mode maintenance'
                );
            });

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Pengguna berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus pengguna: ' . $e->getMessage());
        }
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
