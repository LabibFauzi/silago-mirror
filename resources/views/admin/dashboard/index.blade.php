@extends('admin.layout.app')
@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-muted">Total Pengguna</h3>
        </div>  
        <div class="text-3xl font-bold text-ink">{{ number_format($totalUsers ?? 0, 0, ',', '.') }}</div>
        <div class="text-xs text-green-600 mt-2">+32 minggu ini</div>
    </div>

    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-muted">Barang Aktif</h3>
        </div>
        <div class="text-3xl font-bold text-ink">{{ number_format($activeProducts ?? 0, 0, ',', '.') }}</div>
    </div>

    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-muted">Transaksi Hari Ini</h3>
        </div>
        <div class="text-3xl font-bold text-ink">{{ number_format($todayDeals ?? 0, 0, ',', '.') }}</div>
    </div>

    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-muted">Laporan Belum Ditangani</h3>
        </div>
        <div class="text-3xl font-bold text-ink">{{ number_format($pendingReports ?? 0, 0, ',', '.') }}</div>
        <div class="text-xs text-red-600 mt-2">Perlu ditinjau</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <h3 class="text-lg font-semibold text-ink mb-4">Laporan Terbaru</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-muted uppercase bg-page border-b border-line">
                    <tr>
                        <th class="px-4 py-3 rounded-tl-lg">Pelapor</th>
                        <th class="px-4 py-3">Target Barang</th>
                        <th class="px-4 py-3">Alasan</th>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 rounded-tr-lg">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentReports ?? [] as $report)
                    <tr class="border-b border-line hover:bg-page">
                        <td class="px-4 py-3 font-medium">{{ $report->reporter->name ?? 'User' }}</td>
                        <td class="px-4 py-3">{{ $report->product->name ?? ($report->reportedUser->name ?? '-') }}</td>
                        <td class="px-4 py-3 truncate max-w-xs">{{ $report->reason ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $report->created_at ? $report->created_at->diffForHumans() : '-' }}</td>
                        <td class="px-4 py-3">
                            @if($report->status === 'pending')
                                <span class="bg-red-100 text-red-700 py-1 px-2 rounded-md text-xs">Pending</span>
                            @elseif($report->status === 'resolved')
                                <span class="bg-green-100 text-green-700 py-1 px-2 rounded-md text-xs">Selesai</span>
                            @elseif($report->status === 'dismissed')
                                <span class="bg-gray-100 text-gray-600 py-1 px-2 rounded-md text-xs">Ditolak</span>
                            @else
                                <span class="bg-gray-100 text-gray-600 py-1 px-2 rounded-md text-xs">{{ $report->status ?? '-' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.reports.index') }}" class="text-brand hover:underline font-medium">Tinjau</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-muted">Belum ada laporan terbaru</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl p-6 border border-line shadow-sm">
        <h3 class="text-lg font-semibold text-ink mb-4">Transaksi 7 Hari Terakhir</h3>
        <canvas id="transactionChart" height="200"></canvas>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('transactionChart').getContext('2d');
        const data = {!! json_encode($chartData ?? ['labels' => [], 'data' => []]) !!};
        
        new Chart(ctx, {
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
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e8ebed' } },
                    x: { grid: { display: false } }
                }
            }
        });
    });
</script>
@endsection
