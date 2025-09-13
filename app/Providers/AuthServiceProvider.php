<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;
use App\Policies\UserPolicy;

use App\Models\Resource;
use App\Policies\ResourcePolicy;

use App\Models\Report;
use App\Policies\ReportPolicy;

use App\Models\Project;
use App\Policies\ProjectPolicy;

use App\Models\UserTestResult;
use App\Policies\UserTestResultPolicy;

use App\Models\Test;
use App\Policies\TestPolicy;

use App\Models\Question;
use App\Policies\QuestionPolicy;



class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Resource::class => ResourcePolicy::class, // <-- Ajout de la ResourcePolicy
        Report::class => ReportPolicy::class,
        Project::class => ProjectPolicy::class,
        UserTestResult::class => UserTestResultPolicy::class,
        Test::class => TestPolicy::class,
        Question::class => QuestionPolicy::class

    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Autres bootstraps si nécessaire
    }
}
