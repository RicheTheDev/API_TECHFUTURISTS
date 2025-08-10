<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Project;
use App\Enums\RoleEnum;
use App\Enums\ProjectStatusEnum;

class ProjectPolicy
{
    /**
     * Détermine si l'utilisateur peut voir la liste de tous les projets.
     *
     * Règles :
     * - Seuls les rôles Admin et Mentor peuvent consulter tous les projets.
     *
     * @param  User  $user  Utilisateur authentifié
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [RoleEnum::Admin, RoleEnum::Mentor]);
    }

    /**
     * Détermine si l'utilisateur peut voir un projet spécifique.
     *
     * Règles :
     * - Admin et Mentor peuvent voir tous les projets.
     * - Un participant peut voir uniquement ses propres projets.
     *
     * @param  User    $user    Utilisateur authentifié
     * @param  Project $project Projet à consulter
     * @return bool
     */
    public function view(User $user, Project $project): bool
    {
        return $user->role === RoleEnum::Admin
            || $user->role === RoleEnum::Mentor
            || $project->submitted_by === $user->id;
    }

    /**
     * Détermine si l'utilisateur peut créer un projet.
     *
     * Règles :
     * - Seul un Participant peut créer un projet.
     *
     * @param  User  $user  Utilisateur authentifié
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->role === RoleEnum::Admin;
    }

    /**
     * Détermine si l'utilisateur peut mettre à jour un projet.
     *
     * Règles :
     * - Admin peut modifier n'importe quel projet.
     * - Un participant peut modifier son projet uniquement si :
     *   - C'est bien lui l'auteur du projet.
     *   - Le projet est encore au statut "Soumis".
     *
     * @param  User    $user    Utilisateur authentifié
     * @param  Project $project Projet à modifier
     * @return bool
     */
    public function update(User $user, Project $project): bool
    {
        return $user->role === RoleEnum::Admin
            || ($user->role === RoleEnum::Participant
                && $project->submitted_by === $user->id
                && $project->status === ProjectStatusEnum::Submitted->value);
    }

    /**
     * Détermine si l'utilisateur peut supprimer un projet.
     *
     * Règles :
     * - Admin peut supprimer n'importe quel projet.
     * - Un participant peut supprimer son projet uniquement si :
     *   - C'est bien lui l'auteur du projet.
     *   - Le projet est encore au statut "Soumis".
     *
     * @param  User    $user    Utilisateur authentifié
     * @param  Project $project Projet à supprimer
     * @return bool
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->role === RoleEnum::Admin
            || ($user->role === RoleEnum::Participant
                && $project->submitted_by === $user->id
                && $project->status === ProjectStatusEnum::Submitted->value);
    }
}
