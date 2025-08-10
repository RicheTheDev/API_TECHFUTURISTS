<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Enums\TestTypeEnum;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Tests",
 *     description="Gestion des tests"
 * )
 * 
 * @OA\Schema(
 *     schema="Test",
 *     type="object",
 *     title="Test",
 *     required={"id","title","type","file_url","file_type","created_by","created_at","updated_at"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Test QCM sur PHP"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Description détaillée du test"),
 *     @OA\Property(property="type", type="string", enum={"QCM","Ouvert","Pratique"}, example="QCM"),
 *     @OA\Property(property="file_url", type="string", example="uploads/tests/abc123.pdf"),
 *     @OA\Property(property="file_type", type="string", example="pdf"),
 *     @OA\Property(property="created_by", type="integer", example=42),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-10T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-11T12:00:00Z")
 * )
 */
class TestController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/tests",
     *     tags={"Tests"},
     *     summary="Liste de tous les tests",
     *     description="Retourne la liste complète des tests. Accessible à tous les utilisateurs authentifiés.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des tests",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Test"))
     *     )
     * )
     */
    public function index()
    {
        $this->authorize('viewAny', Test::class);

        $tests = Test::all();

        return $this->successResponse($tests);
    }

    /**
     * @OA\Get(
     *     path="/api/tests/{id}",
     *     tags={"Tests"},
     *     summary="Afficher un test spécifique",
     *     description="Retourne les détails d'un test donné.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du test",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du test",
     *         @OA\JsonContent(ref="#/components/schemas/Test")
     *     ),
     *     @OA\Response(response=404, description="Test non trouvé")
     * )
     */
    public function show($id)
    {
        $test = Test::find($id);

        if (!$test) {
            return $this->notFoundResponse('Test non trouvé.');
        }

        $this->authorize('view', $test);

        return $this->successResponse($test);
    }

    /**
     * @OA\Post(
     *     path="/api/tests",
     *     tags={"Tests"},
     *     summary="Créer un nouveau test",
     *     description="Seuls les administrateurs peuvent créer un test.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "type", "file", "file_type"},
     *                 @OA\Property(property="title", type="string", example="Test QCM PHP"),
     *                 @OA\Property(property="description", type="string", example="Description détaillée"),
     *                 @OA\Property(property="type", type="string", enum={"QCM","Ouvert","Pratique"}, example="QCM"),
     *                 @OA\Property(property="file", type="string", format="binary"),
     *                 @OA\Property(property="file_type", type="string", example="pdf")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Test créé", @OA\JsonContent(ref="#/components/schemas/Test")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', Test::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:' . implode(',', TestTypeEnum::getValues()),
            'file' => 'required|file',
            'file_type' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $file = $request->file('file');
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads/tests', $filename, 'public');

        $test = Test::create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'file_url' => $path,
            'file_type' => $request->file_type,
            'created_by' => Auth::id(),
        ]);

        return $this->createdResponse($test, 'Test créé avec succès.');
    }

    /**
     * @OA\Put(
     *     path="/api/tests/{id}",
     *     tags={"Tests"},
     *     summary="Mettre à jour un test",
     *     description="Seuls les administrateurs peuvent modifier un test.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du test à modifier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string", example="Test QCM modifié"),
     *                 @OA\Property(property="description", type="string", example="Nouvelle description"),
     *                 @OA\Property(property="type", type="string", enum={"QCM","Ouvert","Pratique"}),
     *                 @OA\Property(property="file", type="string", format="binary"),
     *                 @OA\Property(property="file_type", type="string", example="pdf")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Test mis à jour", @OA\JsonContent(ref="#/components/schemas/Test")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Test non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(Request $request, $id)
    {
        $test = Test::find($id);

        if (!$test) {
            return $this->notFoundResponse('Test non trouvé.');
        }

        $this->authorize('update', $test);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|in:' . implode(',', TestTypeEnum::getValues()),
            'file' => 'nullable|file',
            'file_type' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if ($request->hasFile('file')) {
            if ($test->file_url && Storage::disk('public')->exists($test->file_url)) {
                Storage::disk('public')->delete($test->file_url);
            }
            $file = $request->file('file');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/tests', $filename, 'public');
            $test->file_url = $path;
            $test->file_type = $request->file_type ?? $file->getClientOriginalExtension();
        }

        $test->fill($request->only(['title', 'description', 'type', 'file_type']))->save();

        return $this->successResponse($test, 'Test mis à jour avec succès.');
    }

    /**
     * @OA\Delete(
     *     path="/api/tests/{id}",
     *     tags={"Tests"},
     *     summary="Supprimer un test",
     *     description="Seuls les administrateurs peuvent supprimer un test.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du test à supprimer",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Test supprimé"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Test non trouvé")
     * )
     */
    public function destroy($id)
    {
        $test = Test::find($id);

        if (!$test) {
            return $this->notFoundResponse('Test non trouvé.');
        }

        $this->authorize('delete', $test);

        if ($test->file_url && Storage::disk('public')->exists($test->file_url)) {
            Storage::disk('public')->delete($test->file_url);
        }

        $test->delete();

        return $this->successResponse(null, 'Test supprimé avec succès.');
    }

    /**
     * @OA\Get(
     *     path="/api/tests/download/{id}",
     *     tags={"Tests"},
     *     summary="Télécharger un test",
     *     description="Tous les utilisateurs peuvent télécharger un test.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du test à télécharger",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Fichier téléchargé"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Test ou fichier non trouvé")
     * )
     */
    public function download($id)
    {
        $test = Test::find($id);

        if (!$test) {
            return $this->notFoundResponse('Test non trouvé.');
        }

        $this->authorize('download', $test);

        if (!$test->file_url || !Storage::disk('public')->exists($test->file_url)) {
            return $this->notFoundResponse('Fichier introuvable.');
        }

        return response()->download(storage_path("app/public/{$test->file_url}"));
    }
}
