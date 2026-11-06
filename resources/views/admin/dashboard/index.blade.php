@extends('admin.layout.app')
@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Page header --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink">Dashboard</h1>
            <p class="mt-1 text-sm text-muted">Ringkasan aktivitas dan moderasi SILAGO</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(($pendingVerifications ?? 0) > 0)
                <a href="{{ route('admin.verification.index', ['status' => 'pending']) }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs font-semibold text-yellow-800 hover:bg-yellow-100">
                    <span class="h-2 w-2 rounded-full bg-yellow-500"></span>
                    {{ $pendingVerifications }} menunggu verifikasi
                </a>
            @endif
            @if(($pendingReports ?? 0) > 0)
                <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                    <span class="h-2 w-2 rounded-full bg-red-500"></span>
                    {{ $pendingReports }} laporan perlu tindakan
                </a>
            @endif
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-line bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-muted">Total Pengguna</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-soft text-brand">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-ink">{{ number_format($totalUsers ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-muted">Akun mahasiswa &amp; umum terdaftar</p>
        </div>

        <div class="rounded-xl border border-line bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-muted">Barang Aktif</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-soft text-brand">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-ink">{{ number_format($activeProducts ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-muted">Tersedia &amp; sudah terverifikasi</p>
        </div>

        <div class="rounded-xl border border-line bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-muted">Transaksi Hari Ini</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-soft text-brand">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-ink">{{ number_format($todayDeals ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-muted">Transaksi baru dibuat hari ini</p>
        </div>

        <div class="rounded-xl border border-line bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-muted">Laporan Belum Ditangani</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-ink">{{ number_format($pendingReports ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs {{ ($pendingReports ?? 0) > 0 ? 'text-red-600' : 'text-muted' }}">Perlu ditinjau</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Recent reports --}}
        <div class="overflow-hidden rounded-xl border border-line bg-white shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between border-b border-line px-4 py-4 sm:px-6">
                <div>
                    <h3 class="text-base font-semibold text-ink">Laporan Terbaru</h3>
                    <p class="text-xs text-muted">Laporan pelanggaran yang belum ditangani</p>
                </div>
                <a href="{{ route('admin.reports.index') }}" class="text-sm font-semibold text-green-700 hover:text-green-800">Lihat semua</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-line bg-page text-xs uppercase text-muted">
                        <tr>
                            <th class="px-6 py-3">Pelapor</th>
                            <th class="px-6 py-3">Target</th>
                            <th class="px-6 py-3">Alasan</th>
                            <th class="px-6 py-3">Waktu</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentReports ?? [] as $report)
                            <tr class="border-b border-line hover:bg-page">
                                <td class="px-6 py-3 font-medium text-ink">{{ $report->reporter->name ?? 'User' }}</td>
                                <td class="px-6 py-3 text-ink">{{ $report->product->name ?? ($report->reportedUser->name ?? '-') }}</td>
                                <td class="max-w-xs truncate px-6 py-3 text-muted">{{ $report->reason ?? '-' }}</td>
                                <td class="px-6 py-3 text-xs text-muted">{{ $report->created_at ? $report->created_at->diffForHumans() : '-' }}</td>
                                <td class="px-6 py-3">
                                    @if($report->status === 'pending')
                                        <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">Pending</span>
                                    @elseif($report->status === 'resolved')
                                        <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Selesai</span>
                                    @elseif($report->status === 'dismissed')
                                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Ditolak</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">{{ $report->status ?? '-' }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('admin.reports.index', ['q' => $report->id]) }}" class="text-sm font-semibold text-green-700 hover:text-green-800">Tinjau</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-muted">Belum ada laporan yang perlu ditangani.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($recentReports) && method_exists($recentReports, 'links'))
                <div class="border-t border-line px-6 py-4">
                    {{ $recentReports->links() }}
                </div>
            @endif
        </div>

        {{-- Chart --}}
        <div class="rounded-xl border border-line bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-ink">Transaksi 7 Hari Terakhir</h3>
            <p class="mb-4 text-xs text-muted">Jumlah transaksi per hari</p>
            <canvas id="transactionChart" height="220"></canvas>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('transactionChart');
        if (!el) return;
        const data = {!! json_encode($chartData ?? ['labels' => [], 'data' => []]) !!};

        new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.labels.length ? data.labels : ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: data.data.length ? data.data : [0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: '#15803d',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e8ebed' } },
                    x: { grid: { display: false } }
                }
            }
        });
    });
</script>
@endsection
