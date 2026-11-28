@php
    $pwaSetting = \App\Models\AppSetting::first();
    $pwaIcons = \App\Http\Controllers\PwaController::icons($pwaSetting);
    $pwaName = trim((string) ($pwaSetting?->app_name ?: 'Kasira'));
@endphp
<link rel="manifest" href="{{ route('pwa.manifest', isset($pwaStart) ? ['start' => $pwaStart] : []) }}">
<meta name="theme-color" content="#ef4444">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ $pwaName }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $pwaIcons[180] }}">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
</script>
