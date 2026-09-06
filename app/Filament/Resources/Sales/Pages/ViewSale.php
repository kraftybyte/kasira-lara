<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\Sales\Schemas\SaleInfolist;
use App\Services\SaleCancellationService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    public function infolist(Schema $schema): Schema
    {
        return SaleInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | CETAK STRUK
            |--------------------------------------------------------------------------
            */

            Action::make('printReceipt')
                ->label('Cetak Struk')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(function () {

                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return '#';
                    }

                    return url(
                        '/admin/'.
                        $tenant->getRouteKey().
                        '/receipt/'.
                        $this->record->getRouteKey()
                    );
                })
                ->openUrlInNewTab(),

            /*
            |--------------------------------------------------------------------------
            | BATALKAN TRANSAKSI
            |--------------------------------------------------------------------------
            */

            Action::make('cancelSale')
                ->label('Batalkan Transaksi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')

                /*
                |--------------------------------------------------------------------------
                | Hanya muncul jika transaksi belum dibatalkan
                |--------------------------------------------------------------------------
                */

                ->visible(
                    fn (): bool => $this->record->status !== 'cancelled'
                )

                /*
                |--------------------------------------------------------------------------
                | CONFIRMATION
                |--------------------------------------------------------------------------
                */

                ->requiresConfirmation()

                ->modalHeading('Batalkan Transaksi?')

                ->modalDescription(
                    fn (): string => 'Transaksi '.
                        $this->record->invoice_number.
                        ' akan dibatalkan dan stok produk akan dikembalikan.'
                )

                ->modalSubmitActionLabel(
                    'Ya, Batalkan Transaksi'
                )

                ->modalCancelActionLabel(
                    'Jangan Batalkan'
                )

                /*
                |--------------------------------------------------------------------------
                | FORM ALASAN
                |--------------------------------------------------------------------------
                */

                ->form([

                    Textarea::make(
                        'cancellation_reason'
                    )
                        ->label('Alasan Pembatalan')
                        ->placeholder(
                            'Contoh: Customer membatalkan pembelian'
                        )
                        ->required()
                        ->minLength(3)
                        ->rows(4),

                ])

                /*
                |--------------------------------------------------------------------------
                | ACTION
                |--------------------------------------------------------------------------
                */

                ->action(function (
                    array $data
                ) {

                    try {

                        app(SaleCancellationService::class)->cancel(
                            $this->record,
                            Auth::id(),
                            $data['cancellation_reason']
                        );

                        Notification::make()
                            ->title('Transaksi berhasil dibatalkan')
                            ->body(
                                'Stok produk telah dikembalikan.'
                            )
                            ->success()
                            ->send();

                        /*
                        |--------------------------------------------------------------------------
                        | Refresh record
                        |--------------------------------------------------------------------------
                        */

                        $this->record->refresh();

                    } catch (RuntimeException $e) {

                        Notification::make()
                            ->title('Transaksi gagal dibatalkan')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                    } catch (\Throwable $e) {

                        report($e);

                        Notification::make()
                            ->title('Terjadi kesalahan')
                            ->body(
                                'Transaksi tidak dapat dibatalkan.'
                            )
                            ->danger()
                            ->send();
                    }
                }),

        ];
    }
}
