<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ?? 'Layar Dapur' }}</title>
    @vite('resources/css/filament/admin/theme.css')
    <style>
        html { font-size: 20px; }
        body { background-color: #030712; }
    </style>
</head>
<body class="min-h-screen text-gray-100">
    {{ $slot }}
    @stack('scripts')
</body>
</html>
