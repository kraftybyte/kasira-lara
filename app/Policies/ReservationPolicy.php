<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Reservation;
use App\Policies\Concerns\ChecksTenantOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReservationPolicy
{
    use ChecksTenantOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Reservation');
    }

    public function view(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('View:Reservation') && $this->ownedByTenant($reservation);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Reservation');
    }

    public function update(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('Update:Reservation') && $this->ownedByTenant($reservation);
    }

    public function delete(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('Delete:Reservation') && $this->ownedByTenant($reservation);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Reservation');
    }

    public function restore(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('Restore:Reservation') && $this->ownedByTenant($reservation);
    }

    public function forceDelete(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('ForceDelete:Reservation') && $this->ownedByTenant($reservation);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Reservation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Reservation');
    }

    public function replicate(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('Replicate:Reservation') && $this->ownedByTenant($reservation);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Reservation');
    }
}
