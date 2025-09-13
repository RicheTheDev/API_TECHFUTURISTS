<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserTestResult;
use App\Enums\RoleEnum;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Policies\UserTestResultPolicy;

/**
 * @OA\Tag(
 *     name="UserTestResults",
 *     description="Gestion des résultats de tests utilisateurs"
 * )
 *
 * @OA\Schema(
 *     schema="UserTestResult",
 *     type="object",
 *     title="UserTestResult",
 *     required={"id","score","user_id","test_id","completed_at"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="score", type="number", format="float", example=85.5),
 *     @OA\Property(property="file_path", type="string", nullable=true, example="uploads/results/abc123.pdf"),
 *     @OA\Property(property="file_type", type="string", nullable=true, example="pdf"),
 *     @OA\Property(property="user_id", type="integer", example=42),
 *     @OA\Property(property="test_id", type="integer", example=5),
 *     @OA\Property(property="completed_at", type="string", format="date-time", example="2025-08-14T15:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class UserTestResultController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/user-test-results",
     *     tags={"UserTestResults"},
     *     summary="Liste des résultats accessibles à l'utilisateur",
     *     description="Admin voit tous les résultats, les participants voient uniquement les leurs",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste des résultats", @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/UserTestResult")
     *     ))
     * )
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role === RoleEnum::Admin) {
            $results = UserTestResult::all();
        } else {
            $results = UserTestResult::where('user_id', $user->id)->get();
        }

        return $this->successResponse($results);
    }

    /**
     * @OA\Post(
     *     path="/api/user-test-results",
     *     tags={"UserTestResults"},
     *     summary="Créer un nouveau résultat (Admin seulement)",
     *     description="Admin peut créer un résultat et uploader un fichier associé",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"score","user_id","test_id"},
     *                 @OA\Property(property="score", type="number", format="float"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="test_id", type="integer"),
     *                 @OA\Property(property="file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Résultat créé", @OA\JsonContent(ref="#/components/schemas/UserTestResult")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', UserTestResult::class);

        $validator = Validator::make($request->all(), [
            'score' => 'required|numeric',
            'user_id' => 'required|integer|exists:users,id',
            'test_id' => 'required|integer|exists:tests,id',
            'file' => 'nullable|file',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $filePath = null;
        $fileType = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('uploads/results', $filename, 'public');
            $fileType = $file->getClientOriginalExtension();
        }

        $result = UserTestResult::create([
            'score' => $request->score,
            'user_id' => $request->user_id,
            'test_id' => $request->test_id,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'completed_at' => now(),
        ]);

        return $this->createdResponse($result, 'Résultat créé avec succès.');
    }

    /**
     * @OA\Put(
     *     path="/api/user-test-results/{id}",
     *     tags={"UserTestResults"},
     *     summary="Mettre à jour un résultat (Admin seulement)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="score", type="number", format="float"),
     *                 @OA\Property(property="file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Résultat mis à jour", @OA\JsonContent(ref="#/components/schemas/UserTestResult")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Résultat non trouvé")
     * )
     */
    public function update(Request $request, $id)
    {
        $result = UserTestResult::find($id);
        if (!$result) {
            return $this->notFoundResponse('Résultat non trouvé.');
        }

        $this->authorize('update', $result);

        $validator = Validator::make($request->all(), [
            'score' => 'nullable|numeric',
            'file' => 'nullable|file',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if ($request->hasFile('file')) {
            if ($result->file_path && Storage::disk('public')->exists($result->file_path)) {
                Storage::disk('public')->delete($result->file_path);
            }
            $file = $request->file('file');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('uploads/results', $filename, 'public');
            $result->file_path = $filePath;
            $result->file_type = $file->getClientOriginalExtension();
        }

        if ($request->has('score')) {
            $result->score = $request->score;
        }

        $result->save();

        return $this->successResponse($result, 'Résultat mis à jour avec succès.');
    }

    /**
     * @OA\Delete(
     *     path="/api/user-test-results/{id}",
     *     tags={"UserTestResults"},
     *     summary="Supprimer un résultat (Admin seulement)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Résultat supprimé"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Résultat non trouvé")
     * )
     */
    public function destroy($id)
    {
        $result = UserTestResult::find($id);
        if (!$result) {
            return $this->notFoundResponse('Résultat non trouvé.');
        }

        $this->authorize('delete', $result);

        if ($result->file_path && Storage::disk('public')->exists($result->file_path)) {
            Storage::disk('public')->delete($result->file_path);
        }

        $result->delete();

        return $this->successResponse(null, 'Résultat supprimé avec succès.');
    }

    /**
     * @OA\Get(
     *     path="/api/user-test-results/{id}/download",
     *     tags={"UserTestResults"},
     *     summary="Télécharger le fichier associé à un résultat",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Fichier téléchargé"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier non trouvé")
     * )
     */
    public function download($id)
    {
        $result = UserTestResult::find($id);
        if (!$result) {
            return $this->notFoundResponse('Résultat non trouvé.');
        }

        $this->authorize('view', $result);

        if (!$result->file_path || !Storage::disk('public')->exists($result->file_path)) {
            return $this->notFoundResponse('Fichier introuvable.');
        }

        return response()->download(storage_path("app/public/{$result->file_path}"));
    }
}
