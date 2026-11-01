@extends('admin.layout.app')
@section('title', 'Laporan')

@section('content')
<div class="space-y-6" x-data="{ showModal: false, selectedReport: null }">

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Laporan</h1>
        <p class="mt-1 text-sm text-muted">Tinjau dan tindak lanjuti laporan pelanggaran pengguna</p>
    </div>

    <div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
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
                        <td class="px-6 py-4 text-xs text-muted">{{ $report->created_at ? $report->created_at->format('d M Y, H:i') : '-' }}</td>
                        <td class="px-6 py-4">
                            @if($report->status === 'pending')
                                <span class="inline-block rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">Pending</span>
                            @elseif($report->status === 'resolved')
                                <span class="inline-block rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Selesai</span>
                            @elseif($report->status === 'dismissed')
                                <span class="inline-block rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Ditolak</span>
                            @else
                                <span class="inline-block rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">{{ $report->status ?? '-' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="selectedReport = {{ json_encode($report) }}; showModal = true" class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-green-700/20 transition hover:bg-green-700 hover:shadow-md active:scale-95"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Tinjau</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-muted">Tidak ada laporan</td>
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
    </div>

    <!-- Modal Tinjau Laporan -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 bg-black/60 transition-opacity"></div>

            <div x-show="showModal" x-transition
                 class="relative z-10 flex max-h-[92vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">

                {{-- Header --}}
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-line px-6 py-4">
                    <div class="min-w-0">
                        <div class="mb-1.5 flex flex-wrap items-center gap-2">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-faint">Tinjau Laporan</span>
                            <template x-if="selectedReport?.status === 'pending'">
                                <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">Pending</span>
                            </template>
                            <template x-if="selectedReport?.status === 'resolved'">
                                <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Selesai</span>
                            </template>
                            <template x-if="selectedReport?.status === 'dismissed'">
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">Ditolak</span>
                            </template>
                        </div>
                        <h3 class="text-lg font-bold text-ink">
                            #L-<span x-text="String(selectedReport?.id || 0).padStart(4, '0')"></span>
                        </h3>
                        <p class="text-xs text-muted" x-text="selectedReport?.created_at ? new Date(selectedReport.created_at).toLocaleString('id-ID') : ''"></p>
                    </div>
                    <button @click="showModal = false" class="rounded-lg p-1.5 text-faint transition-colors hover:bg-gray-100 hover:text-ink">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5 text-sm">
                    <div class="grid grid-cols-2 gap-3 rounded-xl border border-line bg-page/60 p-4">
                        <div>
                            <span class="block text-xs text-muted">Pelapor</span>
                            <span class="font-medium text-ink" x-text="selectedReport?.reporter?.name || '-'"></span>
                        </div>
                        <div>
                            <span class="block text-xs text-muted">Target</span>
                            <span class="font-medium text-ink" x-text="selectedReport?.product?.name || selectedReport?.reportedUser?.name || '-'"></span>
                        </div>
                        <div class="col-span-2">
                            <span class="block text-xs text-muted">Alasan Laporan</span>
                            <span class="font-medium text-ink" x-text="selectedReport?.reason || '-'"></span>
                        </div>
                    </div>

                    <div>
                        <span class="block text-xs text-muted">Deskripsi Kendala</span>
                        <p class="mt-1 whitespace-pre-line rounded-lg border border-line bg-white p-3 text-ink" x-text="selectedReport?.description || '-'"></p>
                    </div>

                    <template x-if="selectedReport?.admin_note">
                        <div>
                            <span class="block text-xs text-muted">Catatan Admin</span>
                            <p class="mt-1 whitespace-pre-line rounded-lg border border-line bg-white p-3 text-ink" x-text="selectedReport.admin_note"></p>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="shrink-0 border-t border-line bg-gray-50/80 px-6 py-4">
                    <template x-if="selectedReport?.status === 'pending'">
                        <div class="space-y-3">
                            <form method="POST" x-bind:action="`{{ url('admin/reports') }}/${selectedReport?.id}/resolve`" class="space-y-2">
                                @csrf
                                <label class="block text-xs font-semibold text-ink">Catatan Admin (Opsional)</label>
                                <textarea name="admin_note" rows="2" class="w-full rounded-lg border border-line p-2 text-sm" placeholder="Catatan penyelesaian..."></textarea>
                                <button type="submit" class="w-full rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700">Selesaikan Laporan</button>
                            </form>

                            <form method="POST" x-bind:action="`{{ url('admin/reports') }}/${selectedReport?.id}/dismiss`" class="space-y-2">
                                @csrf
                                <label class="block text-xs font-semibold text-ink">Alasan Penolakan</label>
                                <textarea name="admin_note" rows="2" class="w-full rounded-lg border border-line p-2 text-sm" placeholder="Alasan laporan ditolak..." required></textarea>
                                <button type="submit" class="w-full rounded-lg border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100">Tolak Laporan</button>
                            </form>
                        </div>
                    </template>
                    <template x-if="['resolved', 'dismissed'].includes(selectedReport?.status)">
                        <div class="flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium"
                             :class="selectedReport?.status === 'resolved' ? 'bg-green-50 text-green-800' : 'bg-gray-100 text-gray-700'">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Laporan ini sudah ditutup dengan status
                            <span class="font-bold" x-text="selectedReport?.status === 'resolved' ? 'Selesai' : 'Ditolak'"></span>.
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
