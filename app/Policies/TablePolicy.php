<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Table;
use App\Policies\Concerns\ChecksTenantOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TablePolicy
{
    use ChecksTenantOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Table');
    }

    public function view(AuthUser $authUser, Table $table): bool
    {
        return $authUser->can('View:Table') && $this->ownedByTenant($table);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Table');
    }

    public function update(AuthUser $authUser, Table $table): bool
    {
        return $authUser->can('Update:Table') && $this->ownedByTenant($table);
    }

    public function delete(AuthUser $authUser, Table $table): bool
    {
        return $authUser->can('Delete:Table') && $this->ownedByTenant($table);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Table');
    }

    public function restore(AuthUser $authUser, Table $table): bool
    {
        return $authUser->can('Restore:Table') && $this->ownedByTenant($table);
    }

    public function forceDelete(AuthUser $authUser, Table $table): bool
    {
        return $authUser->can('ForceDelete:Table') && $this->ownedByTenant($table);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Table');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Table');
    }

    public function replicate(AuthUser $authUser, Table $table): bool
    {
        return $authUser->can('Replicate:Table') && $this->ownedByTenant($table);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Table');
    }
}
