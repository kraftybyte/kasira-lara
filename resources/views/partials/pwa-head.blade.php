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
    // Tombol "Pasang aplikasi" tampil hanya kalau belum terpasang: Android/desktop lewat prompt browser, iOS lewat petunjuk Bagikan.
    (() => {
        const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
        const ios = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        window.kasiraInstallPrompt = null;
        window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); window.kasiraInstallPrompt = e; document.documentElement.classList.add('kasira-can-install'); });
        if (! standalone && ios) document.documentElement.classList.add('kasira-can-install');
        window.kasiraInstall = async () => {
            if (window.kasiraInstallPrompt) {
                window.kasiraInstallPrompt.prompt();
                await window.kasiraInstallPrompt.userChoice;
                window.kasiraInstallPrompt = null;
                document.documentElement.classList.remove('kasira-can-install');
                return;
            }
            const body = ios ? 'Di Safari: tekan tombol Bagikan, lalu pilih "Tambahkan ke Layar Utama".' : 'Buka menu browser, lalu pilih "Instal aplikasi" atau "Tambahkan ke layar utama".';
            window.FilamentNotification ? new FilamentNotification().title('Pasang aplikasi').body(body).info().send() : alert(body);
        };
    })();
</script>
