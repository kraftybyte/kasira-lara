<?php

namespace App\Filament\Pages\Settings;

use App\Models\TenantSetting;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class TenantSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.settings.tenant-settings';

    protected static ?string $title = 'Pengaturan';

    protected static ?string $slug = 'settings';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 9995;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|array $accessOwnership = ['owner', 'super_admin'];

    public ?array $data = [];

    public TenantSetting $settings;

    public bool $canEditPaywuz = false;

    public function mount(): void
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            abort(403);
        }

        // Check if user is super_admin (can edit Paywuz settings)
        $user = auth()->user();
        $this->canEditPaywuz = $user && $user->hasRole('super_admin');

        $this->settings = TenantSetting::getOrCreateForTenant($tenant->id);

        $this->data = [
            'store_name' => $this->settings->store_name ?? $tenant->name,
            'logo' => $this->settings->logo,
            'address' => $this->settings->address,
            'phone' => $this->settings->phone,
            'email' => $this->settings->email,

            'show_logo' => $this->settings->show_logo ?? true,
            'show_address' => $this->settings->show_address ?? true,
            'show_phone' => $this->settings->show_phone ?? true,
            'show_customer' => $this->settings->show_customer ?? true,
            'show_cashier' => $this->settings->show_cashier ?? true,
            'show_invoice_number' => $this->settings->show_invoice_number ?? true,
            'show_payment_method' => $this->settings->show_payment_method ?? true,
            'show_footer' => $this->settings->show_footer ?? true,
            'footer_text' => $this->settings->footer_text ?? 'Terima kasih atas kunjungan Anda!',
            'tax_rate' => $this->settings->tax_rate ?? 0,
            'show_tax' => $this->settings->show_tax ?? false,

            'paywuz_enabled' => $this->settings->paywuz_enabled ?? false,
            'paywuz_merchant_name' => $this->settings->paywuz_merchant_name ?? 'KasirAja',
            'paywuz_api_key' => $this->settings->paywuz_api_key,
            'paywuz_fee_by_merchant' => $this->settings->paywuz_fee_by_merchant ?? false,

            'bank_name' => $this->settings->bank_name,
            'bank_account' => $this->settings->bank_account,
            'bank_account_name' => $this->settings->bank_account_name,
            'bank_qr_image' => $this->settings->bank_qr_image,
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data')
            ->schema([
                Tabs::make('settings_tabs')
                    ->tabs([
                        Tab::make('store')
                            ->label('Informasi Toko')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Section::make('Informasi Dasar')
                                    ->schema([
                                        TextInput::make('store_name')
                                            ->label('Nama Toko'),

                                        TextInput::make('phone')
                                            ->label('Nomor Telepon'),

                                        TextInput::make('email')
                                            ->label('Email'),
                                    ]),

                                Section::make('Alamat')
                                    ->schema([
                                        Textarea::make('address')
                                            ->label('Alamat')
                                            ->rows(2),
                                    ]),
                            ]),

                        Tab::make('receipt')
                            ->label('Pengaturan Struk')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Logo Toko')
                                    ->schema([
                                        Group::make([
                                            FileUpload::make('logo')
                                                ->label('')
                                                ->image()
                                                ->imagePreviewHeight(100)
                                                ->directory('receipt-logos'),
                                        ])->columns(2),
                                    ]),

                                Section::make('Tampilan')
                                    ->schema([
                                        Toggle::make('show_logo')
                                            ->label('Logo'),

                                        Toggle::make('show_address')
                                            ->label('Alamat'),

                                        Toggle::make('show_phone')
                                            ->label('Telepon'),

                                        Toggle::make('show_customer')
                                            ->label('Pelanggan'),

                                        Toggle::make('show_cashier')
                                            ->label('Kasir'),

                                        Toggle::make('show_invoice_number')
                                            ->label('Invoice'),

                                        Toggle::make('show_payment_method')
                                            ->label('Metode Bayar'),
                                    ])->columns(2),

                                Section::make('Lainnya')
                                    ->schema([
                                        Toggle::make('show_footer')
                                            ->label('Footer Pesanan'),

                                        Textarea::make('footer_text')
                                            ->label('Teks Footer')
                                            ->rows(2)
                                            ->visible(fn (callable $get) => $get('show_footer')),

                                        Toggle::make('show_tax')
                                            ->label('Tampilkan Pajak'),

                                        TextInput::make('tax_rate')
                                            ->label('Tarif Pajak (%)')
                                            ->numeric()
                                            ->suffix('%')
                                            ->visible(fn (callable $get) => $get('show_tax')),
                                    ]),
                            ]),

                        Tab::make('payment')
                            ->label('Payment')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Paywuz QRIS')
                                    ->schema([
                                        Toggle::make('paywuz_enabled')
                                            ->label('Aktifkan Paywuz')
                                            ->disabled(fn () => ! $this->canEditPaywuz)
                                            ->live(),

                                        TextInput::make('paywuz_merchant_name')
                                            ->label('Nama Merchant')
                                            ->disabled(fn () => ! $this->canEditPaywuz)
                                            ->visible(fn (callable $get) => $get('paywuz_enabled') === true),

                                        TextInput::make('paywuz_api_key')
                                            ->label('API Key')
                                            ->disabled(fn () => ! $this->canEditPaywuz)
                                            ->visible(fn (callable $get) => $get('paywuz_enabled') === true),

                                        Toggle::make('paywuz_fee_by_merchant')
                                            ->label('Tanggung Biaya Admin')
                                            ->helperText('PILIH = Tenant menyerap biaya admin. OFF = Biaya admin ditambahkan ke tagihan customer.')
                                            ->visible(fn (callable $get) => $get('paywuz_enabled') === true),

                                        Placeholder::make('paywuz_help')
                                            ->label('Panduan')
                                            ->content(fn () => view('filament.pages.settings.partials.paywuz-help'))
                                            ->visible(fn (callable $get) => $get('paywuz_enabled') === true),
                                    ]),

                                Section::make('')
                                    ->schema([
                                        Placeholder::make('test_connection')
                                            ->label('')
                                            ->content(view('filament.pages.settings.partials.test-connection-button')),
                                    ]),
                            ]),

                        Tab::make('bank')
                            ->label('Rekening Bank')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                Section::make('Informasi Rekening')
                                    ->description('Digunakan untuk pembayaran Transfer Manual')
                                    ->schema([
                                        TextInput::make('bank_name')
                                            ->label('Nama Bank')
                                            ->placeholder('Contoh: BCA, Mandiri, BNI'),

                                        TextInput::make('bank_account')
                                            ->label('Nomor Rekening')
                                            ->placeholder('Contoh: 1234567890'),

                                        TextInput::make('bank_account_name')
                                            ->label('Nama Pemilik Rekening')
                                            ->placeholder('Contoh: Toko Sejahtera'),
                                    ]),

                                Section::make('QR Code Pembayaran')
                                    ->description('Upload QR Code untuk pembayaran Manual')
                                    ->schema([
                                        FileUpload::make('bank_qr_image')
                                            ->label('Gambar QR')
                                            ->image()
                                            ->imagePreviewHeight(150)
                                            ->directory('bank-qr-codes'),
                                    ]),
                            ]),

                        Tab::make('about')
                            ->label('Tentang')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('')
                                    ->schema([
                                        Placeholder::make('app_name')
                                            ->label('Aplikasi')
                                            ->content('KasirAja - POS System'),

                                        Placeholder::make('version')
                                            ->label('Versi')
                                            ->content('1.0.0'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->data;

        $tenant = Filament::getTenant();
        if (! $tenant) {
            abort(403);
        }

        $user = auth()->user();
        $roles = $user ? $user->getRoleNames()->toArray() : [];
        $isOwner = in_array('owner', $roles);
        $isSuperAdmin = in_array('super_admin', $roles);

        if (! $isOwner && ! $isSuperAdmin) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Hanya owner atau super_admin yang dapat mengubah pengaturan.')
                ->danger()
                ->send();

            return;
        }

        try {
            // Common fields (owner & super_admin can edit)
            $this->settings->store_name = $data['store_name'] ?? null;
            $this->settings->logo = $data['logo'] ?? null;
            $this->settings->address = $data['address'] ?? null;
            $this->settings->phone = $data['phone'] ?? null;
            $this->settings->email = $data['email'] ?? null;
            $this->settings->show_logo = $data['show_logo'] ?? false;
            $this->settings->show_address = $data['show_address'] ?? false;
            $this->settings->show_phone = $data['show_phone'] ?? false;
            $this->settings->show_customer = $data['show_customer'] ?? false;
            $this->settings->show_cashier = $data['show_cashier'] ?? false;
            $this->settings->show_invoice_number = $data['show_invoice_number'] ?? false;
            $this->settings->show_payment_method = $data['show_payment_method'] ?? false;
            $this->settings->show_footer = $data['show_footer'] ?? false;
            $this->settings->footer_text = $data['footer_text'] ?? null;
            $this->settings->tax_rate = $data['tax_rate'] ?? 0;
            $this->settings->show_tax = $data['show_tax'] ?? false;

            // Paywuz toggle (owner & super_admin can edit)
            $this->settings->paywuz_enabled = $data['paywuz_enabled'] ?? false;

            // Paywuz settings - ONLY super_admin can edit API Key
            if ($isSuperAdmin) {
                $this->settings->paywuz_merchant_name = $data['paywuz_merchant_name'] ?? null;
                $this->settings->paywuz_api_key = $data['paywuz_api_key'] ?? null;
            }

            // Fee by merchant - owner & super_admin can edit
            $this->settings->paywuz_fee_by_merchant = $data['paywuz_fee_by_merchant'] ?? false;

            // Bank account fields (owner & super_admin can edit)
            $this->settings->bank_name = $data['bank_name'] ?? null;
            $this->settings->bank_account = $data['bank_account'] ?? null;
            $this->settings->bank_account_name = $data['bank_account_name'] ?? null;
            $this->settings->bank_qr_image = $data['bank_qr_image'] ?? null;

            $this->settings->save();

            // Also update Tenant name if store_name changed
            if (! empty($data['store_name']) && $tenant->name !== $data['store_name']) {
                $tenant->update(['name' => $data['store_name']]);
            }

            Notification::make()
                ->title('Berhasil')
                ->body('Pengaturan berhasil disimpan.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal')
                ->body('Error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testPaywuzConnection(): void
    {
        $apiKey = $this->data['paywuz_api_key'] ?? null;

        if (empty($apiKey)) {
            Notification::make()
                ->title('API Key Kosong')
                ->body('Masukkan API Key terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->get('https://paywuz.id/api/v1/transaction/history');

            if ($response->successful()) {
                Notification::make()
                    ->title('Koneksi Berhasil')
                    ->body('API Key valid dan koneksi berhasil.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Koneksi Gagal')
                    ->body('API Key tidak valid atau sudah expire.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Koneksi Gagal')
                ->body('Tidak dapat terhubung ke Paywuz: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
