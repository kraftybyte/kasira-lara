<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Ingredient;
use App\Policies\Concerns\ChecksTenantOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class IngredientPolicy
{
    use ChecksTenantOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Ingredient');
    }

    public function view(AuthUser $authUser, Ingredient $ingredient): bool
    {
        return $authUser->can('View:Ingredient') && $this->ownedByTenant($ingredient);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Ingredient');
    }

    public function update(AuthUser $authUser, Ingredient $ingredient): bool
    {
        return $authUser->can('Update:Ingredient') && $this->ownedByTenant($ingredient);
    }

    public function delete(AuthUser $authUser, Ingredient $ingredient): bool
    {
        return $authUser->can('Delete:Ingredient') && $this->ownedByTenant($ingredient);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Ingredient');
    }

    public function restore(AuthUser $authUser, Ingredient $ingredient): bool
    {
        return $authUser->can('Restore:Ingredient') && $this->ownedByTenant($ingredient);
    }

    public function forceDelete(AuthUser $authUser, Ingredient $ingredient): bool
    {
        return $authUser->can('ForceDelete:Ingredient') && $this->ownedByTenant($ingredient);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Ingredient');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Ingredient');
    }

    public function replicate(AuthUser $authUser, Ingredient $ingredient): bool
    {
        return $authUser->can('Replicate:Ingredient') && $this->ownedByTenant($ingredient);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Ingredient');
    }
}
