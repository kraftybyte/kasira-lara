<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Policies\Concerns\ChecksTenantOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CategoryPolicy
{
    use ChecksTenantOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Category');
    }

    public function view(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('View:Category') && $this->ownedByTenant($category);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Category');
    }

    public function update(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('Update:Category') && $this->ownedByTenant($category);
    }

    public function delete(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('Delete:Category') && $this->ownedByTenant($category);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Category');
    }

    public function restore(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('Restore:Category') && $this->ownedByTenant($category);
    }

    public function forceDelete(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('ForceDelete:Category') && $this->ownedByTenant($category);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Category');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Category');
    }

    public function replicate(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('Replicate:Category') && $this->ownedByTenant($category);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Category');
    }
}
