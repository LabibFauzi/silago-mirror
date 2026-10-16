<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - SILAGO</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
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
    <style>[x-cloak]{display:none !important;}</style>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('styles')
</head>

@php
    $menus = [
        ['label' => 'Dashboard',    'route' => 'admin.dashboard',          'active' => 'admin.dashboard',      'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        ['label' => 'Laporan',      'route' => 'admin.reports.index',      'active' => 'admin.reports.*',      'badge' => $pendingReportsCount ?? 0,                         'badgeClass' => 'bg-red-100 text-red-700',   'icon' => 'M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-3-2-2 2-2-2-2 2-2-2-3 2V6a2 2 0 012-2z'],
        ['label' => 'Pengguna',     'route' => 'admin.users.index',        'active' => 'admin.users.*',        'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z'],
        ['label' => 'Verifikasi',   'route' => 'admin.verification.index', 'active' => 'admin.verification.*', 'badge' => $pendingVerificationCount ?? 0,                    'badgeClass' => 'bg-yellow-100 text-yellow-700', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Stok Barang',  'route' => 'admin.stock.index',        'active' => 'admin.stock.*',        'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['label' => 'Transaksi',    'route' => 'admin.transactions.index', 'active' => 'admin.transactions.*', 'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M3 10h18M7 15h1m4 0h1M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z'],
        ['label' => 'Kategori',     'route' => 'admin.categories.index',   'active' => 'admin.categories.*',   'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M7 7h.01M7 3h5a2 2 0 011.41.59l7 7a2 2 0 010 2.82l-5 5a2 2 0 01-2.82 0l-7-7A2 2 0 013 8V5a2 2 0 012-2z'],
        ['label' => 'Audit Log',    'route' => 'admin.audit.index',        'active' => 'admin.audit.*',        'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    ];

    if (auth()->check() && auth()->user()->role === 'admin') {
        $menus[] = ['label' => 'Pengaturan', 'route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'badge' => null, 'badgeClass' => '', 'icon' => 'M10.325 4.317a1.724 1.724 0 013.35 0 1.724 1.724 0 002.573 1.066 1.724 1.724 0 012.37 2.37 1.724 1.724 0 001.065 2.572 1.724 1.724 0 010 3.35 1.724 1.724 0 00-1.066 2.573 1.724 1.724 0 01-2.37 2.37 1.724 1.724 0 00-2.572 1.065 1.724 1.724 0 01-3.35 0 1.724 1.724 0 00-2.573-1.066 1.724 1.724 0 01-2.37-2.37 1.724 1.724 0 00-1.065-2.572 1.724 1.724 0 010-3.35 1.724 1.724 0 001.066-2.573 1.724 1.724 0 012.37-2.37 1.724 1.724 0 002.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'];

        // Data Manager — hanya muncul saat maintenance mode aktif
        if (app(\App\Services\SettingService::class)->getBool('maintenance_mode', false)) {
            $menus[] = ['label' => 'Data Manager', 'route' => 'admin.data-manager.index', 'active' => 'admin.data-manager.*', 'badge' => 'MAINT', 'badgeClass' => 'bg-red-100 text-red-700', 'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4'];
        }
    }

    $userName = auth()->user()->name ?? 'Administrator';
    $userRole = auth()->user()->role ?? 'admin';
@endphp

<body class="bg-page text-ink font-sans antialiased">
<div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false" class="relative min-h-screen">

    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-30 flex w-[240px] flex-col border-r border-line bg-white transition-transform duration-300 ease-in-out"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        <div class="flex h-16 items-center border-b border-line px-6">
            <h1 class="text-xl font-bold tracking-wide text-brand">SILAGO</h1>
            <span class="ml-2 rounded-full border border-green-100 bg-soft px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-green-800">
                {{ $userRole }}
            </span>
            <button @click="sidebarOpen = false" class="ml-auto rounded p-1 text-muted hover:text-ink lg:hidden" aria-label="Tutup menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @foreach($menus as $m)
                @php $active = request()->routeIs($m['active']); @endphp
                <a href="{{ route($m['route']) }}"
                   class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                          {{ $active ? 'bg-soft text-brand' : 'text-muted hover:bg-gray-50 hover:text-ink' }}">
                    <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-brand' : 'text-faint' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/>
                    </svg>
                    <span>{{ $m['label'] }}</span>
                    @if($m['badge'])
                        <span class="ml-auto rounded-full px-2 py-0.5 text-xs font-medium {{ $m['badgeClass'] }}">{{ $m['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="border-t border-line p-4">
            <div class="mb-3 flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-soft text-sm font-semibold text-brand">
                    {{ strtoupper(mb_substr($userName, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-ink">{{ $userName }}</p>
                    <p class="text-xs capitalize text-muted">{{ $userRole }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50">
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <!-- Mobile overlay -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-20 bg-black/50 lg:hidden"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>

    <!-- Main -->
    <div class="flex min-h-screen flex-col lg:ml-[240px]">
        <header class="sticky top-0 z-10 flex h-16 items-center gap-2 border-b border-line bg-white px-4 lg:px-8">
            <button @click="sidebarOpen = true" class="-ml-2 shrink-0 rounded-md p-2 text-muted hover:bg-gray-100 hover:text-ink lg:hidden" aria-label="Buka menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h2 class="truncate text-base font-semibold text-ink sm:text-lg">@yield('title', 'Dashboard')</h2>
            <div class="ml-auto hidden items-center gap-3 sm:flex">
                <span class="text-sm text-muted">{{ $userName }}</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-soft text-xs font-semibold text-brand">
                    {{ strtoupper(mb_substr($userName, 0, 1)) }}
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-4 lg:px-8 lg:py-6">
            @foreach(['success' => 'green', 'error' => 'red'] as $type => $color)
                @if(session($type))
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                         class="mb-4 flex items-start justify-between rounded-lg border border-{{ $color }}-200 bg-{{ $color }}-50 px-4 py-3 text-sm text-{{ $color }}-700" role="alert">
                        <span>{{ session($type) }}</span>
                        <button @click="show = false" class="ml-4 text-{{ $color }}-500 hover:text-{{ $color }}-700" aria-label="Tutup">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif
            @endforeach

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
