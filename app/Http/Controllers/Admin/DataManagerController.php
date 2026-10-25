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
                'name'           => 'required|string|max:100',
                'email'          => 'required|email|unique:users,email',
                'nim'            => 'nullable|string|max:20|unique:users,nim',
                'username'       => 'nullable|string|max:50|unique:users,username',
                'password'       => 'required|string|min:8',
                'phone'          => 'nullable|string|max:30',
                'role'           => 'required|in:user,moderator,admin',
                'status'         => 'required|in:active,monitored,suspended',
                'profile_photo'  => 'nullable',
                'suspend_reason' => 'nullable|string|max:255',
            ],
            'rules_update' => [
                'name'           => 'required|string|max:100',
                'email'          => 'required|email',
                'nim'            => 'nullable|string|max:20',
                'username'       => 'nullable|string|max:50',
                'password'       => 'nullable|string|min:8',
                'phone'          => 'nullable|string|max:30',
                'role'           => 'required|in:user,moderator,admin',
                'status'         => 'required|in:active,monitored,suspended',
                'profile_photo'  => 'nullable',
                'suspend_reason' => 'nullable|string|max:255',
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
                'slug' => 'required|string|max:100|unique:categories,slug',
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
            'editable'   => [
                'seller_id', 'category_id', 'name', 'price', 'stock', 'condition',
                'status', 'verification_status', 'verified_by', 'expires_at', 'description',
                'specification', 'completeness', 'sell_reason', 'rejection_note', 'scan_result', 'scan_note'
            ],
            'rules_create' => [
                'seller_id'           => 'required|exists:users,id',
                'category_id'         => 'required|exists:categories,id',
                'name'                => 'required|string|max:150',
                'price'               => 'required|numeric|min:0',
                'stock'               => 'required|integer|min:0',
                'condition'           => 'nullable|string|max:50',
                'status'              => 'required|in:available,sold,hidden,archived',
                'verification_status' => 'required|in:pending,verified,rejected',
                'verified_by'         => 'nullable|exists:users,id',
                'expires_at'          => 'nullable|date',
                'description'         => 'nullable|string',
                'specification'       => 'nullable|string',
                'completeness'        => 'nullable|string|max:100',
                'sell_reason'         => 'nullable|string',
                'rejection_note'      => 'nullable|string',
                'scan_result'         => 'nullable|string|max:50',
                'scan_note'           => 'nullable|string|max:255',
            ],
            'rules_update' => [
                'seller_id'           => 'required|exists:users,id',
                'category_id'         => 'required|exists:categories,id',
                'name'                => 'required|string|max:150',
                'price'               => 'required|numeric|min:0',
                'stock'               => 'required|integer|min:0',
                'condition'           => 'nullable|string|max:50',
                'status'              => 'required|in:available,sold,hidden,archived',
                'verification_status' => 'required|in:pending,verified,rejected',
                'verified_by'         => 'nullable|exists:users,id',
                'expires_at'          => 'nullable|date',
                'description'         => 'nullable|string',
                'specification'       => 'nullable|string',
                'completeness'        => 'nullable|string|max:100',
                'sell_reason'         => 'nullable|string',
                'rejection_note'      => 'nullable|string',
                'scan_result'         => 'nullable|string|max:50',
                'scan_note'           => 'nullable|string|max:255',
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
                'buyer_id'   => 'required|exists:users,id|different:seller_id',
                'seller_id'  => 'required|exists:users,id|different:buyer_id',
            ],
            'rules_update' => [
                'product_id' => 'required|exists:products,id',
                'buyer_id'   => 'required|exists:users,id|different:seller_id',
                'seller_id'  => 'required|exists:users,id|different:buyer_id',
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
                'conversation_id'  => 'required|exists:conversations,id',
                'sender_id'        => 'required|exists:users,id',
                'type'             => 'required|in:text,offer,location,system',
                'message'          => 'nullable|string',
                'offer_price'      => 'nullable|numeric|min:0',
                'offer_status'     => 'nullable|in:pending,accepted,rejected,countered',
                'parent_offer_id'  => 'nullable|exists:messages,id',
                'location_lat'     => 'nullable|numeric',
                'location_lng'     => 'nullable|numeric',
                'location_name'    => 'nullable|string|max:255',
                'location_address' => 'nullable|string|max:255',
                'read_at'          => 'nullable|date',
            ],
            'rules_update' => [
                'conversation_id'  => 'required|exists:conversations,id',
                'sender_id'        => 'required|exists:users,id',
                'type'             => 'required|in:text,offer,location,system',
                'message'          => 'nullable|string',
                'offer_price'      => 'nullable|numeric|min:0',
                'offer_status'     => 'nullable|in:pending,accepted,rejected,countered',
                'parent_offer_id'  => 'nullable|exists:messages,id',
                'location_lat'     => 'nullable|numeric',
                'location_lng'     => 'nullable|numeric',
                'location_name'    => 'nullable|string|max:255',
                'location_address' => 'nullable|string|max:255',
                'read_at'          => 'nullable|date',
            ],
        ],
        'marketplace_deals' => [
            'model'      => MarketplaceDeal::class,
            'label'      => 'Transaksi',
            'icon'       => '🤝',
            'columns'    => ['id', 'product_id', 'buyer_id', 'seller_id', 'agreed_price', 'quantity', 'status', 'created_at'],
            'searchable' => [],
            'editable'   => [
                'conversation_id', 'product_id', 'buyer_id', 'seller_id', 'quantity',
                'agreed_price', 'payment_method', 'status', 'accepted_message_id',
                'agreed_at', 'completed_at', 'cancelled_at', 'cancelled_by', 'cancel_reason', 'cancel_note'
            ],
            'rules_create' => [
                'conversation_id'     => 'required|exists:conversations,id',
                'product_id'          => 'required|exists:products,id',
                'buyer_id'            => 'required|exists:users,id|different:seller_id',
                'seller_id'           => 'required|exists:users,id|different:buyer_id',
                'quantity'            => 'required|integer|min:1',
                'agreed_price'        => 'required|numeric|min:0',
                'payment_method'      => 'nullable|string|max:50',
                'status'              => 'required|in:agreed,completed,cancelled',
                'accepted_message_id' => 'nullable|exists:messages,id',
                'agreed_at'           => 'nullable|date',
                'completed_at'        => 'nullable|date',
                'cancelled_at'        => 'nullable|date',
                'cancelled_by'        => 'nullable|exists:users,id',
                'cancel_reason'       => 'nullable|string|max:255',
                'cancel_note'         => 'nullable|string',
            ],
            'rules_update' => [
                'conversation_id'     => 'required|exists:conversations,id',
                'product_id'          => 'required|exists:products,id',
                'buyer_id'            => 'required|exists:users,id|different:seller_id',
                'seller_id'           => 'required|exists:users,id|different:buyer_id',
                'quantity'            => 'required|integer|min:1',
                'agreed_price'        => 'required|numeric|min:0',
                'payment_method'      => 'nullable|string|max:50',
                'status'              => 'required|in:agreed,completed,cancelled',
                'accepted_message_id' => 'nullable|exists:messages,id',
                'agreed_at'           => 'nullable|date',
                'completed_at'        => 'nullable|date',
                'cancelled_at'        => 'nullable|date',
                'cancelled_by'        => 'nullable|exists:users,id',
                'cancel_reason'       => 'nullable|string|max:255',
                'cancel_note'         => 'nullable|string',
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
                'reviewer_id'         => 'required|exists:users,id|different:reviewee_id',
                'reviewee_id'         => 'required|exists:users,id|different:reviewer_id',
                'rating'              => 'required|integer|between:1,5',
                'comment'             => 'nullable|string|max:1000',
            ],
            'rules_update' => [
                'marketplace_deal_id' => 'required|exists:marketplace_deals,id',
                'reviewer_id'         => 'required|exists:users,id|different:reviewee_id',
                'reviewee_id'         => 'required|exists:users,id|different:reviewer_id',
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
            'editable'   => [
                'reporter_id', 'reason', 'status', 'reported_user_id', 'product_id',
                'marketplace_deal_id', 'resolution', 'reviewed_by', 'reviewed_at',
                'description', 'admin_note'
            ],
            'rules_create' => [
                'reporter_id'         => 'required|exists:users,id',
                'reason'              => 'required|string|max:100',
                'status'              => 'required|in:pending,reviewed,resolved,dismissed',
                'reported_user_id'    => 'nullable|exists:users,id',
                'product_id'          => 'nullable|exists:products,id',
                'marketplace_deal_id' => 'nullable|exists:marketplace_deals,id',
                'resolution'          => 'nullable|string|max:50',
                'reviewed_by'         => 'nullable|exists:users,id',
                'reviewed_at'         => 'nullable|date',
                'description'         => 'nullable|string',
                'admin_note'          => 'nullable|string',
            ],
            'rules_update' => [
                'reporter_id'         => 'required|exists:users,id',
                'reason'              => 'required|string|max:100',
                'status'              => 'required|in:pending,reviewed,resolved,dismissed',
                'reported_user_id'    => 'nullable|exists:users,id',
                'product_id'          => 'nullable|exists:products,id',
                'marketplace_deal_id' => 'nullable|exists:marketplace_deals,id',
                'resolution'          => 'nullable|string|max:50',
                'reviewed_by'         => 'nullable|exists:users,id',
                'reviewed_at'         => 'nullable|date',
                'description'         => 'nullable|string',
                'admin_note'          => 'nullable|string',
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
                'user_id'      => 'required|exists:users,id',
                'title'        => 'required|string|max:150',
                'body'         => 'nullable|string|max:255',
                'type'         => 'nullable|string|max:50',
                'reference_id' => 'nullable|string|max:100',
                'is_read'      => 'nullable|boolean',
            ],
            'rules_update' => [
                'user_id'      => 'required|exists:users,id',
                'title'        => 'required|string|max:150',
                'body'         => 'nullable|string|max:255',
                'type'         => 'nullable|string|max:50',
                'reference_id' => 'nullable|string|max:100',
                'is_read'      => 'nullable|boolean',
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
                'admin_id' => 'nullable|exists:users,id',
                'category' => 'required|string|max:30',
                'action'   => 'required|string|max:100',
                'target'   => 'nullable|string|max:255',
                'note'     => 'nullable|string',
                'meta'     => 'nullable',
            ],
            'rules_update' => [
                'admin_id' => 'nullable|exists:users,id',
                'category' => 'required|string|max:30',
                'action'   => 'required|string|max:100',
                'target'   => 'nullable|string|max:255',
                'note'     => 'nullable|string',
                'meta'     => 'nullable',
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
                'title'        => 'required|string|max:150',
                'body'         => 'required|string',
                'audience'     => 'nullable|in:all,user,admin',
                'created_by'   => 'required|exists:users,id',
                'scheduled_at' => 'nullable|date',
                'sent_at'      => 'nullable|date',
            ],
            'rules_update' => [
                'title'        => 'required|string|max:150',
                'body'         => 'required|string',
                'audience'     => 'nullable|in:all,user,admin',
                'created_by'   => 'required|exists:users,id',
                'scheduled_at' => 'nullable|date',
                'sent_at'      => 'nullable|date',
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

        // Eager load relasi penting untuk tampilan tabel agar ramah dibaca
        $relationsToEagerLoad = [
            'products'          => ['seller:id,name', 'category:id,name'],
            'product_images'    => ['product:id,name'],
            'favorites'         => ['user:id,name', 'product:id,name'],
            'conversations'     => ['product:id,name', 'buyer:id,name', 'seller:id,name'],
            'marketplace_deals' => ['product:id,name', 'buyer:id,name', 'seller:id,name'],
            'reviews'           => ['reviewer:id,name', 'reviewee:id,name'],
            'reports'           => ['reporter:id,name', 'reportedUser:id,name', 'product:id,name'],
            'notifications'     => ['user:id,name'],
            'announcements'     => ['creator:id,name'],
        ];

        if (isset($relationsToEagerLoad[$table])) {
            $query->with($relationsToEagerLoad[$table]);
        }

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

        // Ambil opsi relasi untuk form dropdown yang user-friendly
        $relationOptions = $this->getRelationOptionsForTable($table);

        return view('admin.data-manager.table', compact(
            'config', 'table', 'records', 'allColumns', 'tables',
            'relationOptions', 'search', 'sortCol', 'sortDir', 'pk'
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

        // Foto produk yang tersimpan di database tidak punya image_path.
        if ($table === 'product_images' && $record->url) {
            $data['image_path_url'] = $record->url;
        }

        // Normalisasi format datetime untuk input type="datetime-local" (YYYY-MM-DDTHH:mm)
        foreach ($data as $key => $val) {
            if ($val && (str_ends_with($key, '_at') || in_array($key, ['expires_at', 'scheduled_at', 'sent_at']))) {
                try {
                    $data[$key] = \Carbon\Carbon::parse($val)->format('Y-m-d\TH:i');
                } catch (\Throwable) {}
            }
            if (is_bool($val)) {
                $data[$key] = $val ? 1 : 0;
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

        $validator = validator(
            $request->all(),
            $rules,
            $this->validationMessages(),
            $this->customAttributes()
        );

        $data = $validator->validate();

        // Tangkap semua field editable yang dikirim
        foreach ($config['editable'] as $field) {
            if ($request->has($field) && !isset($data[$field]) && !array_key_exists($field, $imageFields)) {
                $val = $request->input($field);
                $data[$field] = ($val === '' || $val === null) ? null : $val;
            }
        }

        // Bersihkan string kosong pada nilai nullable menjadi null
        foreach ($data as $k => $v) {
            if ($v === '') {
                $data[$k] = null;
            }
        }

        // Upload file gambar jika ada
        $pendingImageData = null;
        foreach ($imageFields as $field => $folder) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);

                if ($table === 'product_images') {
                    // Foto produk disimpan di database, bukan disk Render.
                    $data[$field] = null;
                    $data['has_data'] = true;
                    $data['mime_type'] = $file->getMimeType() ?: 'image/jpeg';
                    $pendingImageData = base64_encode(file_get_contents($file->getRealPath()));
                    continue;
                }

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

        try {
            DB::transaction(function () use ($config, $data, $table, $pendingImageData) {
                $modelClass = $config['model'];
                $record = new $modelClass();
                $record->forceFill($data)->save();

                if ($table === 'product_images' && $pendingImageData !== null) {
                    $record->data()->create(['data' => $pendingImageData]);
                }

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

        $validator = validator(
            $request->all(),
            $rules,
            $this->validationMessages(),
            $this->customAttributes()
        );

        $data = $validator->validate();
        $record = $config['model']::where($pk, $id)->firstOrFail();

        // Tangkap field editable tambahan
        foreach ($config['editable'] as $field) {
            if ($request->has($field) && !isset($data[$field]) && !array_key_exists($field, $imageFields)) {
                $val = $request->input($field);
                $data[$field] = ($val === '' || $val === null) ? null : $val;
            }
        }

        // Bersihkan string kosong pada nilai nullable menjadi null
        foreach ($data as $k => $v) {
            if ($v === '') {
                $data[$k] = null;
            }
        }

        // Upload file gambar baru jika ada
        $pendingImageData = null;
        foreach ($imageFields as $field => $folder) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);

                if ($table === 'product_images') {
                    // Foto produk disimpan di database, bukan disk Render.
                    $oldPath = $record->{$field};
                    if ($oldPath && ! str_starts_with($oldPath, 'http://') && ! str_starts_with($oldPath, 'https://') && Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }

                    $data[$field] = null;
                    $data['has_data'] = true;
                    $data['mime_type'] = $file->getMimeType() ?: 'image/jpeg';
                    $pendingImageData = base64_encode(file_get_contents($file->getRealPath()));
                    continue;
                }

                // Hapus file lama jika ada di storage
                $oldPath = $record->{$field};
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                $ext = $file->getClientOriginalExtension() ?: 'webp';
                $filename = Str::uuid() . '.' . $ext;
                $path = $file->storeAs($folder, $filename, 'public');
                $data[$field] = $path;
            } elseif (!$request->filled($field) && !array_key_exists($field, $data)) {
                // Pertahankan gambar lama jika tidak diupload baru
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

        try {
            DB::transaction(function () use ($config, $data, $table, $record, $pendingImageData) {
                $old = $record->toArray();
                $record->forceFill($data)->save();

                if ($table === 'product_images' && $pendingImageData !== null) {
                    $record->data()->delete();
                    $record->data()->create(['data' => $pendingImageData]);
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
     * Ambil opsi relasi untuk form dropdown yang user-friendly.
     */
    public function getRelationOptionsForTable(string $table): array
    {
        $config = self::TABLE_REGISTRY[$table];
        $fields = $config['editable'];
        $options = [];

        $userFields = ['seller_id', 'buyer_id', 'user_id', 'sender_id', 'reviewer_id', 'reviewee_id', 'reporter_id', 'reported_user_id', 'reviewed_by', 'verified_by', 'cancelled_by', 'admin_id', 'created_by'];

        $needsUsers = array_intersect($fields, $userFields);
        if (!empty($needsUsers)) {
            $users = User::orderBy('name')->get(['id', 'name', 'email', 'nim', 'role'])->map(fn ($u) => [
                'value' => (string) $u->id,
                'label' => "#{$u->id} - {$u->name} (" . ($u->nim ? "NIM: {$u->nim} | " : '') . "{$u->email} - {$u->role})",
            ])->all();

            foreach ($needsUsers as $uf) {
                $options[$uf] = $users;
            }
        }

        if (in_array('category_id', $fields)) {
            $options['category_id'] = Category::orderBy('name')->get(['id', 'name'])->map(fn ($c) => [
                'value' => (string) $c->id,
                'label' => "#{$c->id} - {$c->name}",
            ])->all();
        }

        if (in_array('product_id', $fields)) {
            $options['product_id'] = Product::with('seller:id,name')->latest('id')->take(300)->get(['id', 'name', 'price', 'seller_id'])->map(fn ($p) => [
                'value' => (string) $p->id,
                'label' => "#{$p->id} - {$p->name} (Rp" . number_format((float) $p->price, 0, ',', '.') . ($p->seller ? " | Penjual: " . $p->seller->name : "") . ")",
            ])->all();
        }

        if (in_array('conversation_id', $fields)) {
            $options['conversation_id'] = Conversation::with(['product:id,name', 'buyer:id,name', 'seller:id,name'])->latest('id')->take(200)->get()->map(fn ($cv) => [
                'value' => (string) $cv->id,
                'label' => "#{$cv->id} - " . ($cv->product->name ?? "Produk #{$cv->product_id}") . " (Pembeli: " . ($cv->buyer->name ?? "#{$cv->buyer_id}") . " | Penjual: " . ($cv->seller->name ?? "#{$cv->seller_id}") . ")",
            ])->all();
        }

        if (in_array('marketplace_deal_id', $fields)) {
            $options['marketplace_deal_id'] = MarketplaceDeal::with(['product:id,name', 'buyer:id,name'])->latest('id')->take(200)->get()->map(fn ($d) => [
                'value' => (string) $d->id,
                'label' => "#{$d->id} - " . ($d->product->name ?? "Produk #{$d->product_id}") . " (Rp" . number_format((float) $d->agreed_price, 0, ',', '.') . " | Status: {$d->status})",
            ])->all();
        }

        $messageFields = array_intersect($fields, ['accepted_message_id', 'parent_offer_id']);
        if (!empty($messageFields)) {
            $messages = Message::latest('id')->take(100)->get(['id', 'conversation_id', 'sender_id', 'type'])->map(fn ($m) => [
                'value' => (string) $m->id,
                'label' => "#{$m->id} - Pesan di Percakapan #{$m->conversation_id} (Pengirim #{$m->sender_id}, tipe: {$m->type})",
            ])->all();

            foreach ($messageFields as $mf) {
                $options[$mf] = $messages;
            }
        }

        return $options;
    }

    /**
     * Pesan validasi kustom dalam bahasa Indonesia.
     */
    private function validationMessages(): array
    {
        return [
            'required'  => ':attribute wajib diisi.',
            'exists'    => ':attribute yang dipilih tidak valid atau tidak ditemukan di sistem.',
            'unique'    => ':attribute sudah digunakan, silakan pilih nilai lain.',
            'in'        => ':attribute yang dipilih tidak valid.',
            'different' => ':attribute tidak boleh sama dengan :other.',
            'min'       => ':attribute minimal :min karakter atau bernilai minimal :min.',
            'max'       => ':attribute maksimal :max karakter.',
            'numeric'   => ':attribute harus berupa angka numerik.',
            'integer'   => ':attribute harus berupa bilangan bulat.',
            'email'     => ':attribute harus berupa alamat email yang valid.',
            'image'     => ':attribute harus berupa file gambar.',
            'mimes'     => ':attribute harus berformat :values.',
            'date'      => ':attribute harus berupa format tanggal dan waktu yang valid.',
        ];
    }

    /**
     * Pemetaan nama atribut ke label yang ramah pengguna.
     */
    private function customAttributes(): array
    {
        return [
            'seller_id'           => 'Penjual',
            'buyer_id'            => 'Pembeli',
            'user_id'             => 'Pengguna',
            'category_id'         => 'Kategori Barang',
            'product_id'          => 'Barang',
            'conversation_id'     => 'Percakapan',
            'marketplace_deal_id' => 'Transaksi Deal',
            'accepted_message_id' => 'Pesan Deal',
            'parent_offer_id'     => 'Penawaran Induk',
            'sender_id'           => 'Pengirim Pesan',
            'reviewer_id'         => 'Pemberi Ulasan',
            'reviewee_id'         => 'Penerima Ulasan',
            'reporter_id'         => 'Pelapor',
            'reported_user_id'    => 'Pengguna Terlapor',
            'reviewed_by'         => 'Ditinjau Oleh',
            'verified_by'         => 'Diverifikasi Oleh',
            'cancelled_by'        => 'Dibatalkan Oleh',
            'admin_id'            => 'Admin',
            'created_by'          => 'Dibuat Oleh',
            'name'                => 'Nama',
            'email'               => 'Email',
            'nim'                 => 'NIM',
            'username'            => 'Username',
            'password'            => 'Kata Sandi',
            'phone'               => 'Nomor Telepon',
            'role'                => 'Peran Pengguna',
            'status'              => 'Status',
            'verification_status' => 'Status Verifikasi',
            'price'               => 'Harga',
            'stock'               => 'Stok',
            'rating'              => 'Rating',
            'quantity'            => 'Jumlah Kuantitas',
            'agreed_price'        => 'Harga Disepakati',
            'reason'              => 'Alasan Laporan',
            'title'               => 'Judul',
            'body'                => 'Isi Pengumuman / Konten',
            'slug'                => 'Slug URL',
            'address'             => 'Alamat Titik COD',
            'lat'                 => 'Latitude Koordinat',
            'lng'                 => 'Longitude Koordinat',
            'key'                 => 'Kunci Pengaturan',
            'value'               => 'Nilai Pengaturan',
            'condition'           => 'Kondisi Barang',
            'description'         => 'Deskripsi',
            'specification'       => 'Spesifikasi',
            'completeness'        => 'Kelengkapan',
            'sell_reason'         => 'Alasan Dijual',
            'rejection_note'      => 'Catatan Penolakan',
            'scan_result'         => 'Hasil Scan',
            'scan_note'           => 'Catatan Scan',
            'expires_at'          => 'Waktu Kedaluwarsa',
            'is_active'           => 'Status Aktif',
            'type'                => 'Tipe',
            'message'             => 'Isi Pesan',
            'offer_price'         => 'Harga Penawaran',
            'offer_status'        => 'Status Penawaran',
            'location_name'       => 'Nama Lokasi',
            'location_address'    => 'Alamat Lokasi',
            'read_at'             => 'Waktu Dibaca',
            'payment_method'      => 'Metode Pembayaran',
            'agreed_at'           => 'Waktu Disepakati',
            'completed_at'        => 'Waktu Selesai',
            'cancelled_at'        => 'Waktu Dibatalkan',
            'cancel_reason'       => 'Alasan Pembatalan',
            'cancel_note'         => 'Catatan Pembatalan',
            'comment'             => 'Ulasan / Komentar',
            'resolution'          => 'Resolusi',
            'admin_note'          => 'Catatan Admin',
            'reviewed_at'         => 'Waktu Ditinjau',
            'reference_id'        => 'ID Referensi',
            'is_read'             => 'Sudah Dibaca',
            'category'            => 'Kategori Log',
            'action'              => 'Aksi Log',
            'target'              => 'Target Log',
            'note'                => 'Catatan',
            'meta'                => 'Metadata JSON',
            'audience'            => 'Target Penerima',
            'scheduled_at'        => 'Jadwal Kirim',
            'sent_at'             => 'Waktu Terkirim',
            'profile_photo'       => 'Foto Profil',
            'icon'                => 'Ikon Kategori',
            'image_path'          => 'File Foto',
            'sort_order'          => 'Urutan Tampilan',
        ];
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
