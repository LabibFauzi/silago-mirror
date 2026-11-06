<?php

$controllersDir = 'c:\\laragon\\www\\silago-web\\app\\Http\\Controllers\\Admin';
@mkdir($controllersDir, 0755, true);

$controllers = [
    'AdminAuthController' => <<<EOT
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller {
    public function login() { return view('admin.auth.login'); }
    public function authenticate(Request \$request) {
        \$credentials = \$request->validate(['email' => 'required', 'password' => 'required']);
        // Simplified auth
        if (Auth::attempt(\$credentials)) {
            return redirect()->route('admin.dashboard');
        }
        return back()->with('error', 'Login gagal');
    }
    public function logout() { Auth::logout(); return redirect()->route('admin.login'); }
}
EOT,
    'DashboardController' => <<<EOT
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

class DashboardController extends Controller {
    public function index() { return view('admin.dashboard.index'); }
}
EOT,
    'VerificationController' => <<<EOT
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VerificationController extends Controller {
    public function index() { return view('admin.verification.index'); }
    public function approve(Request \$request, \$product) { return back()->with('success', 'Disetujui'); }
    public function reject(Request \$request, \$product) { return back()->with('success', 'Ditolak'); }
}
EOT,
    'ReportController' => <<<EOT
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller {
    public function index() { return view('admin.reports.index'); }
    public function process(Request \$request, \$report) { return back()->with('success', 'Diproses'); }
    public function resolve(Request \$request, \$report) { return back()->with('success', 'Diselesaikan'); }
    public function dismiss(Request \$request, \$report) { return back()->with('success', 'Ditolak'); }
}
EOT,
    'UserController' => <<<EOT
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller {
    public function index() { return view('admin.users.index'); }
    public function show(\$user) { return view('admin.users.show'); }
    public function monitor(Request \$request, \$user) { return back()->with('success', 'Dimonitor'); }
    public function suspend(Request \$request, \$user) { return back()->with('success', 'Disuspend'); }
    public function activate(Request \$request, \$user) { return back()->with('success', 'Diaktifkan'); }
}
EOT,
    'StockController' => <<<EOT
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StockController extends Controller {
    public function index() { return view('admin.stock.index'); }
    public function notifyLowStock(Request \$request, \$product) { return back()->with('success', 'Notifikasi dikirim'); }
    public function adjust(Request \$request, \$product) { return back()->with('success', 'Stok disesuaikan'); }
}
EOT,
    'TransactionController' => <<<EOT
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransactionController extends Controller {
    public function index() { return view('admin.transactions.index'); }
    public function show(\$deal) { return view('admin.transactions.show'); }
    public function supportBuyer(Request \$request, \$deal) { return back()->with('success', 'Support buyer'); }
    public function supportSeller(Request \$request, \$deal) { return back()->with('success', 'Support seller'); }
}
EOT,
    'CategoryController' => <<<EOT
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller {
    public function index() { return view('admin.categories.index'); }
    public function store(Request \$request) { return back()->with('success', 'Tersimpan'); }
    public function update(Request \$request, \$category) { return back()->with('success', 'Diperbarui'); }
}
EOT,
    'CodPointController' => <<<EOT
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CodPointController extends Controller {
    public function index() { return view('admin.cod-points.index'); }
    public function store(Request \$request) { return back()->with('success', 'Tersimpan'); }
    public function update(Request \$request, \$point) { return back()->with('success', 'Diperbarui'); }
}
EOT,
    'AuditController' => <<<EOT
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

class AuditController extends Controller {
    public function index() { return view('admin.audit.index'); }
}
EOT,
    'SettingController' => <<<EOT
<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller {
    public function index() { return view('admin.settings.index'); }
    public function update(Request \$request) { return back()->with('success', 'Pengaturan diperbarui'); }
}
EOT
];

foreach (\$controllers as \$name => \$content) {
    file_put_contents("\$controllersDir/\$name.php", \$content);
}

// Views
\$viewsDir = 'c:\\laragon\\www\\silago-web\\resources\\views\\admin';
\$directories = [
    'layout', 'auth', 'dashboard', 'verification', 'reports', 'users', 'stock', 'transactions', 'categories', 'cod-points', 'audit', 'settings', 'errors'
];
foreach (\$directories as \$d) {
    @mkdir("\$viewsDir/\$d", 0755, true);
}

\$views = [
    'layout/app.blade.php' => <<<EOT
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - SILAGO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#15803d', deep: '#1b4d3e', soft: '#ebf5f0',
                        line: '#e8ebed', ink: '#111827', muted: '#4b5563',
                        faint: '#9ca3af', page: '#f9fafb'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-page text-ink flex h-screen overflow-hidden font-sans">
    <aside class="w-[240px] bg-white border-r border-line flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-line">
            <span class="text-xl font-bold text-brand">SILAGO</span>
            <span class="ml-2 text-xs font-semibold bg-soft text-brand px-2 py-0.5 rounded">ADMIN</span>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-soft text-ink">📊 Dashboard</a>
            <a href="{{ route('admin.reports.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-soft text-ink">
                ⚠️ Laporan <span class="ml-auto bg-red-600 text-white text-xs px-1.5 py-0.5 rounded-full">3</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-soft text-ink">👥 Pengguna</a>
            <a href="{{ route('admin.verification.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-soft text-ink">
                ✅ Verifikasi <span class="ml-auto bg-yellow-600 text-white text-xs px-1.5 py-0.5 rounded-full">5</span>
            </a>
            <a href="{{ route('admin.stock.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-soft text-ink">📦 Stok Barang</a>
            <a href="{{ route('admin.transactions.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-soft text-ink">💰 Transaksi & Sengketa</a>
            <a href="{{ route('admin.categories.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-soft text-ink">📁 Kategori</a>
            <a href="{{ route('admin.cod-points.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-soft text-ink">📍 Titik COD</a>
            <a href="{{ route('admin.audit.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-soft text-ink">📝 Audit Log</a>
            <a href="{{ route('admin.settings.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-soft text-ink">⚙️ Pengaturan</a>
        </nav>
        <div class="p-4 border-t border-line">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand hover:bg-deep focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand">
                    Keluar
                </button>
            </form>
        </div>
    </aside>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="h-16 bg-white border-b border-line flex items-center justify-between px-6">
            <div class="flex items-center bg-page border border-line rounded-md px-3 py-1.5 w-64">
                <span class="text-faint">🔍</span>
                <input type="text" placeholder="Cari..." class="ml-2 bg-transparent border-none outline-none text-sm w-full">
            </div>
            <div class="flex items-center space-x-4">
                <button class="text-muted hover:text-ink relative">🔔</button>
                <div class="w-8 h-8 rounded-full bg-brand text-white flex items-center justify-center font-bold text-sm">A</div>
            </div>
        </header>
        <main class="flex-1 overflow-y-auto bg-page p-6">
            @if(session('success'))
                <div class="mb-4 bg-soft text-brand p-4 rounded-md border border-brand/20">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-50 text-red-600 p-4 rounded-md border border-red-200">
                    {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
EOT,
    'auth/login.blade.php' => <<<EOT
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SILAGO</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex h-screen">
    <div class="hidden md:flex w-1/2 bg-green-700 items-center justify-center">
        <div class="text-white text-center">
            <h1 class="text-4xl font-bold mb-4">SILAGO Admin</h1>
            <p>Sistem Informasi Layanan Jual Beli</p>
        </div>
    </div>
    <div class="w-full md:w-1/2 flex items-center justify-center bg-white p-8">
        <div class="w-full max-w-md">
            <h2 class="text-2xl font-bold mb-6 text-gray-900 text-center">Masuk ke Panel Admin</h2>
            <form action="{{ route('admin.authenticate') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email / Username</label>
                    <input type="text" name="email" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500" required>
                </div>
                <button type="submit" class="w-full bg-green-700 text-white rounded-md py-2 font-medium hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Masuk</button>
            </form>
        </div>
    </div>
</body>
</html>
EOT,
    'dashboard/index.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Dashboard</h1>
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white p-6 rounded-xl border border-line shadow-sm">
        <h3 class="text-sm font-medium text-muted mb-1">Total Pengguna</h3>
        <p class="text-2xl font-bold text-ink">1,240</p>
    </div>
    <div class="bg-white p-6 rounded-xl border border-line shadow-sm">
        <h3 class="text-sm font-medium text-muted mb-1">Transaksi Berhasil</h3>
        <p class="text-2xl font-bold text-ink">854</p>
    </div>
    <div class="bg-white p-6 rounded-xl border border-line shadow-sm">
        <h3 class="text-sm font-medium text-muted mb-1">Laporan Pending</h3>
        <p class="text-2xl font-bold text-red-600">12</p>
    </div>
    <div class="bg-white p-6 rounded-xl border border-line shadow-sm">
        <h3 class="text-sm font-medium text-muted mb-1">Total Pendapatan</h3>
        <p class="text-2xl font-bold text-brand">Rp 12.500.000</p>
    </div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-line shadow-sm p-6">
        <h3 class="font-bold text-lg mb-4">Laporan Terbaru</h3>
        <div class="space-y-4">
            <div class="flex justify-between items-center p-3 border border-line rounded-lg">
                <div>
                    <p class="font-medium">Barang palsu</p>
                    <p class="text-xs text-muted">Pelapor: Budi</p>
                </div>
                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Diproses</span>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-line shadow-sm p-6">
        <h3 class="font-bold text-lg mb-4">Grafik Transaksi</h3>
        <div class="h-48 bg-page flex items-center justify-center text-muted">
            Grafik Chart.js
        </div>
    </div>
</div>
@endsection
EOT,
    'verification/index.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Verifikasi Produk</h1>
<div class="bg-white rounded-xl border border-line shadow-sm">
    <div class="p-4 border-b border-line">Tabel Verifikasi (Mockup)</div>
</div>
@endsection
EOT,
    'reports/index.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Laporan & Pengaduan</h1>
<div class="bg-white rounded-xl border border-line shadow-sm">
    <div class="p-4 border-b border-line">Tabel Laporan (Mockup)</div>
</div>
@endsection
EOT,
    'users/index.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Manajemen Pengguna</h1>
<div class="bg-white rounded-xl border border-line shadow-sm">
    <div class="p-4 border-b border-line">Tabel Pengguna (Mockup)</div>
</div>
@endsection
EOT,
    'users/show.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Detail Pengguna</h1>
<div class="bg-white rounded-xl border border-line shadow-sm p-6">
    Detail...
</div>
@endsection
EOT,
    'stock/index.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Pemantauan Stok</h1>
<div class="bg-white rounded-xl border border-line shadow-sm">
    <div class="p-4 border-b border-line">Tabel Stok (Mockup)</div>
</div>
@endsection
EOT,
    'transactions/index.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Transaksi & Sengketa</h1>
<div class="bg-white rounded-xl border border-line shadow-sm">
    <div class="p-4 border-b border-line">Tabel Transaksi (Mockup)</div>
</div>
@endsection
EOT,
    'transactions/show.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Detail Transaksi</h1>
<div class="bg-white rounded-xl border border-line shadow-sm p-6">
    Detail...
</div>
@endsection
EOT,
    'categories/index.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Kategori</h1>
<div class="bg-white rounded-xl border border-line shadow-sm">
    <div class="p-4 border-b border-line">Tabel Kategori (Mockup)</div>
</div>
@endsection
EOT,
    'cod-points/index.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Titik COD</h1>
<div class="bg-white rounded-xl border border-line shadow-sm">
    <div class="p-4 border-b border-line">Tabel Titik COD (Mockup)</div>
</div>
@endsection
EOT,
    'audit/index.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Audit Log</h1>
<div class="bg-white rounded-xl border border-line shadow-sm">
    <div class="p-4 border-b border-line">Tabel Audit (Mockup)</div>
</div>
@endsection
EOT,
    'settings/index.blade.php' => <<<EOT
@extends('admin.layout.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Pengaturan Sistem</h1>
<div class="bg-white rounded-xl border border-line shadow-sm p-6">
    Form Pengaturan...
</div>
@endsection
EOT,
];

foreach (\$views as \$path => \$content) {
    file_put_contents("\$viewsDir/\$path", \$content);
}

echo "Done generating stubs.\n";
