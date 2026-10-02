<?php

use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\PaywuzWebhookController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\OrderDetailsModal;
use App\Models\Table;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login', 301)->name('home');

/*
|--------------------------------------------------------------------------
| Customer QR Order
|--------------------------------------------------------------------------
|
| Customer-facing order page accessed via QR code scan on table.
|
*/

Route::get('/order/{tenant}/{table}', [CustomerOrderController::class, 'show'])
    ->name('customer.order');

Route::get('/order/{tenant}/{table}/checkout', function ($tenant, $table) {
    return redirect()->route('customer.order', ['tenant' => $tenant, 'table' => $table]);
})->name('customer.order.checkout.get');

Route::post('/order/{tenant}/{table}/checkout', [CustomerOrderController::class, 'checkout'])
    ->name('customer.order.checkout');

Route::get('/order/{tenant}/{table}/{sale}/payment', [CustomerOrderController::class, 'payment'])
    ->name('customer.order.payment');

Route::post('/order/{tenant}/{table}/{sale}/confirm', [CustomerOrderController::class, 'paymentConfirm'])
    ->name('customer.order.payment.confirm');

Route::get('/order/{tenant}/{table}/{sale}/success', [CustomerOrderController::class, 'success'])
    ->name('customer.order.success');

/*
|--------------------------------------------------------------------------
| POS Receipt
|--------------------------------------------------------------------------
|
| Standalone receipt.
| Tidak menggunakan layout Filament.
|
*/

Route::middleware(['auth'])
    ->get(
        '/admin/{tenant}/receipt/{sale}',
        [ReceiptController::class, 'show']
    )
    ->name('receipt.show');

/*
|--------------------------------------------------------------------------
| Paywuz Webhook
|--------------------------------------------------------------------------
|
| Endpoint untuk menerima notifikasi pembayaran dari paywuz.id
|
*/

Route::post('/webhook/paywuz', [PaywuzWebhookController::class, 'handle'])
    ->name('webhook.paywuz');

/*
|--------------------------------------------------------------------------
| Report Exports
|--------------------------------------------------------------------------
|
| Dedicated routes for exporting reports as files.
|
*/

Route::get('/admin/{tenant}/reports/sales/pdf', [ReportExportController::class, 'salesPdf'])
    ->name('reports.sales.pdf');

Route::get('/admin/{tenant}/reports/sales/csv', [ReportExportController::class, 'salesCsv'])
    ->name('reports.sales.csv');

Route::get('/admin/{tenant}/reports/ingredients/csv', [ReportExportController::class, 'ingredientsCsv'])
    ->name('reports.ingredients.csv');

Route::get('/admin/{tenant}/reports/ingredients/usage/csv', [ReportExportController::class, 'ingredientsUsageCsv'])
    ->name('reports.ingredients.usage.csv');

Route::get('/test-modal', function () {
    $tableId = (int) request('table', 1);
    $tableName = 'Meja '.$tableId;

    $modal = app(OrderDetailsModal::class);
    $modal->selectedTableId = $tableId;
    $modal->selectedTableName = $tableName;

    $table = Table::find($tableId);
    $modal->selectedTenantId = $table?->tenant_id;
    $modal->selectedTenantSlug = $table?->tenant?->slug;

    // Load orders
    $modal->loadOrders();
    $modal->isOpen = true;

    // Debug output
    $debug = "tableId={$modal->selectedTableId}, orders=".count($modal->orders);
    if (! empty($modal->orders)) {
        $debug .= ', first_keys='.implode(',', array_keys($modal->orders[0] ?? []));
    }

    return "<pre>DEBUG: {$debug}\n\n".$modal->render()->toHtml();
});

/*
|--------------------------------------------------------------------------
| Debug Routes - Hapus setelah debugging selesai
|--------------------------------------------------------------------------
*/

Route::get('/debug/session', function () {
    $data = [
        'session_id' => session()->getId(),
        'session_started' => session()->isStarted(),
        'has_user' => auth()->check(),
        'user' => auth()->user()?->email,
        'path' => request()->path(),
        'app_env' => config('app.env'),
        'secure_cookie' => config('session.secure'),
        'session_domain' => config('session.domain'),
        'session_driver' => config('session.driver'),
    ];

    return response()->json($data);
});

Route::get('/debug/routes', function () {
    $routes = [];
    foreach (Route::getRoutes() as $route) {
        if (str_starts_with($route->uri(), 'admin')) {
            $routes[] = [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'middleware' => $route->middleware(),
            ];
        }
    }

    return response()->json($routes);
});
