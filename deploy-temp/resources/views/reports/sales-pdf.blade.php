<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #ef4444; padding-bottom: 12px; }
        .header h1 { font-size: 18px; font-weight: 700; color: #ef4444; }
        .header p { font-size: 10px; color: #64748b; margin-top: 4px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 16px; }
        .stat-box { background: #f8fafc; border-radius: 6px; padding: 10px; border: 1px solid #e2e8f0; }
        .stat-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 4px; }
        .profit .stat-value { color: #10b981; }
        .profit { border-color: #10b981; background: #f0fdf4; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 8px; }
        thead { background: #f1f5f9; }
        th { padding: 6px 8px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 9px; color: #64748b; letter-spacing: 0.5px; }
        td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; }
        tr:nth-child(even) td { background: #fafafa; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 9999px; font-size: 9px; font-weight: 600; }
        .badge-cash { background: #dcfce7; color: #16a34a; }
        .badge-qris { background: #dbeafe; color: #2563eb; }
        .badge-transfer { background: #f3e8ff; color: #9333ea; }
        .badge-default { background: #fef3c7; color: #d97706; }
        .footer { margin-top: 16px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Penjualan</h1>
        <p>{{ $tenant->name }} &bull; {{ $startDate }} - {{ $endDate }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-label">Total Penjualan</div>
            <div class="stat-value">Rp {{ number_format($stats['totalRevenue'], 0, ',', '.') }}</div>
        </div>
        <div class="stat-box profit">
            <div class="stat-label">Profit</div>
            <div class="stat-value">Rp {{ number_format($stats['totalProfit'] ?? 0, 0, ',', '.') }} ({{ number_format($stats['profitMargin'] ?? 0, 1, ',', '.') }}%)</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Transaksi</div>
            <div class="stat-value">{{ number_format($stats['totalSales'], 0, ',', '.') }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Rata-rata</div>
            <div class="stat-value">Rp {{ number_format($stats['averageTransaction'] ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th>Customer</th>
                <th>Metode</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">PPN</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
                @php
                    $badgeClass = match($sale->payment_method) {
                        'cash' => 'badge-cash',
                        'qris' => 'badge-qris',
                        'transfer' => 'badge-transfer',
                        default => 'badge-default',
                    };
                    $label = match($sale->payment_method) {
                        'cash' => 'Tunai',
                        'qris' => 'QRIS',
                        'transfer' => 'Transfer',
                        default => ucfirst($sale->payment_method),
                    };
                @endphp
                <tr>
                    <td>{{ $sale->invoice_number }}</td>
                    <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $sale->user?->name ?? '-' }}</td>
                    <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $label }}</span></td>
                    <td class="text-right">Rp {{ number_format((float) $sale->subtotal, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format((float) $sale->tax, 0, ',', '.') }}</td>
                    <td class="text-right"><strong>Rp {{ number_format((float) $sale->grand_total, 0, ',', '.') }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak {{ now()->format('d/m/Y H:i') }} &bull; Sistem Kasira POS
    </div>
</body>
</html>
