<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    @include('partials.pwa-head', ['pwaStart' => request()->getRequestUri()])
    <title>{{ $title ?? 'Layar Dapur' }}</title>
    @vite('resources/css/filament/admin/theme.css')
    <style>
        @media (min-width: 1024px) { html { font-size: 20px; } }
        body { background-color: #030712; }
    </style>
</head>
<body class="min-h-screen text-gray-100">
    {{ $slot }}
    @stack('scripts')
</body>
</html>
