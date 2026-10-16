<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\CodPoint;
use App\Models\Conversation;
use App\Models\Favorite;
use App\Models\MarketplaceDeal;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Report;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

/**
 * Data Manager — CRUD langsung ke semua tabel.
 *
 * HANYA tersedia saat:
 * 1. Mode Maintenance aktif (middleware: maintenance.active)
 * 2. User adalah admin (middleware: admin.only)
 *
 * Semua aksi dicatat di audit log.
 */
class DataManagerController extends Controller
{
    /**
     * Registry tabel yang bisa dikelola.
     * Key = slug URL, value = konfigurasi tabel.
     */
    private const TABLE_REGISTRY = [
        'users' => [
            'model'      => User::class,
            'label'      => 'Pengguna',
            'icon'       => '👥',
            'columns'    => ['id', 'name', 'email', 'nim', 'username', 'role', 'status', 'created_at'],
            'searchable' => ['name', 'email', 'nim', 'username'],
            'editable'   => ['name', 'username', 'nim', 'email', 'phone', 'role', 'status', 'password', 'profile_photo', 'suspend_reason'],
            'image_fields' => [
                'profile_photo' => 'profile-photos',
            ],
            'rules_create' => [
                'name'     => 'required|string|max:100',
                'email'    => 'required|email|unique:users',
                'nim'      => 'nullable|string|max:20|unique:users',
                'username' => 'nullable|string|max:50|unique:users',
                'password' => 'required|string|min:8',
                'phone'    => 'nullable|string|max:30',
                'role'     => 'required|in:user,moderator,admin',
                'status'   => 'required|in:active,monitored,suspended',
                'profile_photo' => 'nullable',
            ],
            'rules_update' => [
                'name'     => 'required|string|max:100',
                'email'    => 'required|email',
                'nim'      => 'nullable|string|max:20',
                'username' => 'nullable|string|max:50',
                'password' => 'nullable|string|min:8',
                'phone'    => 'nullable|string|max:30',
                'role'     => 'required|in:user,moderator,admin',
                'status'   => 'required|in:active,monitored,suspended',
                'profile_photo' => 'nullable',
            ],
        ],
        'categories' => [
            'model'      => Category::class,
            'label'      => 'Kategori',
            'icon'       => '🏷️',
            'columns'    => ['id', 'name', 'slug', 'icon', 'created_at'],
            'searchable' => ['name', 'slug'],
            'editable'   => ['name', 'slug', 'icon'],
            'image_fields' => [
                'icon' => 'categories',
            ],
            'rules_create' => [
                'name' => 'required|string|max:100',
                'slug' => 'required|string|max:100|unique:categories',
                'icon' => 'nullable',
            ],
            'rules_update' => [
                'name' => 'required|string|max:100',
                'slug' => 'required|string|max:100',
                'icon' => 'nullable',
            ],
        ],
        'products' => [
            'model'      => Product::class,
            'label'      => 'Barang',
            'icon'       => '📦',
            'columns'    => ['id', 'name', 'price', 'stock', 'status', 'verification_status', 'seller_id', 'category_id', 'created_at'],
            'searchable' => ['name', 'description'],
            'editable'   => ['seller_id', 'category_id', 'name', 'description', 'condition', 'specification', 'completeness', 'sell_reason', 'price', 'stock', 'is_negotiable', 'status', 'verification_status', 'verified_by', 'rejection_note', 'scan_result', 'scan_note', 'expires_at'],
            'rules_create' => [
                'seller_id'   => 'required|exists:users,id',
                'category_id' => 'required|exists:categories,id',
                'name'        => 'required|string|max:150',
                'price'       => 'required|numeric|min:0',
                'stock'       => 'required|integer|min:0',
                'status'      => 'required|in:available,sold,hidden,archived',
                'verification_status' => 'required|in:pending,verified,rejected',
            ],
            'rules_update' => [
                'seller_id'   => 'required|exists:users,id',
                'category_id' => 'required|exists:categories,id',
                'name'        => 'required|string|max:150',
                'price'       => 'required|numeric|min:0',
                'stock'       => 'required|integer|min:0',
                'status'      => 'required|in:available,sold,hidden,archived',
                'verification_status' => 'required|in:pending,verified,rejected',
            ],
        ],
        'product_images' => [
            'model'      => ProductImage::class,
            'label'      => 'Foto Barang',
            'icon'       => '🖼️',
            'columns'    => ['id', 'product_id', 'image_path', 'sort_order'],
            'searchable' => ['image_path'],
            'editable'   => ['product_id', 'image_path', 'sort_order'],
            'image_fields' => [
                'image_path' => 'products',
            ],
            'rules_create' => [
                'product_id' => 'required|exists:products,id',
                'image_path' => 'nullable',
                'sort_order' => 'nullable|integer|min:0',
            ],
            'rules_update' => [
                'product_id' => 'required|exists:products,id',
                'image_path' => 'nullable',
                'sort_order' => 'nullable|integer|min:0',
            ],
        ],
        'favorites' => [
            'model'      => Favorite::class,
            'label'      => 'Favorit',
            'icon'       => '❤️',
            'columns'    => ['id', 'user_id', 'product_id', 'created_at'],
            'searchable' => [],
            'editable'   => ['user_id', 'product_id'],
            'rules_create' => [
                'user_id'    => 'required|exists:users,id',
                'product_id' => 'required|exists:products,id',
            ],
            'rules_update' => [
                'user_id'    => 'required|exists:users,id',
                'product_id' => 'required|exists:products,id',
            ],
        ],
        'cod_points' => [
            'model'      => CodPoint::class,
            'label'      => 'Titik COD',
            'icon'       => '📍',
            'columns'    => ['id', 'name', 'address', 'lat', 'lng', 'is_active', 'created_at'],
            'searchable' => ['name', 'address'],
            'editable'   => ['name', 'address', 'lat', 'lng', 'is_active'],
            'rules_create' => [
                'name'      => 'required|string|max:100',
                'address'   => 'nullable|string|max:255',
                'lat'       => 'nullable|numeric',
                'lng'       => 'nullable|numeric',
                'is_active' => 'nullable|boolean',
            ],
            'rules_update' => [
                'name'      => 'required|string|max:100',
                'address'   => 'nullable|string|max:255',
                'lat'       => 'nullable|numeric',
                'lng'       => 'nullable|numeric',
                'is_active' => 'nullable|boolean',
            ],
        ],
        'conversations' => [
            'model'      => Conversation::class,
            'label'      => 'Percakapan',
            'icon'       => '💬',
            'columns'    => ['id', 'product_id', 'buyer_id', 'seller_id', 'created_at', 'updated_at'],
            'searchable' => [],
            'editable'   => ['product_id', 'buyer_id', 'seller_id'],
            'rules_create' => [
                'product_id' => 'required|exists:products,id',
                'buyer_id'   => 'required|exists:users,id',
                'seller_id'  => 'required|exists:users,id',
            ],
            'rules_update' => [
                'product_id' => 'required|exists:products,id',
                'buyer_id'   => 'required|exists:users,id',
                'seller_id'  => 'required|exists:users,id',
            ],
        ],
        'messages' => [
            'model'      => Message::class,
            'label'      => 'Pesan',
            'icon'       => '✉️',
            'columns'    => ['id', 'conversation_id', 'sender_id', 'type', 'message', 'offer_price', 'offer_status', 'created_at'],
            'searchable' => ['message'],
            'editable'   => ['conversation_id', 'sender_id', 'type', 'message', 'offer_price', 'offer_status', 'parent_offer_id', 'location_lat', 'location_lng', 'location_name', 'location_address', 'read_at'],
            'rules_create' => [
                'conversation_id' => 'required|exists:conversations,id',
                'sender_id'       => 'required|exists:users,id',
                'type'            => 'required|in:text,offer,location,system',
                'message'         => 'nullable|string',
            ],
            'rules_update' => [
                'conversation_id' => 'required|exists:conversations,id',
                'sender_id'       => 'required|exists:users,id',
                'type'            => 'required|in:text,offer,location,system',
                'message'         => 'nullable|string',
            ],
        ],
        'marketplace_deals' => [
            'model'      => MarketplaceDeal::class,
            'label'      => 'Transaksi',
            'icon'       => '🤝',
            'columns'    => ['id', 'product_id', 'buyer_id', 'seller_id', 'agreed_price', 'quantity', 'status', 'created_at'],
            'searchable' => [],
            'editable'   => ['conversation_id', 'accepted_message_id', 'product_id', 'buyer_id', 'seller_id', 'quantity', 'agreed_price', 'payment_method', 'status', 'agreed_at', 'completed_at', 'cancelled_at', 'cancelled_by', 'cancel_reason', 'cancel_note'],
            'rules_create' => [
                'conversation_id' => 'required|exists:conversations,id',
                'product_id'      => 'required|exists:products,id',
                'buyer_id'        => 'required|exists:users,id',
                'seller_id'       => 'required|exists:users,id',
                'agreed_price'    => 'required|numeric|min:0',
                'quantity'        => 'required|integer|min:1',
                'status'          => 'required|in:agreed,completed,cancelled',
            ],
            'rules_update' => [
                'product_id'   => 'required|exists:products,id',
                'buyer_id'     => 'required|exists:users,id',
                'seller_id'    => 'required|exists:users,id',
                'agreed_price' => 'required|numeric|min:0',
                'quantity'     => 'required|integer|min:1',
                'status'       => 'required|in:agreed,completed,cancelled',
            ],
        ],
        'reviews' => [
            'model'      => Review::class,
            'label'      => 'Ulasan',
            'icon'       => '⭐',
            'columns'    => ['id', 'marketplace_deal_id', 'reviewer_id', 'reviewee_id', 'rating', 'comment', 'created_at'],
            'searchable' => ['comment'],
            'editable'   => ['marketplace_deal_id', 'reviewer_id', 'reviewee_id', 'rating', 'comment'],
            'rules_create' => [
                'marketplace_deal_id' => 'required|exists:marketplace_deals,id',
                'reviewer_id'         => 'required|exists:users,id',
                'reviewee_id'         => 'required|exists:users,id',
                'rating'              => 'required|integer|between:1,5',
                'comment'             => 'nullable|string|max:1000',
            ],
            'rules_update' => [
                'marketplace_deal_id' => 'required|exists:marketplace_deals,id',
                'reviewer_id'         => 'required|exists:users,id',
                'reviewee_id'         => 'required|exists:users,id',
                'rating'              => 'required|integer|between:1,5',
                'comment'             => 'nullable|string|max:1000',
            ],
        ],
        'reports' => [
            'model'      => Report::class,
            'label'      => 'Laporan',
            'icon'       => '🚨',
            'columns'    => ['id', 'reporter_id', 'reported_user_id', 'reason', 'status', 'resolution', 'created_at'],
            'searchable' => ['reason', 'description'],
            'editable'   => ['reporter_id', 'reported_user_id', 'marketplace_deal_id', 'product_id', 'reason', 'description', 'status', 'resolution', 'admin_note', 'reviewed_by', 'reviewed_at'],
            'rules_create' => [
                'reporter_id' => 'required|exists:users,id',
                'reason'      => 'required|string|max:100',
                'status'      => 'required|in:pending,reviewed,resolved,dismissed',
            ],
            'rules_update' => [
                'reporter_id' => 'required|exists:users,id',
                'reason'      => 'required|string|max:100',
                'status'      => 'required|in:pending,reviewed,resolved,dismissed',
            ],
        ],
        'notifications' => [
            'model'      => Notification::class,
            'label'      => 'Notifikasi',
            'icon'       => '🔔',
            'columns'    => ['id', 'user_id', 'title', 'type', 'is_read', 'created_at'],
            'searchable' => ['title', 'body'],
            'editable'   => ['user_id', 'title', 'body', 'type', 'reference_id', 'is_read'],
            'rules_create' => [
                'user_id' => 'required|exists:users,id',
                'title'   => 'required|string|max:150',
                'body'    => 'nullable|string|max:255',
                'type'    => 'nullable|string|max:50',
            ],
            'rules_update' => [
                'user_id' => 'required|exists:users,id',
                'title'   => 'required|string|max:150',
                'body'    => 'nullable|string|max:255',
                'type'    => 'nullable|string|max:50',
            ],
        ],
        'admin_audit_logs' => [
            'model'      => AdminAuditLog::class,
            'label'      => 'Audit Log',
            'icon'       => '📋',
            'columns'    => ['id', 'admin_id', 'category', 'action', 'target', 'note', 'created_at'],
            'searchable' => ['action', 'target', 'note'],
            'editable'   => ['admin_id', 'category', 'action', 'target', 'note', 'meta'],
            'rules_create' => [
                'category' => 'required|string|max:30',
                'action'   => 'required|string|max:100',
            ],
            'rules_update' => [
                'category' => 'required|string|max:30',
                'action'   => 'required|string|max:100',
            ],
        ],
        'settings' => [
            'model'      => Setting::class,
            'label'      => 'Pengaturan',
            'icon'       => '⚙️',
            'columns'    => ['key', 'value'],
            'searchable' => ['key', 'value'],
            'editable'   => ['key', 'value'],
            'pk'         => 'key',
            'rules_create' => [
                'key'   => 'required|string|max:100|unique:settings,key',
                'value' => 'nullable|string',
            ],
            'rules_update' => [
                'key'   => 'required|string|max:100',
                'value' => 'nullable|string',
            ],
        ],
        'announcements' => [
            'model'      => Announcement::class,
            'label'      => 'Pengumuman',
            'icon'       => '📢',
            'columns'    => ['id', 'title', 'audience', 'created_by', 'scheduled_at', 'sent_at', 'created_at'],
            'searchable' => ['title', 'body'],
            'editable'   => ['title', 'body', 'audience', 'created_by', 'scheduled_at', 'sent_at'],
            'rules_create' => [
                'title'    => 'required|string|max:150',
                'body'     => 'required|string',
                'audience' => 'nullable|in:all,user,admin',
            ],
            'rules_update' => [
                'title'    => 'required|string|max:150',
                'body'     => 'required|string',
                'audience' => 'nullable|in:all,user,admin',
            ],
        ],
    ];

    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * Halaman utama Data Manager — daftar tabel yang tersedia.
     */
    public function index(): View
    {
        $tables = collect(self::TABLE_REGISTRY)->map(fn ($cfg, $slug) => [
            'slug'  => $slug,
            'label' => $cfg['label'],
            'icon'  => $cfg['icon'],
            'count' => $cfg['model']::count(),
        ]);

        return view('admin.data-manager.index', compact('tables'));
    }

    /**
     * Tampilkan data tabel tertentu dengan pencarian dan paginasi.
     */
    public function table(Request $request, string $table): View
    {
        $config = $this->resolveTable($table);
        $modelClass = $config['model'];
        $pk = $config['pk'] ?? 'id';

        $query = $modelClass::query();

        // Pencarian
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search, $config) {
                foreach ($config['searchable'] as $col) {
                    $q->orWhere($col, 'like', "%{$search}%");
                }
            });
        }

        // Pengurutan
        $sortCol = $request->input('sort', $pk);
        $sortDir = $request->input('dir', 'desc');
        if (in_array($sortCol, $config['columns'])) {
            $query->orderBy($sortCol, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy($pk, 'desc');
        }

        $records = $query->paginate(20)->withQueryString();

        // Ambil daftar kolom dari DB schema untuk form (semua kolom)
        $allColumns = Schema::getColumnListing((new $modelClass)->getTable());

        $tables = collect(self::TABLE_REGISTRY)->map(fn ($cfg, $slug) => [
            'slug'  => $slug,
            'label' => $cfg['label'],
            'icon'  => $cfg['icon'],
        ]);

        return view('admin.data-manager.table', compact(
            'config', 'table', 'records', 'allColumns', 'tables',
            'search', 'sortCol', 'sortDir', 'pk'
        ));
    }

    /**
     * Ambil satu record sebagai JSON (untuk modal edit).
     */
    public function show(string $table, string $id): JsonResponse
    {
        $config = $this->resolveTable($table);
        $pk = $config['pk'] ?? 'id';
        $record = $config['model']::where($pk, $id)->firstOrFail();
        $data = $record->toArray();

        // Tambahkan URL gambar untuk preview di modal edit
        $imageFields = array_keys($config['image_fields'] ?? []);
        foreach ($imageFields as $imgField) {
            if (!empty($data[$imgField])) {
                $path = $data[$imgField];
                $data[$imgField . '_url'] = str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
            }
        }

        return response()->json($data);
    }

    /**
     * Simpan record baru.
     */
    public function store(Request $request, string $table): RedirectResponse
    {
        $config = $this->resolveTable($table);
        $rules  = $config['rules_create'] ?? [];
        $imageFields = $config['image_fields'] ?? [];

        // Sesuaikan validasi untuk image fields
        foreach ($imageFields as $field => $folder) {
            if ($request->hasFile($field)) {
                $rules[$field] = 'required|image|mimes:jpg,jpeg,png,webp,svg,gif|max:5120';
            } else {
                $rules[$field] = 'nullable';
            }
        }

        $data = $request->validate($rules);

        // Upload file gambar jika ada
        foreach ($imageFields as $field => $folder) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $ext = $file->getClientOriginalExtension() ?: 'webp';
                $filename = Str::uuid() . '.' . $ext;
                $path = $file->storeAs($folder, $filename, 'public');
                $data[$field] = $path;
            }
        }

        // Khusus users: hash password
        if ($table === 'users' && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Tangkap semua field editable yang dikirim (termasuk yang nullable tanpa rules)
        foreach ($config['editable'] as $field) {
            if ($request->has($field) && !isset($data[$field]) && !array_key_exists($field, $imageFields)) {
                $data[$field] = $request->input($field);
            }
        }

        // Khusus users: password sudah di-hash di atas
        if ($table === 'users' && isset($data['password']) && !str_starts_with($data['password'], '$2y$')) {
            $data['password'] = Hash::make($data['password']);
        }

        try {
            DB::transaction(function () use ($config, $data, $table) {
                $record = $config['model']::create($data);

                $this->auditLogService->log(
                    'data_manager',
                    "Tambah data ke {$table}",
                    $this->getRecordIdentifier($record, $config),
                    'Mode maintenance — Data Manager',
                    ['data' => $this->sanitizeForLog($data)]
                );
            });

            return redirect()
                ->route('admin.data-manager.table', $table)
                ->with('success', "Data berhasil ditambahkan ke tabel {$config['label']}.");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', "Gagal menambah data: {$e->getMessage()}");
        }
    }

    /**
     * Perbarui record yang ada.
     */
    public function update(Request $request, string $table, string $id): RedirectResponse
    {
        $config = $this->resolveTable($table);
        $pk     = $config['pk'] ?? 'id';
        $rules  = $config['rules_update'] ?? [];
        $imageFields = $config['image_fields'] ?? [];

        // Tambahkan ignore untuk unique rules
        if ($table === 'users') {
            $rules['email']    = "required|email|unique:users,email,{$id}";
            $rules['nim']      = "nullable|string|max:20|unique:users,nim,{$id}";
            $rules['username'] = "nullable|string|max:50|unique:users,username,{$id}";
        }
        if ($table === 'categories') {
            $rules['slug'] = "required|string|max:100|unique:categories,slug,{$id}";
        }
        if ($table === 'settings') {
            $rules['key'] = "required|string|max:100|unique:settings,key,{$id},key";
        }

        // Sesuaikan validasi untuk image fields
        foreach ($imageFields as $field => $folder) {
            if ($request->hasFile($field)) {
                $rules[$field] = 'nullable|image|mimes:jpg,jpeg,png,webp,svg,gif|max:5120';
            } else {
                $rules[$field] = 'nullable';
            }
        }

        $data = $request->validate($rules);
        $record = $config['model']::where($pk, $id)->firstOrFail();

        // Upload file gambar baru jika ada
        foreach ($imageFields as $field => $folder) {
            if ($request->hasFile($field)) {
                // Hapus file lama jika ada di storage
                $oldPath = $record->{$field};
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                $file = $request->file($field);
                $ext = $file->getClientOriginalExtension() ?: 'webp';
                $filename = Str::uuid() . '.' . $ext;
                $path = $file->storeAs($folder, $filename, 'public');
                $data[$field] = $path;
            } elseif (!$request->filled($field)) {
                // Jika tidak ada upload file baru dan field tidak diisi teks, pertahankan gambar lama
                unset($data[$field]);
            }
        }

        // Khusus users: hash password jika diisi, hapus jika kosong
        if ($table === 'users') {
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
        }

        // Tangkap field editable tambahan
        foreach ($config['editable'] as $field) {
            if ($request->has($field) && !isset($data[$field]) && !array_key_exists($field, $imageFields)) {
                $data[$field] = $request->input($field);
            }
        }

        try {
            DB::transaction(function () use ($config, $data, $table, $record) {
                $old = $record->toArray();

                // Untuk User: gunakan forceFill agar role, status, dll bisa diubah
                if ($table === 'users') {
                    $record->forceFill($data)->save();
                } else {
                    $record->update($data);
                }

                $this->auditLogService->log(
                    'data_manager',
                    "Ubah data di {$table}",
                    $this->getRecordIdentifier($record, $config),
                    'Mode maintenance — Data Manager',
                    ['old' => $this->sanitizeForLog($old), 'new' => $this->sanitizeForLog($data)]
                );
            });

            return redirect()
                ->route('admin.data-manager.table', $table)
                ->with('success', "Data berhasil diperbarui di tabel {$config['label']}.");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', "Gagal mengubah data: {$e->getMessage()}");
        }
    }

    /**
     * Hapus record.
     */
    public function destroy(string $table, string $id): RedirectResponse
    {
        $config = $this->resolveTable($table);
        $pk     = $config['pk'] ?? 'id';

        try {
            DB::transaction(function () use ($config, $table, $pk, $id) {
                $record = $config['model']::where($pk, $id)->firstOrFail();
                $snapshot = $record->toArray();

                // Hapus file gambar jika ada di storage
                $imageFields = $config['image_fields'] ?? [];
                foreach ($imageFields as $field => $folder) {
                    $img = $record->{$field};
                    if ($img && Storage::disk('public')->exists($img)) {
                        Storage::disk('public')->delete($img);
                    }
                }

                $record->delete();

                $this->auditLogService->log(
                    'data_manager',
                    "Hapus data dari {$table}",
                    $this->getRecordIdentifier($record, $config),
                    'Mode maintenance — Data Manager',
                    ['deleted' => $this->sanitizeForLog($snapshot)]
                );
            });

            return redirect()
                ->route('admin.data-manager.table', $table)
                ->with('success', "Data berhasil dihapus dari tabel {$config['label']}.");
        } catch (\Exception $e) {
            return back()->with('error', "Gagal menghapus data: {$e->getMessage()}");
        }
    }

    // ──────────────────────────── Helpers ────────────────────────────

    /**
     * Ambil konfigurasi tabel atau abort 404.
     */
    private function resolveTable(string $slug): array
    {
        if (! isset(self::TABLE_REGISTRY[$slug])) {
            abort(404, "Tabel '{$slug}' tidak ditemukan.");
        }

        return self::TABLE_REGISTRY[$slug];
    }

    /**
     * Buat label identifier untuk audit log.
     */
    private function getRecordIdentifier(Model $record, array $config): string
    {
        $pk = $config['pk'] ?? 'id';
        $keyVal = $record->getAttribute($pk);

        // Coba pakai nama/title sebagai label
        $label = $record->getAttribute('name')
            ?? $record->getAttribute('title')
            ?? $record->getAttribute('key')
            ?? null;

        return $label
            ? "{$config['label']} #{$keyVal} ({$label})"
            : "{$config['label']} #{$keyVal}";
    }

    /**
     * Bersihkan data sensitif sebelum disimpan ke audit log.
     */
    private function sanitizeForLog(array $data): array
    {
        $sensitive = ['password', 'remember_token', 'fcm_token', 'completion_token_hash', 'completion_code_hash'];
        foreach ($sensitive as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}
