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
    // Pasang ke layar utama: ikon di Dashboard selalu tampil selama belum terpasang. Android/Chrome memakai
    // dialog instal bawaan; iOS tidak punya API-nya, jadi diberi petunjuk Bagikan. Ajakan otomatis sekali per perangkat.
    (() => {
        const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
        const ios = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const hint = ios
            ? 'Di Safari: tekan tombol Bagikan, lalu pilih "Tambahkan ke Layar Utama".'
            : 'Buka menu browser, lalu pilih "Instal aplikasi" atau "Tambahkan ke layar utama".';
        window.kasiraInstallPrompt = null;
        if (! standalone) document.documentElement.classList.add('kasira-can-install');

        const notify = (title, body, withButton) => {
            if (! window.FilamentNotification) return;
            const n = new FilamentNotification().title(title).body(body).info().persistent();
            if (withButton && window.FilamentNotificationAction) {
                n.actions([new FilamentNotificationAction('pasang').label('Pasang sekarang').button().url('javascript:window.kasiraInstall()')]);
            }
            n.send();
        };
        const nudgeOnce = (withButton) => {
            try {
                if (localStorage.getItem('kasira.install.nudged')) return;
                localStorage.setItem('kasira.install.nudged', '1');
            } catch (e) {}
            notify('Pasang {{ $pwaName }} di layar utama', withButton ? 'Buka seperti aplikasi, tanpa bilah alamat.' : hint, withButton);
        };

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            window.kasiraInstallPrompt = e;
            nudgeOnce(true);
        });
        if (! standalone && ios) window.addEventListener('load', () => setTimeout(() => nudgeOnce(false), 1500));

        window.kasiraInstall = async () => {
            if (window.kasiraInstallPrompt) {
                window.kasiraInstallPrompt.prompt();
                const choice = await window.kasiraInstallPrompt.userChoice;
                window.kasiraInstallPrompt = null;
                if (choice && choice.outcome === 'accepted') document.documentElement.classList.remove('kasira-can-install');
                return;
            }
            window.FilamentNotification ? notify('Pasang {{ $pwaName }}', hint, false) : alert(hint);
        };
    })();
</script>
