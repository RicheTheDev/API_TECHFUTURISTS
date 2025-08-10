<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Report;
use App\Enums\RoleEnum;
use App\Enums\ReportStatusEnum;

class ReportPolicy
{
    /**
     * Détermine si l'utilisateur peut voir la liste de tous les rapports.
     *
     * Règles :
     * - Seuls les rôles Admin et Mentor peuvent consulter tous les rapports.
     *
     * @param  User  $user  Utilisateur authentifié
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [RoleEnum::Admin, RoleEnum::Mentor]);
    }

    /**
     * Détermine si l'utilisateur peut voir un rapport spécifique.
     *
     * Règles :
     * - Admin et Mentor peuvent voir tous les rapports.
     * - Un participant peut voir uniquement ses propres rapports.
     *
     * @param  User   $user    Utilisateur authentifié
     * @param  Report $report  Rapport à consulter
     * @return bool
     */
    public function view(User $user, Report $report): bool
    {
        return $user->role === RoleEnum::Admin
            || $user->role === RoleEnum::Mentor
            || $user->role === RoleEnum::Participant
            || $report->submitted_by === $user->id;
    }

    /**
     * Détermine si l'utilisateur peut créer un rapport.
     *
     * Règles :
     * - Seul un Participant peut créer un rapport.
     *
     * @param  User  $user  Utilisateur authentifié
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->role === RoleEnum::Participant;
    }

    /**
     * Détermine si l'utilisateur peut mettre à jour un rapport.
     *
     * Règles :
     * - Admin peut modifier n'importe quel rapport.
     * - Un participant peut modifier son rapport uniquement si :
     *   - C'est bien lui l'auteur du rapport.
     *   - Le rapport est encore au statut "Soumis".
     *
     * @param  User   $user    Utilisateur authentifié
     * @param  Report $report  Rapport à modifier
     * @return bool
     */
    public function update(User $user, Report $report): bool
    {
        return $user->role === RoleEnum::Admin
            || ($user->role === RoleEnum::Participant
                && $report->submitted_by === $user->id
                && $report->status === ReportStatusEnum::Submitted->value);
    }

    /**
     * Détermine si l'utilisateur peut supprimer un rapport.
     *
     * Règles :
     * - Admin peut supprimer n'importe quel rapport.
     * - Un participant peut supprimer son rapport uniquement si :
     *   - C'est bien lui l'auteur du rapport.
     *   - Le rapport est encore au statut "Soumis".
     *
     * @param  User   $user    Utilisateur authentifié
     * @param  Report $report  Rapport à supprimer
     * @return bool
     */
    public function delete(User $user, Report $report): bool
    {
        return $user->role === RoleEnum::Admin
            || ($user->role === RoleEnum::Participant
                && $report->submitted_by === $user->id
                && $report->status === ReportStatusEnum::Submitted->value);
    }
}
