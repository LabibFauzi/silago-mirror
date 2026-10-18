@extends('admin.layout.app')
@section('title', 'Laporan')

@section('content')
<div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden" x-data="{ showModal: false, selectedReport: null }">
    <div class="border-b border-line px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium {{ (!request('status') || request('status') === 'all') ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Semua</a>
            <a href="?status=pending" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'pending' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Pending</a>
            <a href="?status=resolved" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'resolved' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Selesai</a>
            <a href="?status=dismissed" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'dismissed' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Ditolak</a>
        </div>
        <form method="GET" action="" class="w-full sm:w-72">
            <input type="hidden" name="status" value="{{ request('status', '') }}">
            <input type="text" name="q" placeholder="Cari ID / pelapor..." class="w-full px-4 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" value="{{ request('q', '') }}">
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-muted uppercase bg-page border-b border-line">
                <tr>
                    <th class="px-6 py-4">ID Laporan</th>
                    <th class="px-6 py-4">Pelapor</th>
                    <th class="px-6 py-4">Target Barang</th>
                    <th class="px-6 py-4">Alasan</th>
                    <th class="px-6 py-4">Waktu</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports ?? [] as $report)
                <tr class="border-b border-line hover:bg-page">
                    <td class="px-6 py-4 font-medium text-ink">#L-{{ str_pad($report->id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img src="{{ $report->reporter->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($report->reporter->name ?? 'User') }}" alt="Avatar" class="w-7 h-7 rounded-full object-cover mr-2 border border-line">
                            <span class="font-medium text-ink">{{ $report->reporter->name ?? 'User' }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">{{ $report->product->name ?? ($report->reportedUser->name ?? '-') }}</td>
                    <td class="px-6 py-4 truncate max-w-xs">{{ $report->reason ?? '-' }}</td>
                    <td class="px-6 py-4">{{ $report->created_at ? $report->created_at->format('d M Y, H:i') : '-' }}</td>
                    <td class="px-6 py-4">
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
                    <td class="px-6 py-4 text-right">
                        <button @click="selectedReport = {{ json_encode($report) }}; showModal = true" class="text-brand hover:underline font-medium">Tinjau</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-muted">Tidak ada laporan</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if(isset($reports) && method_exists($reports, 'links'))
    <div class="px-6 py-4 border-t border-line">
        {{ $reports->links() }}
    </div>
    @endif

    <!-- Modal Tinjau Laporan -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50"></div>
            
            <div x-show="showModal" class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-bold text-ink">Detail Laporan</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="bg-page p-4 rounded-lg mb-4 text-sm">
                    <div class="mb-2"><span class="text-muted">Pelapor:</span> <span class="font-medium text-ink" x-text="selectedReport?.reporter?.name || '-'"></span></div>
                    <div class="mb-2"><span class="text-muted">Target Barang:</span> <span class="font-medium text-ink" x-text="selectedReport?.product?.name || selectedReport?.reportedUser?.name || '-'"></span></div>
                    <div class="mb-2"><span class="text-muted">Alasan Laporan:</span> <span class="font-medium text-ink" x-text="selectedReport?.reason || '-'"></span></div>
                    <div class="mb-2"><span class="text-muted">Deskripsi Kendala:</span> <p class="mt-1 p-2 bg-white border border-line rounded text-ink" x-text="selectedReport?.description || '-'"></p></div>
                </div>

                <template x-if="selectedReport?.status === 'pending'">
                    <div class="space-y-3">
                        <!-- Selesaikan -->
                        <form method="POST" x-bind:action="`{{ url('admin/reports') }}/${selectedReport?.id}/resolve`">
                            @csrf
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-ink mb-1">Catatan Admin (Opsional)</label>
                                <textarea name="admin_note" rows="2" class="w-full p-2 border border-line rounded-lg text-sm"></textarea>
                            </div>
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">Selesaikan Laporan</button>
                        </form>

                        <!-- Tolak -->
                        <form method="POST" x-bind:action="`{{ url('admin/reports') }}/${selectedReport?.id}/dismiss`">
                            @csrf
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-ink mb-1">Alasan Penolakan</label>
                                <textarea name="admin_note" rows="2" class="w-full p-2 border border-line rounded-lg text-sm" placeholder="Alasan laporan ditolak..." required></textarea>
                            </div>
                            <button type="submit" class="w-full bg-red-100 hover:bg-red-200 text-red-700 font-medium py-2 px-4 rounded-lg border border-red-300">Tolak Laporan</button>
                        </form>
                    </div>
                </template>

                <template x-if="['resolved', 'dismissed'].includes(selectedReport?.status)">
                    <div class="text-center p-4 bg-gray-50 rounded-lg text-sm text-muted">
                        Laporan ini sudah ditutup dengan status <span x-text="selectedReport?.status === 'resolved' ? 'Selesai' : 'Ditolak'" class="font-bold"></span>.
                        <div class="mt-2 text-left" x-show="selectedReport?.admin_note">
                            <span class="text-xs text-muted block mb-1">Catatan Admin:</span>
                            <p class="p-2 bg-white border border-line rounded" x-text="selectedReport?.admin_note"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
