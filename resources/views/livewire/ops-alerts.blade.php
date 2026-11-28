@php
    $count = count($alerts);
    $chip = fn (string $level): string => match ($level) {
        'danger' => 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100',
        default => 'border-sky-200 bg-sky-50 text-sky-800 hover:bg-sky-100',
    };
@endphp
<div wire:poll.30s class="ops-strip flex min-w-0 flex-1 items-center gap-2 overflow-x-auto py-1" aria-label="Pemberitahuan operasional">
    @if ($tenant && $count)
        <x-heroicon-s-bell-alert class="ops-bell-ring h-5 w-5 shrink-0 text-red-500" />
        @foreach ($alerts as $alert)
            <a href="{{ $alert['url'] }}" class="shrink-0 rounded-full border px-3 py-1 text-xs font-semibold transition-colors {{ $chip($alert['level']) }}">{{ $alert['text'] }}</a>
        @endforeach
    @elseif ($tenant)
        <x-heroicon-o-bell class="h-5 w-5 shrink-0 text-emerald-500" />
        <span class="shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Semua aman</span>
        <span class="hidden truncate text-xs text-gray-500 lg:inline">{{ $status }}. Diperbarui {{ $updatedAt }}.</span>
    @endif
</div>
