<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Question;
use App\Enums\RoleEnum;

class QuestionPolicy
{
    /**
     * Déterminer si l'utilisateur peut voir toutes les questions d'un test.
     * 
     * - Admin et Mentor peuvent voir toutes les questions
     * - Les autres (Participants) ne peuvent pas
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [RoleEnum::Admin, RoleEnum::Mentor]);
    }

    /**
     * Déterminer si l'utilisateur peut voir une question spécifique.
     * 
     * - Admin et Mentor peuvent voir
     */
    public function view(User $user, Question $question): bool
    {
        return in_array($user->role, [RoleEnum::Admin, RoleEnum::Mentor]);
    }

    /**
     * Déterminer si l'utilisateur peut créer une question.
     * 
     * - Admin et Mentor peuvent créer
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [RoleEnum::Admin, RoleEnum::Mentor]);
    }

    /**
     * Déterminer si l'utilisateur peut mettre à jour une question.
     * 
     * - Admin et Mentor peuvent mettre à jour
     */
    public function update(User $user, Question $question): bool
    {
        return in_array($user->role, [RoleEnum::Admin, RoleEnum::Mentor]);
    }

    /**
     * Déterminer si l'utilisateur peut supprimer une question.
     * 
     * - Admin seulement
     */
    public function delete(User $user, Question $question): bool
    {
        return $user->role === RoleEnum::Admin;
    }
}
