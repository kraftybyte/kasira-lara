@php
    $count = count($alerts);
    $top = $alerts[0] ?? null;
    $pill = match ($top['level'] ?? 'ok') {
        'danger' => 'border-red-300 bg-red-50 text-red-700',
        'warning' => 'border-amber-300 bg-amber-50 text-amber-800',
        'info' => 'border-sky-300 bg-sky-50 text-sky-800',
        default => 'border-emerald-300 bg-emerald-50 text-emerald-800',
    };
    $dot = fn (string $level): string => match ($level) {
        'danger' => 'bg-red-500',
        'warning' => 'bg-amber-500',
        default => 'bg-sky-500',
    };
@endphp
<div wire:poll.30s class="relative me-2" x-data="{ open: false }" @click.outside="open = false">
    @if ($tenant)
        <button type="button" @click="open = ! open" class="flex max-w-[16rem] items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold lg:max-w-sm {{ $pill }}" aria-label="Pemberitahuan operasional">
            @if ($count)
                <x-heroicon-s-bell-alert class="ops-bell-ring h-4 w-4 shrink-0" />
            @else
                <x-heroicon-o-bell class="h-4 w-4 shrink-0" />
            @endif
            <span class="hidden truncate md:inline">{{ $top['text'] ?? 'Semua aman' }}</span>
            @if ($count > 1)
                <span class="shrink-0 rounded-full bg-white/80 px-1.5 text-[10px] font-bold">{{ $count }}</span>
            @endif
        </button>
        <div x-show="open" x-cloak class="absolute end-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white text-left shadow-lg sm:w-96">
            <div class="border-b border-gray-100 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Pemberitahuan</div>
            @forelse ($alerts as $alert)
                <a href="{{ $alert['url'] }}" class="flex items-start gap-3 border-b border-gray-100 px-4 py-3 text-sm text-gray-800 hover:bg-gray-50">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $dot($alert['level']) }}"></span>
                    <span>{{ $alert['text'] }}</span>
                </a>
            @empty
                <div class="px-4 py-3 text-sm">
                    <p class="font-semibold text-emerald-700">Semua aman</p>
                    <p class="mt-1 text-gray-600">{{ $status }}</p>
                </div>
            @endforelse
            <div class="px-4 py-2 text-[11px] text-gray-400">Diperbarui {{ $updatedAt }}, otomatis tiap 30 detik</div>
        </div>
    @endif
</div>
