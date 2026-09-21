<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Sale;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportExportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SALES EXPORT
    |--------------------------------------------------------------------------
    */

    public function salesPdf(Request $request, string $tenant): Response
    {
        $tenantModel = $this->resolveTenant($tenant);

        $startDate = $request->query('startDate', now()->startOfDay()->format('Y-m-d'));
        $endDate = $request->query('endDate', now()->endOfDay()->format('Y-m-d'));
        $statusFilter = $request->query('statusFilter');

        $sales = $this->getSalesQuery($tenantModel->id, $startDate, $endDate, $statusFilter)
            ->with(['customer', 'user', 'table'])
            ->get();

        $pdf = Pdf::loadView('reports.sales-pdf', [
            'tenant' => $tenantModel,
            'dateRange' => 'custom',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sales' => $sales,
            'stats' => [
                'totalRevenue' => $sales->sum('grand_total'),
                'totalProfit' => 0,
                'profitMargin' => 0,
                'totalSales' => $sales->count(),
                'averageTransaction' => $sales->count() > 0 ? $sales->sum('grand_total') / $sales->count() : 0,
                'topProducts' => collect(),
                'salesByPayment' => [],
            ],
        ]);

        $pdf->setPaper('A4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-penjualan-'.$tenantModel->name.'-'.$startDate.'.pdf"',
        ]);
    }

    public function salesCsv(Request $request, string $tenant): Response
    {
        $tenantModel = $this->resolveTenant($tenant);

        $startDate = $request->query('startDate', now()->startOfDay()->format('Y-m-d'));
        $endDate = $request->query('endDate', now()->endOfDay()->format('Y-m-d'));
        $statusFilter = $request->query('statusFilter');

        $sales = $this->getSalesQuery($tenantModel->id, $startDate, $endDate, $statusFilter)
            ->with(['customer', 'user', 'table'])
            ->get();

        $filename = 'laporan-penjualan-'.$tenantModel->name.'-'.now()->format('Y-m-d-His').'.csv';

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'Invoice', 'Tanggal', 'Kasir', 'Customer', 'Meja',
            'Subtotal', 'Diskon', 'PPN', 'Total', 'Metode Bayar', 'Dibayar', 'Kembalian',
        ]);

        foreach ($sales as $sale) {
            fputcsv($handle, [
                (string) $sale->invoice_number,
                $sale->created_at->format('d/m/Y H:i'),
                $this->sanitizeUtf8($sale->user?->name ?? '-'),
                $this->sanitizeUtf8($sale->customer?->name ?? 'Walk-in'),
                $this->sanitizeUtf8($sale->table?->name ?? '-'),
                (string) number_format((float) $sale->subtotal, 0, ',', '.'),
                (string) number_format((float) $sale->discount, 0, ',', '.'),
                (string) number_format((float) $sale->tax, 0, ',', '.'),
                (string) number_format((float) $sale->grand_total, 0, ',', '.'),
                (string) $sale->payment_method,
                (string) number_format((float) $sale->paid_amount, 0, ',', '.'),
                (string) number_format((float) $sale->change_amount, 0, ',', '.'),
            ]);
        }

        return $this->csvResponse($handle, $filename);
    }

    /*
    |--------------------------------------------------------------------------
    | INGREDIENTS EXPORT
    |--------------------------------------------------------------------------
    */

    public function ingredientsCsv(Request $request, string $tenant): Response
    {
        $tenantModel = $this->resolveTenant($tenant);

        $searchIngredient = $request->query('searchIngredient', '');

        $query = Ingredient::query()
            ->with('supplier')
            ->where('tenant_id', $tenantModel->id)
            ->where('is_active', true);

        if (! empty($searchIngredient)) {
            $query->where('name', 'like', '%'.$searchIngredient.'%');
        }

        $ingredients = $query->orderBy('name')->get();

        $filename = 'laporan-bahan-baku-'.$tenantModel->name.'-'.now()->format('Y-m-d-His').'.csv';

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'Nama Bahan', 'SKU', 'Supplier', 'Satuan', 'Stok',
            'Stok Minimum', 'Harga Beli', 'Total Nilai Stok', 'Status',
        ]);

        foreach ($ingredients as $ingredient) {
            $status = match (true) {
                (float) $ingredient->stock <= 0 => 'Habis',
                $ingredient->minimum_stock && (float) $ingredient->stock <= (float) $ingredient->minimum_stock => 'Rendah',
                default => 'Normal',
            };

            fputcsv($handle, [
                $this->sanitizeUtf8($ingredient->name),
                $this->sanitizeUtf8($ingredient->sku ?? '-'),
                $this->sanitizeUtf8($ingredient->supplier?->name ?? '-'),
                $this->sanitizeUtf8($ingredient->unit),
                (string) number_format((int) $ingredient->stock, 0, ',', '.'),
                $ingredient->minimum_stock ? (string) number_format((int) $ingredient->minimum_stock, 0, ',', '.') : '-',
                (string) number_format((float) $ingredient->cost_price, 0, ',', '.'),
                (string) number_format((int) $ingredient->stock * (float) $ingredient->cost_price, 0, ',', '.'),
                $status,
            ]);
        }

        return $this->csvResponse($handle, $filename);
    }

    public function ingredientsUsageCsv(Request $request, string $tenant): Response
    {
        $tenantModel = $this->resolveTenant($tenant);

        $startDate = $request->query('startDate', now()->startOfDay()->format('Y-m-d'));
        $endDate = $request->query('endDate', now()->endOfDay()->format('Y-m-d'));

        // Get sales in date range
        $sales = Sale::query()
            ->where('tenant_id', $tenantModel->id)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->where('status', 'completed')
            ->pluck('id');

        if ($sales->isEmpty()) {
            $sales = collect([0]);
        }

        // Get sale items and products with ingredients
        $saleItems = SaleItem::whereIn('sale_id', $sales)->get();
        $soldProductIds = $saleItems->pluck('product_id')->filter()->unique();

        $usage = [];

        foreach ($soldProductIds as $productId) {
            $product = Product::with('ingredients')->find($productId);

            if (! $product || $product->ingredients->isEmpty()) {
                continue;
            }

            $totalQty = $saleItems->where('product_id', $productId)->sum('quantity');

            foreach ($product->ingredients as $ingredient) {
                $used = (float) $ingredient->pivot->quantity * $totalQty;
                $key = $ingredient->id;

                if (isset($usage[$key])) {
                    $usage[$key]['used'] += $used;
                    $usage[$key]['products'][] = $product->name;
                } else {
                    $usage[$key] = [
                        'name' => $ingredient->name,
                        'supplier' => $ingredient->supplier?->name,
                        'unit' => $ingredient->unit,
                        'stock' => (float) $ingredient->stock,
                        'used' => $used,
                        'products' => [$product->name],
                    ];
                }
            }
        }

        // Sort by usage (highest first)
        usort($usage, fn ($a, $b) => $b['used'] <=> $a['used']);

        $filename = 'laporan-penggunaan-bahan-'.$tenantModel->name.'-'.now()->format('Y-m-d-His').'.csv';

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'Nama Bahan', 'Supplier', 'Satuan', 'Digunakan', 'Sisa Stok', 'Produk',
        ]);

        foreach ($usage as $item) {
            fputcsv($handle, [
                $this->sanitizeUtf8($item['name']),
                $this->sanitizeUtf8($item['supplier'] ?? '-'),
                $this->sanitizeUtf8($item['unit']),
                (string) number_format((int) $item['used'], 0, ',', '.'),
                (string) number_format((int) $item['stock'], 0, ',', '.'),
                $this->sanitizeUtf8(implode(', ', array_unique($item['products']))),
            ]);
        }

        return $this->csvResponse($handle, $filename);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    protected function resolveTenant(string $tenant): Tenant
    {
        $tenantModel = Tenant::where('slug', $tenant)->orWhere('id', $tenant)->first();

        abort_unless($tenantModel, 404, 'Tenant not found');

        return $tenantModel;
    }

    protected function getSalesQuery(int $tenantId, string $startDate, string $endDate, ?string $statusFilter = null)
    {
        $query = Sale::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        } else {
            $query->where('status', 'completed');
        }

        return $query->orderByDesc('created_at');
    }

    protected function sanitizeUtf8(string $value): string
    {
        // Convert to UTF-8, replacing invalid sequences
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

        // Remove any remaining invalid characters
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        return $value;
    }

    protected function csvResponse($handle, string $filename): Response
    {
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        // Sanitize content to ensure valid UTF-8
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');

        // Clean filename
        $cleanFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$cleanFilename.'"',
            'Content-Length' => strlen($content),
        ]);
    }
}
