<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Policies\Concerns\ChecksTenantOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CustomerPolicy
{
    use ChecksTenantOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Customer');
    }

    public function view(AuthUser $authUser, Customer $customer): bool
    {
        return $authUser->can('View:Customer') && $this->ownedByTenant($customer);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Customer');
    }

    public function update(AuthUser $authUser, Customer $customer): bool
    {
        return $authUser->can('Update:Customer') && $this->ownedByTenant($customer);
    }

    public function delete(AuthUser $authUser, Customer $customer): bool
    {
        return $authUser->can('Delete:Customer') && $this->ownedByTenant($customer);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Customer');
    }

    public function restore(AuthUser $authUser, Customer $customer): bool
    {
        return $authUser->can('Restore:Customer') && $this->ownedByTenant($customer);
    }

    public function forceDelete(AuthUser $authUser, Customer $customer): bool
    {
        return $authUser->can('ForceDelete:Customer') && $this->ownedByTenant($customer);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Customer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Customer');
    }

    public function replicate(AuthUser $authUser, Customer $customer): bool
    {
        return $authUser->can('Replicate:Customer') && $this->ownedByTenant($customer);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Customer');
    }
}
