<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Test;
use App\Enums\RoleEnum;

class TestPolicy
{
    /**
     * Détermine si l'utilisateur peut voir la liste des tests.
     *
     * Règle :
     * - Tous les utilisateurs peuvent voir les tests.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true; // Tous les utilisateurs peuvent voir la liste
    }

    /**
     * Détermine si l'utilisateur peut voir un test spécifique.
     *
     * Règle :
     * - Tous les utilisateurs peuvent voir un test.
     *
     * @param User $user
     * @param Test $test
     * @return bool
     */
    public function view(User $user, Test $test): bool
    {
        return true;
    }

    /**
     * Détermine si l'utilisateur peut créer un test.
     *
     * Règle :
     * - Seuls les Admins peuvent créer un test.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->role === RoleEnum::Admin;
    }

    /**
     * Détermine si l'utilisateur peut mettre à jour un test.
     *
     * Règle :
     * - Seuls les Admins peuvent modifier un test.
     *
     * @param User $user
     * @param Test $test
     * @return bool
     */
    public function update(User $user, Test $test): bool
    {
        return $user->role === RoleEnum::Admin;
    }

    /**
     * Détermine si l'utilisateur peut supprimer un test.
     *
     * Règle :
     * - Seuls les Admins peuvent supprimer un test.
     *
     * @param User $user
     * @param Test $test
     * @return bool
     */
    public function delete(User $user, Test $test): bool
    {
        return $user->role === RoleEnum::Admin;
    }

    /**
     * Détermine si l'utilisateur peut télécharger un test.
     *
     * Règle :
     * - Tous les utilisateurs peuvent télécharger un test.
     *
     * @param User $user
     * @param Test $test
     * @return bool
     */
    public function download(User $user, Test $test): bool
    {
        return true;
    }
}
