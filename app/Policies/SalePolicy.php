<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sale;
use App\Policies\Concerns\ChecksTenantOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SalePolicy
{
    use ChecksTenantOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Sale');
    }

    public function view(AuthUser $authUser, Sale $sale): bool
    {
        return $authUser->can('View:Sale') && $this->ownedByTenant($sale);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Sale');
    }

    public function update(AuthUser $authUser, Sale $sale): bool
    {
        return $authUser->can('Update:Sale') && $this->ownedByTenant($sale);
    }

    public function delete(AuthUser $authUser, Sale $sale): bool
    {
        return $authUser->can('Delete:Sale') && $this->ownedByTenant($sale);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Sale');
    }

    public function restore(AuthUser $authUser, Sale $sale): bool
    {
        return $authUser->can('Restore:Sale') && $this->ownedByTenant($sale);
    }

    public function forceDelete(AuthUser $authUser, Sale $sale): bool
    {
        return $authUser->can('ForceDelete:Sale') && $this->ownedByTenant($sale);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Sale');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Sale');
    }

    public function replicate(AuthUser $authUser, Sale $sale): bool
    {
        return $authUser->can('Replicate:Sale') && $this->ownedByTenant($sale);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Sale');
    }
}
