<?php

use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\PaywuzWebhookController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportExportController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

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
