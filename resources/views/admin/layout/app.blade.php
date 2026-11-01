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
    $pendingReportsCount = \App\Models\Report::where('status', 'pending')->count();
    $pendingVerificationCount = \App\Models\Product::where('verification_status', 'pending')->count();
    $totalPendingNotif = $pendingReportsCount + $pendingVerificationCount;

    $menus = [
        ['label' => 'Dashboard',    'route' => 'admin.dashboard',          'active' => 'admin.dashboard',      'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        ['label' => 'Laporan',      'route' => 'admin.reports.index',      'active' => 'admin.reports.*',      'badge' => $pendingReportsCount,                              'badgeClass' => 'bg-red-100 text-red-700',   'icon' => 'M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-3-2-2 2-2-2-2 2-2-2-3 2V6a2 2 0 012-2z'],
        ['label' => 'Pengguna',     'route' => 'admin.users.index',        'active' => 'admin.users.*',        'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z'],
        ['label' => 'Verifikasi',   'route' => 'admin.verification.index', 'active' => 'admin.verification.*', 'badge' => $pendingVerificationCount,                         'badgeClass' => 'bg-yellow-100 text-yellow-700', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Barang',       'route' => 'admin.stock.index',        'active' => 'admin.stock.*',        'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['label' => 'Transaksi',    'route' => 'admin.transactions.index', 'active' => 'admin.transactions.*', 'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M3 10h18M7 15h1m4 0h1M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z'],
        ['label' => 'Kategori',     'route' => 'admin.categories.index',   'active' => 'admin.categories.*',   'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M7 7h.01M7 3h5a2 2 0 011.41.59l7 7a2 2 0 010 2.82l-5 5a2 2 0 01-2.82 0l-7-7A2 2 0 013 8V5a2 2 0 012-2z'],
        ['label' => 'Audit Log',    'route' => 'admin.audit.index',        'active' => 'admin.audit.*',        'badge' => null,                                              'badgeClass' => '',                          'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    ];

    if (auth()->check() && auth()->user()->role === 'admin') {
        $menus[] = ['label' => 'Pengaturan', 'route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'badge' => null, 'badgeClass' => '', 'icon' => 'M10.325 4.317a1.724 1.724 0 013.35 0 1.724 1.724 0 002.573 1.066 1.724 1.724 0 012.37 2.37 1.724 1.724 0 001.065 2.572 1.724 1.724 0 010 3.35 1.724 1.724 0 00-1.066 2.573 1.724 1.724 0 01-2.37 2.37 1.724 1.724 0 00-2.572 1.065 1.724 1.724 0 01-3.35 0 1.724 1.724 0 00-2.573-1.066 1.724 1.724 0 01-2.37-2.37 1.724 1.724 0 00-1.065-2.572 1.724 1.724 0 010-3.35 1.724 1.724 0 001.066-2.573 1.724 1.724 0 012.37-2.37 1.724 1.724 0 002.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'];
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
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-soft text-sm font-semibold text-brand overflow-hidden border border-line">
                    @if(auth()->check() && (auth()->user()->profile_photo || auth()->user()->avatar))
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ $userName }}" class="h-full w-full object-cover">
                    @else
                        {{ strtoupper(mb_substr($userName, 0, 1)) }}
                    @endif
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
            <div class="ml-auto flex items-center gap-3">
                <!-- Notification Bell & Popup Dropdown -->
                <div x-data="{ notifOpen: false }" @click.outside="notifOpen = false" class="relative">
                    <button @click="notifOpen = !notifOpen"
                            class="relative rounded-full p-2 text-muted hover:bg-gray-100 hover:text-ink transition-colors"
                            aria-label="Notifikasi">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if($totalPendingNotif > 0)
                            <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white ring-2 ring-white">
                                {{ $totalPendingNotif > 9 ? '9+' : $totalPendingNotif }}
                            </span>
                        @endif
                    </button>

                    <!-- Popup Dropdown -->
                    <div x-show="notifOpen" x-cloak x-transition
                         class="absolute right-0 mt-2 w-80 rounded-xl border border-line bg-white shadow-xl z-50 overflow-hidden">
                        <div class="border-b border-line px-4 py-3 bg-gray-50 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-ink">Notifikasi Admin</h3>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $totalPendingNotif > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-muted' }}">
                                {{ $totalPendingNotif }} Perlu Tindakan
                            </span>
                        </div>
                        <div class="divide-y divide-line max-h-80 overflow-y-auto">
                            @if($pendingVerificationCount > 0)
                                <a href="{{ route('admin.verification.index', ['status' => 'pending']) }}"
                                   class="flex items-start gap-3 p-3 hover:bg-soft transition-colors">
                                    <div class="h-8 w-8 rounded-full bg-yellow-100 text-yellow-700 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-ink">Verifikasi Barang Baru</p>
                                        <p class="text-xs text-muted mt-0.5">Ada <strong class="text-yellow-700">{{ $pendingVerificationCount }}</strong> barang menunggu verifikasi.</p>
                                    </div>
                                </a>
                            @endif

                            @if($pendingReportsCount > 0)
                                <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
                                   class="flex items-start gap-3 p-3 hover:bg-red-50 transition-colors">
                                    <div class="h-8 w-8 rounded-full bg-red-100 text-red-700 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-ink">Laporan Pelanggaran</p>
                                        <p class="text-xs text-muted mt-0.5">Ada <strong class="text-red-700">{{ $pendingReportsCount }}</strong> laporan perlu ditindaklanjuti.</p>
                                    </div>
                                </a>
                            @endif

                            @if($totalPendingNotif === 0)
                                <div class="p-6 text-center text-xs text-muted">
                                    <svg class="h-8 w-8 text-faint mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Tidak ada notifikasi baru saat ini.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="hidden items-center gap-3 sm:flex">
                    <span class="text-sm font-medium text-muted">{{ $userName }}</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-soft text-xs font-semibold text-brand overflow-hidden border border-line">
                        @if(auth()->check() && (auth()->user()->profile_photo || auth()->user()->avatar))
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ $userName }}" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(mb_substr($userName, 0, 1)) }}
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <!-- Floating Toast Popup Notification for Web -->
        <div class="fixed top-4 right-4 z-50 space-y-3 pointer-events-none sm:top-6 sm:right-6">
            @if(session('success'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
                     x-transition:enter="transform ease-out duration-300 transition"
                     x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                     x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-xl border border-green-200 bg-white p-4 shadow-xl ring-1 ring-black/5" role="alert">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900">Berhasil</p>
                        <p class="text-xs text-gray-600 mt-0.5">{{ session('success') }}</p>
                    </div>
                    <button @click="show = false" class="shrink-0 rounded-lg p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show"
                     x-transition:enter="transform ease-out duration-300 transition"
                     x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                     x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-xl border border-red-200 bg-white p-4 shadow-xl ring-1 ring-black/5" role="alert">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900">Perhatian</p>
                        <p class="text-xs text-gray-600 mt-0.5">{{ session('error') }}</p>
                    </div>
                    <button @click="show = false" class="shrink-0 rounded-lg p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif
        </div>

        <main class="flex-1 px-4 py-4 lg:px-8 lg:py-6">
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
