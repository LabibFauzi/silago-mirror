@extends('admin.layout.app')
@section('title', 'Audit Log')

@section('content')
<div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden" x-data="{ showModal: false, selectedLog: null }">
    <div class="border-b border-line px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium {{ (!request('category') || request('category') === 'all') ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Semua</a>
            <a href="?category=pengguna" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('category') === 'pengguna' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Pengguna</a>
            <a href="?category=barang" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('category') === 'barang' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Barang</a>
            <a href="?category=laporan" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('category') === 'laporan' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Laporan</a>
            <a href="?category=transaksi" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('category') === 'transaksi' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Transaksi</a>
            <a href="?category=pengaturan" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('category') === 'pengaturan' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Pengaturan</a>
            <a href="?category=sistem" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('category') === 'sistem' ? 'bg-brand text-white' : 'bg-page text-muted hover:bg-gray-100' }}">Sistem</a>
        </div>
        <form method="GET" action="" class="w-full sm:w-72">
            <input type="hidden" name="category" value="{{ request('category', '') }}">
            <input type="text" name="q" placeholder="Cari tindakan / admin..." class="w-full px-4 py-2 border border-line rounded-lg text-sm focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand" value="{{ request('q', '') }}">
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-muted uppercase bg-page border-b border-line">
                <tr>
                    <th class="px-6 py-4">Waktu</th>
                    <th class="px-6 py-4">Admin</th>
                    <th class="px-6 py-4">Tindakan</th>
                    <th class="px-6 py-4">Target</th>
                    <th class="px-6 py-4">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditLogs ?? [] as $log)
                <tr class="border-b border-line hover:bg-page cursor-pointer" @click="selectedLog = {{ json_encode($log) }}; showModal = true">
                    <td class="px-6 py-4 whitespace-nowrap text-muted">{{ $log->created_at ? $log->created_at->format('d M Y, H:i:s') : '-' }}</td>
                    <td class="px-6 py-4 font-medium text-ink">{{ $log->admin->name ?? 'System' }}</td>
                    <td class="px-6 py-4">
                        <span class="bg-gray-100 text-gray-700 py-1 px-2 rounded-md text-xs font-mono">{{ $log->action ?? '-' }}</span>
                    </td>
                    <td class="px-6 py-4">{{ $log->target ?? '-' }}</td>
                    <td class="px-6 py-4 truncate max-w-xs text-muted">{{ $log->note ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-muted">Tidak ada audit log</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if(isset($auditLogs) && method_exists($auditLogs, 'links'))
    <div class="px-6 py-4 border-t border-line">
        {{ $auditLogs->links() }}
    </div>
    @endif

    <!-- Modal Detail Log -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50"></div>
            
            <div x-show="showModal" class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-bold text-ink">Detail Audit Log</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="space-y-3 text-sm">
                    <div class="grid grid-cols-3 gap-2 border-b border-line pb-2">
                        <div class="text-muted">Waktu</div>
                        <div class="col-span-2 font-medium text-ink" x-text="selectedLog?.created_at ? new Date(selectedLog.created_at).toLocaleString('id-ID') : '-'"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 border-b border-line pb-2">
                        <div class="text-muted">Admin</div>
                        <div class="col-span-2 font-medium text-ink" x-text="selectedLog?.admin?.name || 'System'"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 border-b border-line pb-2">
                        <div class="text-muted">Tindakan</div>
                        <div class="col-span-2 font-mono text-xs bg-gray-100 p-1 rounded inline-block" x-text="selectedLog?.action || '-'"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 border-b border-line pb-2">
                        <div class="text-muted">Target</div>
                        <div class="col-span-2 font-medium text-ink" x-text="selectedLog?.target || '-'"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 border-b border-line pb-2">
                        <div class="text-muted">Kategori</div>
                        <div class="col-span-2 text-ink" x-text="selectedLog?.category || '-'"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="text-muted">Keterangan</div>
                        <div class="col-span-2 text-ink" x-text="selectedLog?.note || '-'"></div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-line" x-show="selectedLog?.meta">
                        <div class="text-muted mb-2">Meta / Perubahan:</div>
                        <pre class="bg-gray-800 text-green-400 p-3 rounded-lg text-xs overflow-x-auto whitespace-pre-wrap" x-text="selectedLog?.meta ? JSON.stringify(selectedLog.meta, null, 2) : ''"></pre>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button @click="showModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
