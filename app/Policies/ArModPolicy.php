<?php

namespace App\Policies;

use App\Models\ArMod;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArModPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can see the list if they have access to at least one server
        return $user->isAdmin() || $user->servers()->count() > 0;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ArMod $arMod): bool
    {
        // Users can view a mod if they have access to the server it belongs to
        return $user->isAdmin() || $user->servers()->where('servers.id', $arMod->server_id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users can create if they have access to at least one server
        return $user->isAdmin() || $user->servers()->count() > 0;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ArMod $arMod): bool
    {
        // Users can update if they have access to the server this mod belongs to
        return $user->isAdmin() || $user->servers()->where('servers.id', $arMod->server_id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ArMod $arMod): bool
    {
        // Users can delete if they have access to the server this mod belongs to
        return $user->isAdmin() || $user->servers()->where('servers.id', $arMod->server_id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ArMod $arMod): bool
    {
        // Users can restore if they have access to the server this mod belongs to
        return $user->isAdmin() || $user->servers()->where('servers.id', $arMod->server_id)->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ArMod $arMod): bool
    {
        // Only admins can force delete
        return $user->isAdmin();
    }
} 