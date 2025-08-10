<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Enums\ProjectStatusEnum;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Projects",
 *     description="Gestion des projets"
 * )
 *
 * @OA\Schema(
 *     schema="Project",
 *     type="object",
 *     title="Project",
 *     required={"id","title","file_url","file_type","submitted_by","submitted_at","status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Plateforme de e-learning"),
 *     @OA\Property(property="description", type="string", example="Description du projet"),
 *     @OA\Property(property="file_url", type="string", example="uploads/projects/abc123.zip"),
 *     @OA\Property(property="file_type", type="string", example="zip"),
 *     @OA\Property(property="feedback", type="string", nullable=true, example="Bon concept, à approfondir"),
 *     @OA\Property(property="submitted_by", type="integer", example=42),
 *     @OA\Property(property="submitted_at", type="string", format="date-time", example="2025-08-08T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-09T12:00:00Z"),
 *     @OA\Property(property="status", type="string", enum={"Soumis", "En Revue", "Approuvé", "Rejeté"}, example="Soumis")
 * )
 */
class ProjectController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/projects",
     *     tags={"Projects"},
     *     summary="Liste des projets avec statistiques",
     *     description="Retourne les projets accessibles à l'utilisateur selon ses permissions. Les Admins et Mentors voient tous les projets, les Participants uniquement les leurs.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste des projets et statistiques", @OA\JsonContent(
     *         @OA\Property(property="projects", type="array", @OA\Items(ref="#/components/schemas/Project")),
     *         @OA\Property(property="total_submitted", type="integer", example=10),
     *         @OA\Property(property="total_approved", type="integer", example=4),
     *         @OA\Property(property="total_in_review", type="integer", example=3),
     *         @OA\Property(property="projects_this_month", type="integer", example=2)
     *     ))
     * )
     */
    public function index()
    {
        $this->authorize('viewAny', Project::class);

        $user = Auth::user();

        if ($user->can('viewAny', Project::class)) {
            $projects = Project::all();
        } else {
            $projects = Project::where('submitted_by', $user->id)->get();
        }

        $total_submitted = $projects->count();
        $total_approved = $projects->where('status', ProjectStatusEnum::Approved->value)->count();
        $total_in_review = $projects->where('status', ProjectStatusEnum::InReview->value)->count();

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $projects_this_month = Project::whereYear('submitted_at', $currentYear)
            ->whereMonth('submitted_at', $currentMonth)
            ->count();

        return $this->successResponse([
            'projects' => $projects,
            'total_submitted' => $total_submitted,
            'total_approved' => $total_approved,
            'total_in_review' => $total_in_review,
            'projects_this_month' => $projects_this_month,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/projects/participant",
     *     tags={"Projects"},
     *     summary="Liste des projets de l'utilisateur connecté",
     *     description="Si l'utilisateur est Admin ou Mentor, retourne tous les projets. Sinon, retourne uniquement ceux qu'il a soumis.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste des projets", @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Project")
     *     ))
     * )
     */
    public function participantProjects()
    {
        $this->authorize('viewAny', Project::class);

        $user = Auth::user();

        if ($user->can('viewAny', Project::class)) {
            $projects = Project::all();
        } else {
            $projects = Project::where('submitted_by', $user->id)->get();
        }

        return $this->successResponse($projects);
    }

    /**
     * @OA\Post(
     *     path="/api/projects",
     *     tags={"Projects"},
     *     summary="Créer un nouveau projet",
     *     description="Seuls les utilisateurs avec le rôle Admin peuvent créer un projet.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(required={"title","file"}, @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Projet créé", @OA\JsonContent(ref="#/components/schemas/Project")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $file = $request->file('file');
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads/projects', $filename, 'public');

        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_url' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
            'status' => ProjectStatusEnum::Submitted->value,
        ]);

        return $this->createdResponse($project, 'Projet soumis avec succès.');
    }

    /**
     * @OA\Post(
     *     path="/api/projects/admin/edit/{id}",
     *     tags={"Projects"},
     *     summary="Modifier un projet (Admin/Mentor)",
     *     description="Seuls les Admins et Mentors peuvent modifier n'importe quel projet.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="file", type="string", format="binary"),
     *                 @OA\Property(property="feedback", type="string"),
     *                 @OA\Property(property="status", type="string", enum={"Soumis", "En Revue", "Approuvé", "Rejeté"})
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Projet mis à jour", @OA\JsonContent(ref="#/components/schemas/Project")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Projet non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function editAdmin(Request $request, $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return $this->notFoundResponse('Projet non trouvé');
        }

        $this->authorize('update', $project);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file',
            'feedback' => 'nullable|string',
            'status' => 'nullable|string|in:' . implode(',', ProjectStatusEnum::getValues()),
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if ($request->hasFile('file')) {
            if ($project->file_url && Storage::disk('public')->exists($project->file_url)) {
                Storage::disk('public')->delete($project->file_url);
            }
            $file = $request->file('file');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/projects', $filename, 'public');
            $project->file_url = $path;
            $project->file_type = $file->getClientOriginalExtension();
        }

        $project->fill($request->only(['title', 'description', 'feedback', 'status']))->save();

        return $this->successResponse($project, 'Projet mis à jour avec succès.');
    }

    /**
     * @OA\Post(
     *     path="/api/projects/participant/edit/{id}",
     *     tags={"Projects"},
     *     summary="Modifier son propre projet",
     *     description="Seuls les Participants peuvent modifier leurs propres projets, et uniquement si le statut est 'Soumis'.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Projet mis à jour", @OA\JsonContent(ref="#/components/schemas/Project")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Projet non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function editParticipant(Request $request, $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return $this->notFoundResponse('Projet non trouvé');
        }

        $this->authorize('update', $project);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if ($request->hasFile('file')) {
            if ($project->file_url && Storage::disk('public')->exists($project->file_url)) {
                Storage::disk('public')->delete($project->file_url);
            }
            $file = $request->file('file');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/projects', $filename, 'public');
            $project->file_url = $path;
            $project->file_type = $file->getClientOriginalExtension();
        }

        $project->fill($request->only(['title', 'description']))->save();

        return $this->successResponse($project, 'Projet mis à jour avec succès.');
    }

    /**
     * @OA\Get(
     *     path="/api/projects/download/{id}",
     *     tags={"Projects"},
     *     summary="Télécharger un fichier de projet",
     *     description="L'utilisateur doit avoir l'autorisation de voir le projet pour pouvoir télécharger son fichier.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Fichier téléchargé"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Projet ou fichier non trouvé")
     * )
     */
    public function download($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return $this->notFoundResponse('Projet non trouvé');
        }

        $this->authorize('view', $project);

        if (!$project->file_url || !Storage::disk('public')->exists($project->file_url)) {
            return $this->notFoundResponse('Fichier introuvable.');
        }

        return response()->download(storage_path("app/public/{$project->file_url}"));
    }

    /**
     * @OA\Delete(
     *     path="/api/projects/{id}",
     *     tags={"Projects"},
     *     summary="Supprimer un projet",
     *     description="Seuls les Admins peuvent supprimer n'importe quel projet. Les Participants peuvent supprimer leurs propres projets uniquement si le statut est 'Soumis'.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Projet supprimé"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Projet non trouvé")
     * )
     */
    public function destroy($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return $this->notFoundResponse('Projet non trouvé');
        }

        $this->authorize('delete', $project);

        if ($project->file_url && Storage::disk('public')->exists($project->file_url)) {
            Storage::disk('public')->delete($project->file_url);
        }

        $project->delete();

        return $this->successResponse(null, 'Projet supprimé avec succès.');
    }
}
