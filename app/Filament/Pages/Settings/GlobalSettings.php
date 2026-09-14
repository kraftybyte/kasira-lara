<?php

namespace App\Filament\Pages\Settings;

use App\Models\AppSetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class GlobalSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.settings.global-settings';

    protected static ?string $title = 'Pengaturan Aplikasi';

    protected static ?string $slug = 'global-settings';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 9990;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public ?array $data = [];

    public AppSetting $settings;

    public function mount(): void
    {
        // Only super_admin can access
        if (! auth()->user()?->hasRole('super_admin')) {
            abort(403);
        }

        $this->settings = AppSetting::firstOrCreate(['id' => 1]);

        $this->form->fill([
            'app_name' => $this->settings->app_name ?? 'KasirAja',
            'app_logo' => $this->settings->app_logo,
            'app_favicon' => $this->settings->app_favicon,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->model($this->settings)
            ->schema([
                Section::make('Informasi Aplikasi')
                    ->description('Pengaturan global untuk nama dan identitas aplikasi.')
                    ->schema([
                        TextInput::make('app_name')
                            ->label('Nama Aplikasi')
                            ->required()
                            ->maxLength(100),
                    ]),

                Section::make('Logo Aplikasi')
                    ->description('Logo yang akan ditampilkan di header aplikasi.')
                    ->schema([
                        FileUpload::make('app_logo')
                            ->label('Upload Logo')
                            ->image()
                            ->imagePreviewHeight(80)
                            ->disk('public')
                            ->directory('app-logos'),
                    ]),

                Section::make('Favicon')
                    ->description('Icon yang akan ditampilkan di tab browser.')
                    ->schema([
                        FileUpload::make('app_favicon')
                            ->label('Upload Favicon')
                            ->image()
                            ->imagePreviewHeight(40)
                            ->disk('public')
                            ->directory('app-favicons'),
                    ]),
            ]);
    }

    public function save(): void
    {
        // Only super_admin can save
        if (! auth()->user()?->hasRole('super_admin')) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Hanya super_admin yang dapat mengubah pengaturan.')
                ->danger()
                ->send();

            return;
        }

        try {
            $data = $this->form->getState();

            $this->settings->app_name = $data['app_name'] ?? 'KasirAja';
            $this->settings->app_logo = $data['app_logo'] ?? null;
            $this->settings->app_favicon = $data['app_favicon'] ?? null;
            $this->settings->save();

            Notification::make()
                ->title('Berhasil')
                ->body('Pengaturan aplikasi berhasil disimpan.')
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
}
