<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Enums\QuestionTypeEnum;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Questions",
 *     description="Gestion des questions liées aux tests"
 * )
 *
 * @OA\Schema(
 *     schema="Question",
 *     type="object",
 *     title="Question",
 *     description="Représentation d'une question attachée à un test",
 *     required={"id","type","test_id","created_at","updated_at"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="text", type="string", nullable=true, example="Quelle est la capitale de la France ?"),
 *     @OA\Property(property="type", type="string", enum={"QCM","Ouvert"}, example="QCM"),
 *     @OA\Property(
 *         property="options",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(type="string", example="Paris")
 *     ),
 *     @OA\Property(property="correct_answer", type="string", nullable=true, example="Paris"),
 *     @OA\Property(property="file_path", type="string", nullable=true, example="uploads/questions/abc123.pdf"),
 *     @OA\Property(property="file_type", type="string", nullable=true, example="pdf"),
 *     @OA\Property(property="test_id", type="integer", example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-14T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-14T12:00:00Z")
 * )
 */
class QuestionController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/questions",
     *     tags={"Questions"},
     *     summary="Liste toutes les questions (tous rôles)",
     *     description="Retourne la liste de toutes les questions accessibles. Tous les utilisateurs authentifiés peuvent consulter.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des questions",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Question")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $this->authorize('viewAny', Question::class);

        $questions = Question::query()->latest()->get();

        return $this->successResponse($questions);
    }

    /**
     * @OA\Get(
     *     path="/api/questions/{id}",
     *     tags={"Questions"},
     *     summary="Détail d'une question (tous rôles)",
     *     description="Retourne le détail d'une question. Tous les utilisateurs authentifiés peuvent consulter.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la question", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Question trouvée", @OA\JsonContent(ref="#/components/schemas/Question")),
     *     @OA\Response(response=404, description="Question non trouvée")
     * )
     */
    public function show($id)
    {
        $question = Question::find($id);
        if (!$question) {
            return $this->notFoundResponse('Question non trouvée.');
        }

        $this->authorize('view', $question);

        return $this->successResponse($question);
    }

    /**
     * @OA\Post(
     *     path="/api/questions",
     *     tags={"Questions"},
     *     summary="Créer une question (Admin uniquement)",
     *     description="Seuls les Admins peuvent créer. Supporte l'upload de fichier (multipart/form-data).",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"type","test_id"},
     *                 @OA\Property(property="text", type="string", nullable=true),
     *                 @OA\Property(property="type", type="string", enum={"QCM","Ouvert"}),
     *                 @OA\Property(property="options", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="correct_answer", type="string", nullable=true),
     *                 @OA\Property(property="file", type="string", format="binary", nullable=true),
     *                 @OA\Property(property="test_id", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Question créée", @OA\JsonContent(ref="#/components/schemas/Question")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', Question::class);

        $validator = Validator::make($request->all(), [
            'text' => 'nullable|string',
            'type' => 'required|string|in:' . implode(',', QuestionTypeEnum::getValues()),
            'options' => 'nullable|array',
            'options.*' => 'string|max:255',
            'correct_answer' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240', // 10 Mo
            'test_id' => 'required|exists:tests,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $data = $validator->validated();

        // Upload fichier si fourni
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/questions', $filename, 'public');

            $data['file_path'] = $path;
            $data['file_type'] = $file->getClientOriginalExtension();
        }

        $question = Question::create($data);

        return $this->createdResponse($question, 'Question créée avec succès.');
    }

    /**
     * @OA\Post(
     *     path="/api/questions/{id}",
     *     tags={"Questions"},
     *     summary="Mettre à jour une question (Admin uniquement)",
     *     description="Seuls les Admins peuvent modifier. Supporte l'upload d'un nouveau fichier (multipart/form-data).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la question", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="text", type="string", nullable=true),
     *                 @OA\Property(property="type", type="string", enum={"QCM","Ouvert"}),
     *                 @OA\Property(property="options", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="correct_answer", type="string", nullable=true),
     *                 @OA\Property(property="file", type="string", format="binary", nullable=true),
     *                 @OA\Property(property="test_id", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Question mise à jour", @OA\JsonContent(ref="#/components/schemas/Question")),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Question non trouvée"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
     public function update(Request $request, $id)
    {
        $question = Question::find($id);
        if (!$question) {
            return $this->notFoundResponse('Question non trouvée.');
        }

        $this->authorize('update', $question);

        $validator = Validator::make($request->all(), [
            'text' => 'nullable|string',
            'type' => 'nullable|string|in:' . implode(',', QuestionTypeEnum::getValues()),
            'options' => 'nullable|array',
            'options.*' => 'string|max:255',
            'correct_answer' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            'test_id' => 'nullable|exists:tests,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $data = $validator->validated();

        // Remplacement du fichier si un nouveau est fourni
        if ($request->hasFile('file')) {
            if ($question->file_path && Storage::disk('public')->exists($question->file_path)) {
                Storage::disk('public')->delete($question->file_path);
            }
            $file = $request->file('file');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/questions', $filename, 'public');

            $data['file_path'] = $path;
            $data['file_type'] = $file->getClientOriginalExtension();
        }

        $question->fill($data)->save();

        return $this->successResponse($question, 'Question mise à jour avec succès.');
    }

    /**
     * @OA\Delete(
     *     path="/api/questions/{id}",
     *     tags={"Questions"},
     *     summary="Supprimer une question (Admin uniquement)",
     *     description="Seuls les Admins peuvent supprimer une question. Supprime aussi le fichier associé s'il existe.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la question", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Question supprimée"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Question non trouvée")
     * )
     */
    public function destroy($id)
    {
        $question = Question::find($id);
        if (!$question) {
            return $this->notFoundResponse('Question non trouvée.');
        }

        $this->authorize('delete', $question);

        if ($question->file_path && Storage::disk('public')->exists($question->file_path)) {
            Storage::disk('public')->delete($question->file_path);
        }

        $question->delete();

        return $this->successResponse(null, 'Question supprimée avec succès.');
    }

    /**
     * @OA\Get(
     *     path="/api/questions/{id}/download",
     *     tags={"Questions"},
     *     summary="Télécharger le fichier d'une question (tous rôles)",
     *     description="Tout utilisateur authentifié peut télécharger le fichier associé à une question s'il existe.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID de la question", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Fichier téléchargé"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Fichier ou question non trouvé")
     * )
     */
    public function download($id)
    {
        $question = Question::find($id);
        if (!$question) {
            return $this->notFoundResponse('Question non trouvée.');
        }

        // Autorisation basée sur la lecture (tout utilisateur peut voir/télécharger)
        $this->authorize('view', $question);

        if (!$question->file_path || !Storage::disk('public')->exists($question->file_path)) {
            return $this->notFoundResponse('Fichier introuvable.');
        }

        return response()->download(storage_path("app/public/{$question->file_path}"));
    }
}
