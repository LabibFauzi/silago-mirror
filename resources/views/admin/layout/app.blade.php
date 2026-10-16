<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - SILAGO</title>
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#15803d',
                        deep: '#1b4d3e',
                        soft: '#ebf5f0',
                        line: '#e8ebed',
                        ink: '#111827',
                        muted: '#4b5563',
                        faint: '#9ca3af',
                        page: '#f9fafb'
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-page text-ink antialiased">
<div x-data="{ sidebarOpen: false }" class="relative min-h-screen">
    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 w-[240px] bg-white border-r border-line flex flex-col z-30 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out"
           :class="{ 'translate-x-0': sidebarOpen }">
        <div class="h-16 flex items-center px-6 border-b border-line">
            <h1 class="text-xl font-bold text-brand tracking-wide">SILAGO</h1>
            <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-gray-100 text-muted rounded">ADMIN</span>
            <!-- Close button mobile -->
            <button @click="sidebarOpen = false" class="lg:hidden ml-auto p-1 text-muted hover:text-ink rounded">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.dashboard') ? 'bg-soft text-brand' : 'text-muted hover:bg-gray-50' }}">
                Dashboard
            </a>

            <a href="{{ route('admin.reports.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.reports.*') ? 'bg-soft text-brand' : 'text-muted hover:bg-gray-50' }}">
                Laporan
                @if(isset($pendingReportsCount) && $pendingReportsCount > 0)
                <span class="ml-auto bg-red-100 text-red-700 py-0.5 px-2 rounded-full text-xs">{{ $pendingReportsCount }}</span>
                @endif
            </a>

            <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.users.*') ? 'bg-soft text-brand' : 'text-muted hover:bg-gray-50' }}">
                Pengguna
            </a>

            <a href="{{ route('admin.verification.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.verification.*') ? 'bg-soft text-brand' : 'text-muted hover:bg-gray-50' }}">
                Verifikasi
                @if(isset($pendingVerificationCount) && $pendingVerificationCount > 0)
                <span class="ml-auto bg-yellow-100 text-yellow-700 py-0.5 px-2 rounded-full text-xs">{{ $pendingVerificationCount }}</span>
                @endif
            </a>

            <a href="{{ route('admin.stock.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.stock.*') ? 'bg-soft text-brand' : 'text-muted hover:bg-gray-50' }}">
                Stok Barang
            </a>

            <a href="{{ route('admin.transactions.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.transactions.*') ? 'bg-soft text-brand' : 'text-muted hover:bg-gray-50' }}">
                Transaksi
            </a>

            <a href="{{ route('admin.categories.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.categories.*') ? 'bg-soft text-brand' : 'text-muted hover:bg-gray-50' }}">
                Kategori
            </a>

            <a href="{{ route('admin.audit.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.audit.*') ? 'bg-soft text-brand' : 'text-muted hover:bg-gray-50' }}">
                Audit Log
            </a>

            @if(auth()->check() && auth()->user()->role === 'admin')
            <a href="{{ route('admin.settings.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('admin.settings.*') ? 'bg-soft text-brand' : 'text-muted hover:bg-gray-50' }}">
                Pengaturan
            </a>
            @endif
        </nav>

        <div class="p-4 border-t border-line">
            <div class="flex flex-col mb-3">
                <span class="text-sm font-medium text-ink truncate">{{ auth()->user()->name ?? 'Administrator' }}</span>
                <span class="text-xs text-muted">{{ auth()->user()->role ?? 'admin' }}</span>
            </div>
            <form method="POST" action="{{ route('admin.logout') ?? '#' }}">
                @csrf
                <button type="submit" class="w-full text-left px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <!-- Mobile Overlay -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false"
         class="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
    </div>

    <!-- Main Content -->
    <div class="lg:ml-[240px] flex flex-col min-h-screen">
        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-line flex items-center px-4 lg:px-8 z-10 sticky top-0 gap-2">
            <!-- Mobile Menu Button -->
            <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 text-muted hover:text-ink rounded-md hover:bg-gray-100 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h2 class="text-base sm:text-lg font-semibold text-ink truncate">@yield('title', 'Dashboard')</h2>
        </header>

        <!-- Flash Messages -->
        <div class="px-4 lg:px-8 pt-4 lg:pt-6">
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                        <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                    </span>
                </div>
            @endif

            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                        <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                    </span>
                </div>
            @endif
        </div>

        <!-- Content -->
        <main class="flex-1 px-4 lg:px-8 pb-8 {{ session('success') || session('error') ? 'pt-2' : 'pt-4 lg:pt-6' }}">
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
