@extends('admin.layout.app')
@section('title', 'Data Manager')

@section('content')
<div class="space-y-6">
    {{-- Banner peringatan --}}
    <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4">
        <svg class="h-6 w-6 shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <h3 class="font-semibold text-red-800">Perhatian — Mode Maintenance Aktif</h3>
            <p class="mt-1 text-sm text-red-700">Data Manager memberi akses CRUD langsung ke semua tabel database. Semua perubahan dicatat di Audit Log. Gunakan dengan hati-hati.</p>
        </div>
    </div>

    {{-- Grid tabel --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach($tables as $t)
        <a href="{{ route('admin.data-manager.table', $t['slug']) }}"
           class="group flex items-center gap-4 rounded-xl border border-line bg-white p-5 shadow-sm transition-all hover:border-brand hover:shadow-md">
            <span class="text-3xl">{{ $t['icon'] }}</span>
            <div class="min-w-0 flex-1">
                <h4 class="truncate font-semibold text-ink group-hover:text-brand">{{ $t['label'] }}</h4>
                <p class="text-xs text-muted">{{ $t['slug'] }}</p>
            </div>
            <span class="rounded-full bg-soft px-3 py-1 text-sm font-semibold text-brand">{{ number_format($t['count']) }}</span>
        </a>
        @endforeach
    </div>
</div>
@endsection
