<?php

$files = [
    'app/Http/Requests/Api/RegisterRequest.php' => <<<PHP
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'nim' => 'required|string|max:20|unique:users|regex:/^[0-9.]+$/',
            'email' => [
                'required',
                'email',
                'unique:users',
                function (\$attribute, \$value, \$fail) {
                    \$setting = DB::table('settings')->where('key', 'allowed_email_domains')->first();
                    if (\$setting && \$setting->value) {
                        \$domains = explode("\n", str_replace("\r", "", \$setting->value));
                        \$valid = false;
                        foreach (\$domains as \$domain) {
                            \$domain = trim(\$domain);
                            if (\$domain && str_ends_with(\$value, \$domain)) {
                                \$valid = true;
                                break;
                            }
                        }
                        if (!\$valid) {
                            \$fail('Email harus menggunakan domain yang diizinkan.');
                        }
                    }
                }
            ],
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
PHP,
    'app/Http/Requests/Api/LoginRequest.php' => <<<PHP
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string',
        ];
    }
}
PHP,
    'app/Http/Requests/Api/UpdateProfileRequest.php' => <<<PHP
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|nullable|string|max:30',
            'profile_photo' => 'sometimes|nullable|image|max:2048|mimes:jpg,jpeg,png,webp',
        ];
    }
}
PHP,
    'app/Http/Resources/UserResource.php' => <<<PHP
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request \$request): array
    {
        return [
            'id' => \$this->id,
            'name' => \$this->name,
            'nim' => \$this->nim,
            'email' => \$this->email,
            'phone' => \$this->phone,
            'profile_photo_url' => \$this->profile_photo ? Storage::url(\$this->profile_photo) : null,
            'role' => \$this->role,
            'status' => \$this->status,
            'email_verified_at' => \$this->email_verified_at,
            'created_at' => \$this->created_at,
        ];
    }
}
PHP,
    'app/Http/Resources/NotificationResource.php' => <<<PHP
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class NotificationResource extends JsonResource
{
    public function toArray(Request \$request): array
    {
        return [
            'id' => \$this->id,
            'title' => \$this->title,
            'body' => \$this->body,
            'type' => \$this->type,
            'reference_id' => \$this->reference_id,
            'is_read' => \$this->is_read,
            'created_at' => Carbon::parse(\$this->created_at)->setTimezone('Asia/Jakarta')->isoFormat('D MMM YYYY, HH:mm'),
        ];
    }
}
PHP,
    'app/Http/Controllers/Api/V1/AuthController.php' => <<<PHP
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest \$request)
    {
        \$user = User::create([
            'name' => \$request->name,
            'nim' => \$request->nim,
            'email' => \$request->email,
            'password' => Hash::make(\$request->password),
            'role' => 'user',
            'status' => 'active',
        ]);

        \$code = Str::random(6); // should be numbers usually but 6-digit random
        \$code = rand(100000, 999999);
        Cache::put('email_verify_' . \$user->email, \$code, now()->addMinutes(15));
        Log::info('Email verification code for ' . \$user->email . ' is ' . \$code);

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan cek email untuk kode verifikasi.',
            'user' => new UserResource(\$user)
        ], 201);
    }

    public function verifyEmail(Request \$request)
    {
        \$request->validate([
            'email' => 'required|email',
            'code' => 'required'
        ]);

        \$cachedCode = Cache::get('email_verify_' . \$request->email);

        if (!\$cachedCode || \$cachedCode != \$request->code) {
            return response()->json(['message' => 'Kode verifikasi tidak valid atau kedaluwarsa.', 'errors' => ['code' => ['Kode tidak valid']]], 400);
        }

        \$user = User::where('email', \$request->email)->firstOrFail();
        \$user->update(['email_verified_at' => now()]);
        Cache::forget('email_verify_' . \$request->email);

        return response()->json([
            'message' => 'Email berhasil diverifikasi',
            'user' => new UserResource(\$user)
        ]);
    }

    public function resendVerification(Request \$request)
    {
        \$request->validate(['email' => 'required|email']);
        
        \$key = 'resend_verification_' . \$request->email;
        if (RateLimiter::tooManyAttempts(\$key, 1)) {
            return response()->json(['message' => 'Terlalu banyak percobaan. Tunggu beberapa saat.'], 429);
        }
        RateLimiter::hit(\$key, 60);

        \$user = User::where('email', \$request->email)->firstOrFail();
        if (\$user->email_verified_at) {
            return response()->json(['message' => 'Email sudah diverifikasi.'], 400);
        }

        \$code = rand(100000, 999999);
        Cache::put('email_verify_' . \$user->email, \$code, now()->addMinutes(15));
        Log::info('Resend Email verification code for ' . \$user->email . ' is ' . \$code);

        return response()->json(['message' => 'Kode verifikasi telah dikirim ulang.']);
    }

    public function login(LoginRequest \$request)
    {
        \$key = 'login:' . \$request->ip();
        if (RateLimiter::tooManyAttempts(\$key, 5)) {
            return response()->json(['message' => 'Terlalu banyak percobaan login. Silakan coba lagi nanti.', 'errors' => []], 429);
        }

        \$loginType = filter_var(\$request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'nim';
        \$credentials = [
            \$loginType => \$request->login,
            'password' => \$request->password
        ];

        if (!Auth::attempt(\$credentials)) {
            RateLimiter::hit(\$key);
            return response()->json(['message' => 'Kredensial tidak valid', 'errors' => ['login' => ['Kredensial tidak valid']]], 401);
        }

        \$user = Auth::user();
        if (\$user->status === 'suspended') {
            Auth::logout();
            return response()->json(['message' => 'Akun ditangguhkan.', 'errors' => []], 401);
        }

        RateLimiter::clear(\$key);

        \$token = \$user->createToken('silago_api')->plainTextToken;

        if (\$request->filled('fcm_token')) {
            \$user->update(['fcm_token' => \$request->fcm_token]);
        }

        return response()->json([
            'token' => \$token,
            'user' => new UserResource(\$user)
        ]);
    }

    public function logout(Request \$request)
    {
        \$request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }

    public function forgotPassword(Request \$request)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function resetPassword(Request \$request)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
PHP,
    'app/Http/Controllers/Api/V1/ProfileController.php' => <<<PHP
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function me(Request \$request)
    {
        return response()->json(new UserResource(\$request->user()));
    }

    public function update(UpdateProfileRequest \$request)
    {
        \$user = \$request->user();
        
        \$data = [];
        if (\$request->has('name')) {
            \$data['name'] = \$request->name;
        }
        if (\$request->has('phone')) {
            \$data['phone'] = \$request->phone;
        }

        if (\$request->hasFile('profile_photo')) {
            if (\$user->profile_photo && Storage::disk('public')->exists(\$user->profile_photo)) {
                Storage::disk('public')->delete(\$user->profile_photo);
            }
            \$file = \$request->file('profile_photo');
            \$filename = Str::uuid() . '.' . \$file->getClientOriginalExtension();
            \$path = \$file->storeAs('profile-photos', \$filename, 'public');
            \$data['profile_photo'] = \$path;
        }

        \$user->update(\$data);

        return response()->json(new UserResource(\$user->fresh()));
    }

    public function updateFcmToken(Request \$request)
    {
        \$request->validate(['fcm_token' => 'required|string']);
        \$request->user()->update(['fcm_token' => \$request->fcm_token]);
        return response()->json(['message' => 'FCM Token updated']);
    }

    public function summary(Request \$request)
    {
        \$user = \$request->user();

        // avg_rating
        \$avg_rating = \App\Models\Review::where('reviewee_id', \$user->id)->avg('rating') ?? 0.0;
        \$total_reviews = \App\Models\Review::where('reviewee_id', \$user->id)->count();
        
        // total_sold, total_bought
        \$total_sold = \App\Models\Deal::whereHas('product', function(\$q) use (\$user) {
            \$q->where('user_id', \$user->id);
        })->where('status', 'completed')->count();

        \$total_bought = \App\Models\Deal::where('buyer_id', \$user->id)->where('status', 'completed')->count();

        // active_products
        \$active_products = \App\Models\Product::where('user_id', \$user->id)
            ->where('status', 'available')
            ->where('is_verified', true)
            ->count();
            
        // active_deals
        \$active_deals = \App\Models\Deal::where(function(\$q) use (\$user) {
                \$q->where('buyer_id', \$user->id)
                  ->orWhereHas('product', function(\$q2) use (\$user) {
                      \$q2->where('user_id', \$user->id);
                  });
            })
            ->where('status', 'agreed')
            ->count();

        // total_favorites
        \$total_favorites = \App\Models\Favorite::where('user_id', \$user->id)->count();

        return response()->json([
            'avg_rating' => round(\$avg_rating, 1),
            'total_reviews' => \$total_reviews,
            'total_sold' => \$total_sold,
            'total_bought' => \$total_bought,
            'active_products' => \$active_products,
            'active_deals' => \$active_deals,
            'total_favorites' => \$total_favorites,
        ]);
    }
}
PHP,
    'app/Http/Controllers/Api/V1/ConfigController.php' => <<<PHP
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ConfigController extends Controller
{
    public function index()
    {
        \$settings = DB::table('settings')->pluck('value', 'key');
        
        return response()->json([
            'max_photos' => isset(\$settings['max_photos']) ? (int)\$settings['max_photos'] : 5,
            'maintenance_mode' => isset(\$settings['maintenance_mode']) ? filter_var(\$settings['maintenance_mode'], FILTER_VALIDATE_BOOLEAN) : false,
            'listing_active_days' => isset(\$settings['listing_active_days']) ? (int)\$settings['listing_active_days'] : 30,
            'low_stock_threshold' => isset(\$settings['low_stock_threshold']) ? (int)\$settings['low_stock_threshold'] : 3,
            'report_reasons' => [
                'Penipuan',
                'Barang terlarang',
                'Sengketa transaksi',
                'Perilaku pengguna',
                'Foto tidak sesuai',
                'Spam chat',
                'Lainnya'
            ],
            'allowed_email_domains' => isset(\$settings['allowed_email_domains']) ? \$settings['allowed_email_domains'] : ''
        ]);
    }
}
PHP,
    'app/Http/Controllers/Api/V1/CategoryController.php' => <<<PHP
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        \$categories = Category::withCount('products')
            ->get(['id', 'name', 'slug', 'icon', 'products_count']);
            
        return response()->json(\$categories);
    }
}
PHP,
    'app/Http/Controllers/Api/V1/CodPointController.php' => <<<PHP
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CodPoint;

class CodPointController extends Controller
{
    public function index()
    {
        \$points = CodPoint::where('is_active', true)->get();
        return response()->json(\$points);
    }
}
PHP,
    'app/Http/Controllers/Api/V1/NotificationController.php' => <<<PHP
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request \$request)
    {
        \$notifications = Notification::where('user_id', \$request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return NotificationResource::collection(\$notifications);
    }

    public function unreadCount(Request \$request)
    {
        \$count = Notification::where('user_id', \$request->user()->id)
            ->where('is_read', false)
            ->count();
            
        return response()->json(['count' => \$count]);
    }

    public function markRead(Request \$request, Notification \$notification)
    {
        if (\$notification->user_id !== \$request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        \$notification->update(['is_read' => true]);
        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllRead(Request \$request)
    {
        Notification::where('user_id', \$request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        return response()->json(['message' => 'All marked as read']);
    }
}
PHP,
    'app/Http/Controllers/Api/V1/UserController.php' => <<<PHP
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Review;
use App\Models\Deal;
use App\Models\Product;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request \$request, User \$user)
    {
        \$avg_rating = Review::where('reviewee_id', \$user->id)->avg('rating') ?? 0.0;
        \$total_reviews = Review::where('reviewee_id', \$user->id)->count();
        
        \$total_sold = Deal::whereHas('product', function(\$q) use (\$user) {
            \$q->where('user_id', \$user->id);
        })->where('status', 'completed')->count();

        \$total_bought = Deal::where('buyer_id', \$user->id)->where('status', 'completed')->count();

        return response()->json([
            'id' => \$user->id,
            'name' => \$user->name,
            'email_verified_at' => \$user->email_verified_at,
            'avg_rating' => round(\$avg_rating, 1),
            'total_reviews' => \$total_reviews,
            'total_sold' => \$total_sold,
            'total_bought' => \$total_bought,
        ]);
    }

    public function reviews(Request \$request, User \$user)
    {
        \$reviews = Review::with('reviewer:id,name')
            ->where('reviewee_id', \$user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return response()->json(\$reviews);
    }

    public function products(Request \$request, User \$user)
    {
        \$products = Product::where('user_id', \$user->id)
            ->where('status', 'available')
            ->where('is_verified', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return response()->json(\$products);
    }
}
PHP
];

foreach (\$files as \$path => \$content) {
    \$dir = dirname(\$path);
    if (!is_dir(\$dir)) {
        mkdir(\$dir, 0755, true);
    }
    file_put_contents(\$path, \$content);
    echo "Created: \$path\n";
}
