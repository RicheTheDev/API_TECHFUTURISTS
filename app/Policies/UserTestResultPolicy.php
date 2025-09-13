<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserTestResult;
use App\Enums\RoleEnum;

class UserTestResultPolicy
{
    /**
     * Détermine si l'utilisateur peut voir tous les résultats.
     *
     * Règles :
     * - Admin peut voir tous les résultats
     * - Un utilisateur normal peut voir uniquement ses résultats
     */
    public function viewAny(User $user): bool
    {
        return $user->role === RoleEnum::Admin;
    }

    /**
     * Détermine si l'utilisateur peut voir un résultat spécifique.
     *
     * Règles :
     * - Admin peut voir tous les résultats
     * - Un utilisateur peut voir uniquement ses propres résultats
     */
    public function view(User $user, UserTestResult $result): bool
    {
        return $user->role === RoleEnum::Admin || $result->user_id === $user->id;
    }

    /**
     * Détermine si l'utilisateur peut créer un résultat.
     *
     * Règles :
     * - Seul l'Admin peut créer un résultat
     */
    public function create(User $user): bool
    {
        return $user->role === RoleEnum::Admin;
    }

    /**
     * Détermine si l'utilisateur peut mettre à jour un résultat.
     *
     * Règles :
     * - Seul l'Admin peut mettre à jour un résultat
     */
    public function update(User $user, UserTestResult $result): bool
    {
        return $user->role === RoleEnum::Admin;
    }

    /**
     * Détermine si l'utilisateur peut supprimer un résultat.
     *
     * Règles :
     * - Seul l'Admin peut supprimer un résultat
     */
    public function delete(User $user, UserTestResult $result): bool
    {
        return $user->role === RoleEnum::Admin;
    }

    /**
     * Détermine si l'utilisateur peut télécharger le fichier du résultat.
     *
     * Règles :
     * - Admin peut télécharger tous les fichiers
     * - Un utilisateur peut télécharger uniquement son propre fichier
     */
    public function download(User $user, UserTestResult $result): bool
    {
        return $user->role === RoleEnum::Admin || $result->user_id === $user->id;
    }
}
