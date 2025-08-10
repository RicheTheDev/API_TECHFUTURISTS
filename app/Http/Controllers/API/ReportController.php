<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Enums\ReportStatusEnum;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Reports",
 *     description="Gestion des rapports"
 * )
 *
 * @OA\Schema(
 *     schema="Report",
 *     type="object",
 *     title="Report",
 *     required={"id","title","file_url","file_type","submitted_by","submitted_at","status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Rapport d'avancement"),
 *     @OA\Property(property="description", type="string", example="Description du rapport"),
 *     @OA\Property(property="file_url", type="string", example="uploads/reports/abc123.pdf"),
 *     @OA\Property(property="file_type", type="string", example="pdf"),
 *     @OA\Property(property="feedback", type="string", nullable=true, example="Très bon travail"),
 *     @OA\Property(property="submission_deadline", type="string", format="date", nullable=true, example="2025-12-31"),
 *     @OA\Property(property="submitted_by", type="integer", example=42),
 *     @OA\Property(property="submitted_at", type="string", format="date-time", example="2025-08-08T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-09T12:00:00Z"),
 *     @OA\Property(property="status", type="string", enum={"Soumis", "En Revue", "Approuvé", "Rejeté"}, example="Soumis")
 * )
 */
class ReportController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/reports",
     *     tags={"Reports"},
     *     summary="Liste des rapports avec statistiques (permissions via Policy)",
     *     description="Retourne les rapports accessibles à l'utilisateur selon ses permissions. Les Admins et Mentors voient tous les rapports, les Participants uniquement les leurs.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste des rapports et statistiques", @OA\JsonContent(
     *         @OA\Property(property="reports", type="array", @OA\Items(ref="#/components/schemas/Report")),
     *         @OA\Property(property="total_submitted", type="integer", example=10),
     *         @OA\Property(property="total_approved", type="integer", example=4),
     *         @OA\Property(property="total_in_review", type="integer", example=3),
     *         @OA\Property(property="reports_this_month", type="integer", example=2)
     *     ))
     * )
     */
    public function index()
    {
        $this->authorize('viewAny', Report::class);

        $user = Auth::user();

        if ($user->can('viewAny', Report::class)) {
            $reports = Report::all();
        } else {
            $reports = Report::where('submitted_by', $user->id)->get();
        }

        $total_submitted = $reports->count();
        $total_approved = $reports->where('status', ReportStatusEnum::Approved->value)->count();
        $total_in_review = $reports->where('status', ReportStatusEnum::InReview->value)->count();

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $reports_this_month = Report::whereYear('submitted_at', $currentYear)
            ->whereMonth('submitted_at', $currentMonth)
            ->count();

        return $this->successResponse([
            'reports' => $reports,
            'total_submitted' => $total_submitted,
            'total_approved' => $total_approved,
            'total_in_review' => $total_in_review,
            'reports_this_month' => $reports_this_month,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/participant",
     *     tags={"Reports"},
     *     summary="Liste des rapports de l'utilisateur connecté",
     *     description="Si l'utilisateur est Admin ou Mentor, retourne tous les rapports. Sinon, retourne uniquement ceux qu'il a soumis.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste des rapports", @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Report")
     *     ))
     * )
     */
    public function participantReports()
    {
        $this->authorize('viewAny', Report::class);

        $user = Auth::user();

        if ($user->can('viewAny', Report::class)) {
            $reports = Report::all();
        } else {
            $reports = Report::where('submitted_by', $user->id)->get();
        }

        return $this->successResponse($reports);
    }

    /**
     * @OA\Post(
     *     path="/api/reports",
     *     tags={"Reports"},
     *     summary="Créer un nouveau rapport",
     *     description="Seuls les utilisateurs avec le rôle Participant peuvent créer un rapport.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(required={"title","file"}, @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="submission_deadline", type="string", format="date"),
     *                 @OA\Property(property="file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Rapport créé", @OA\JsonContent(ref="#/components/schemas/Report")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', Report::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'submission_deadline' => 'nullable|date',
            'file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $file = $request->file('file');
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads/reports', $filename, 'public');

        $report = Report::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_url' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'submission_deadline' => $request->submission_deadline,
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
            'status' => ReportStatusEnum::Submitted->value,
        ]);

        return $this->createdResponse($report, 'Rapport soumis avec succès.');
    }

    /**
     * @OA\Post(
     *     path="/api/reports/admin/edit/{id}",
     *     tags={"Reports"},
     *     summary="Modifier un rapport (Admin/Mentor)",
     *     description="Seuls les Admins et Mentors peuvent modifier n'importe quel rapport.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="submission_deadline", type="string", format="date"),
     *                 @OA\Property(property="file", type="string", format="binary"),
     *                 @OA\Property(property="feedback", type="string"),
     *                 @OA\Property(property="status", type="string", enum={"Soumis", "En Revue", "Approuvé", "Rejeté"})
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Rapport mis à jour", @OA\JsonContent(ref="#/components/schemas/Report")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Rapport non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function editAdmin(Request $request, $id)
    {
        $report = Report::find($id);
        if (!$report) {
            return $this->notFoundResponse('Rapport non trouvé');
        }

        $this->authorize('update', $report);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'submission_deadline' => 'nullable|date',
            'file' => 'nullable|file',
            'feedback' => 'nullable|string',
            'status' => 'nullable|string|in:' . implode(',', ReportStatusEnum::getValues()),
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if ($request->hasFile('file')) {
            if ($report->file_url && Storage::disk('public')->exists($report->file_url)) {
                Storage::disk('public')->delete($report->file_url);
            }
            $file = $request->file('file');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/reports', $filename, 'public');
            $report->file_url = $path;
            $report->file_type = $file->getClientOriginalExtension();
        }

        $report->fill($request->only(['title', 'description', 'submission_deadline', 'feedback', 'status']))->save();

        return $this->successResponse($report, 'Rapport mis à jour avec succès.');
    }

    /**
     * @OA\Post(
     *     path="/api/reports/participant/edit/{id}",
     *     tags={"Reports"},
     *     summary="Modifier son propre rapport",
     *     description="Seuls les Participants peuvent modifier leurs propres rapports, et uniquement si le statut est 'Soumis'.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="submission_deadline", type="string", format="date"),
     *                 @OA\Property(property="file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Rapport mis à jour", @OA\JsonContent(ref="#/components/schemas/Report")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Rapport non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function editParticipant(Request $request, $id)
    {
        $report = Report::find($id);
        if (!$report) {
            return $this->notFoundResponse('Rapport non trouvé');
        }

        $this->authorize('update', $report);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'submission_deadline' => 'nullable|date',
            'file' => 'nullable|file',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if ($request->hasFile('file')) {
            if ($report->file_url && Storage::disk('public')->exists($report->file_url)) {
                Storage::disk('public')->delete($report->file_url);
            }
            $file = $request->file('file');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/reports', $filename, 'public');
            $report->file_url = $path;
            $report->file_type = $file->getClientOriginalExtension();
        }

        $report->fill($request->only(['title', 'description', 'submission_deadline']))->save();

        return $this->successResponse($report, 'Rapport mis à jour avec succès.');
    }

    /**
     * @OA\Get(
     *     path="/api/reports/download/{id}",
     *     tags={"Reports"},
     *     summary="Télécharger un fichier de rapport",
     *     description="L'utilisateur doit avoir l'autorisation de voir le rapport pour pouvoir télécharger son fichier.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Fichier téléchargé"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Rapport ou fichier non trouvé")
     * )
     */
    public function download($id)
    {
        $report = Report::find($id);
        if (!$report) {
            return $this->notFoundResponse('Rapport non trouvé');
        }

        $this->authorize('view', $report);

        if (!$report->file_url || !Storage::disk('public')->exists($report->file_url)) {
            return $this->notFoundResponse('Fichier introuvable.');
        }

        return response()->download(storage_path("app/public/{$report->file_url}"));
    }

    /**
     * @OA\Delete(
     *     path="/api/reports/{id}",
     *     tags={"Reports"},
     *     summary="Supprimer un rapport",
     *     description="Seuls les Admins peuvent supprimer n'importe quel rapport. Les Participants peuvent supprimer leurs propres rapports uniquement si le statut est 'Soumis'.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Rapport supprimé"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Rapport non trouvé")
     * )
     */
    public function destroy($id)
    {
        $report = Report::find($id);
        if (!$report) {
            return $this->notFoundResponse('Rapport non trouvé');
        }

        $this->authorize('delete', $report);

        if ($report->file_url && Storage::disk('public')->exists($report->file_url)) {
            Storage::disk('public')->delete($report->file_url);
        }

        $report->delete();

        return $this->successResponse(null, 'Rapport supprimé avec succès.');
    }
}
