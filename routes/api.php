<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ResourceController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\TestController;
use App\Http\Controllers\API\QuestionController;  


/*
|--------------------------------------------------------------------------
| Auth public routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);

/*
|--------------------------------------------------------------------------
| Auth protected routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.jwt'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Tests - Accessible à tous les rôles authentifiés
    Route::get('/tests', [TestController::class, 'index']);
    Route::get('/tests/{id}', [TestController::class, 'show']);
    Route::get('/tests/download/{id}', [TestController::class, 'download']);

    // Téléchargement accessible à tous les rôles
    Route::get('/projects/download/{id}', [ProjectController::class, 'download']);
    Route::get('/reports/download/{id}', [ReportController::class, 'download']);

    // Infos utilisateur connecté
    Route::middleware('auth:api')->get('/me', [UserController::class, 'me']);

    // Gestion des questions
    Route::get('/questions/{id}/download', [QuestionController::class, 'download'])->name('questions.download');
    Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');
    Route::get('/questions/{id}', [QuestionController::class, 'show']) ->name('questions.show');

   // Gestion des résultats du participant
    Route::get('/user-test-results', [UserTestResultController::class, 'index']);
    Route::get('/user-test-results/{id}/download', [UserTestResultController::class, 'download']);


});


/*
|--------------------------------------------------------------------------
| Routes Admin uniquement
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.jwt'])->group(function () {
    Route::get('/admin/dashboard', [UserController::class, 'adminDashboard']);
    
    // Gestion des participants
    Route::get('/participants', [UserController::class, 'index']);
    Route::get('/participants/{id}', [UserController::class, 'show']);
    Route::put('/participants/{id}', [UserController::class, 'update']);
    Route::delete('/participants/{id}', [UserController::class, 'destroy']);

    // Gestion des ressources
    Route::get('/resources', [ResourceController::class, 'index']);
    Route::post('/resources', [ResourceController::class, 'store']);
    Route::get('/resources/{id}', [ResourceController::class, 'show']);
    Route::put('/resources/{id}', [ResourceController::class, 'update']);
    Route::delete('/resources/{id}', [ResourceController::class, 'destroy']);
    Route::get('/resources/download/{id}', [ResourceController::class, 'download']);

    // Gestion des rapports
    Route::get('/reports', [ReportController::class, 'index']); // Voir tous les rapports
    Route::put('/reports/{id}/status', [ReportController::class, 'updateStatus']); // Modifier statut

    Route::post('/projects', [ProjectController::class, 'store']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::post('/projects/admin/edit/{id}', [ProjectController::class, 'editAdmin']);

    // Gestion des tests (Admin uniquement)
    Route::post('/tests', [TestController::class, 'store']);
    Route::put('/tests/{id}', [TestController::class, 'update']);
    Route::delete('/tests/{id}', [TestController::class, 'destroy']);

    //Gestion des questions
    Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::post('/questions/{id}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{id}', [QuestionController::class, 'destroy'])->name('questions.destroy');

    // Gestion des results du participant
    Route::post('/user-test-results', [UserTestResultController::class, 'store']);
    Route::put('/user-test-results/{id}', [UserTestResultController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| Routes Mentor et Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.jwt', 'role:Admin,Mentor'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']); // Liste
    Route::put('/reports/{id}/status', [ReportController::class, 'updateStatus']); // Changer statut

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects/admin/edit/{id}', [ProjectController::class, 'editAdmin']);
});

/*
|--------------------------------------------------------------------------
| Routes Participant uniquement
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.jwt', 'role:Participant'])->group(function () {
    Route::get('/participant/dashboard', [UserController::class, 'participantDashboard']);

    // Accès aux ressources
    // Route::get('/resources', [ResourceController::class, 'index']);
    // Route::get('/resources/{id}', [ResourceController::class, 'show']);
    Route::get('/resources/{id}/download', [ResourceController::class, 'download']);

    // Gestion des rapports du participant
    Route::post('/reports', [ReportController::class, 'store']); // Créer un rapport
    Route::get('/reports/{id}', [ReportController::class, 'show']); // Voir un rapport
    Route::put('/reports/{id}', [ReportController::class, 'update']); // Modifier rapport
    Route::delete('/reports/{id}', [ReportController::class, 'destroy']); // Supprimer rapport
    
    // Liste des projets de l'utilisateur connecté
    Route::get('/projects/participant', [ProjectController::class, 'participantProjects']);
    Route::post('/projects/participant/edit/{id}', [ProjectController::class, 'editParticipant']);
});