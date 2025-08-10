<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Resource;
use App\Enums\RoleEnum;

class ResourcePolicy
{
    /**
    * Détermine si l'utilisateur peut voir la liste des ressources.
    * Ici, on autorise Admin, Mentor et Participant.
     */
        public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            RoleEnum::Admin->value,
            RoleEnum::Mentor->value,
            RoleEnum::Participant->value,
        ]);
    }

    /**
     * Détermine si l'utilisateur peut voir une ressource spécifique.
     * Tous les rôles peuvent voir une ressource.
     */
    public function view(User $user, Resource $resource): bool
    {
        return in_array($user->role, RoleEnum::getValues());
    }

    /**
     * Détermine si l'utilisateur peut créer une ressource.
     * Seuls les Admins peuvent créer.
     */
    public function create(User $user): bool
    {
        return $user->role === RoleEnum::Admin->value;
    }

    /**
     * Détermine si l'utilisateur peut modifier une ressource.
     * Seuls les Admins peuvent modifier.
     */
    public function update(User $user, Resource $resource): bool
    {
        return $user->role === RoleEnum::Admin->value;
    }

    /**
     * Détermine si l'utilisateur peut supprimer une ressource.
     * Seuls les Admins peuvent supprimer.
     */
    public function delete(User $user, Resource $resource): bool
    {
        return $user->role === RoleEnum::Admin->value;
    }
}
