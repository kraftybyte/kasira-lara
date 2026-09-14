<?php

namespace App\Filament\Auth\Pages;

use App\Models\AppSetting;

class Login extends \Filament\Auth\Pages\Login
{
    public function getTitle(): string
    {
        $appSetting = AppSetting::first();

        return $appSetting?->app_name ?? 'KasirAja';
    }

    public function getBrandLogo(): ?string
    {
        $appSetting = AppSetting::first();
        $logo = $appSetting?->app_logo;

        if (blank($logo)) {
            return null;
        }

        // Handle FileUpload JSON format
        if (is_string($logo)) {
            $decoded = json_decode($logo, true);
            if (is_array($decoded) && ! empty($decoded)) {
                $logo = array_keys($decoded)[0];
            }
        }

        if (blank($logo)) {
            return null;
        }

        return url('storage/'.$logo);
    }

    public function getBrandLogoHeight(): ?string
    {
        return '2.5rem';
    }

    public function getHeading(): ?string
    {
        return null;
    }
}
