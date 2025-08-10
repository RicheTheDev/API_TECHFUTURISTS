<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\RoleEnum;

class UserPolicy
{
    /**
     * Autorise uniquement les admins à voir la liste des utilisateurs.
     */
    public function viewAny(User $user): bool
    {
        return $user->role->value === RoleEnum::Admin->value;
    }

    /**
     * Autorise l’admin à voir tout utilisateur, ou un utilisateur à voir son propre profil.
     */
    public function view(User $user, User $model): bool
    {
        return $user->role->value === RoleEnum::Admin->value || $user->id === $model->id;
    }

    /**
     * Autorise l’admin à modifier tout utilisateur, ou un utilisateur à modifier son propre profil.
     */
    public function update(User $user, User $model): bool
    {
        return $user->role->value === RoleEnum::Admin->value || $user->id === $model->id;
    }

    /**
     * Autorise uniquement l’admin à supprimer un utilisateur.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->role->value === RoleEnum::Admin->value;
    }
}
