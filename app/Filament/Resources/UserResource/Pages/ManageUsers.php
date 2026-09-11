<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource\UserResource;
use App\Models\Tenant;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Facades\Filament;

class ManageUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        // Assign user to current tenant after creation
        $tenant = Filament::getTenant();
        $user = $this->getRecord();

        if ($tenant && $user) {
            $tenant->users()->attach($user->id, ['status' => 'active']);
        }
    }
}
