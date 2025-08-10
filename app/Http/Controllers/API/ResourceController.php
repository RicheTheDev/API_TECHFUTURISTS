<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * @OA\Tag(name="Resources", description="Gestion des ressources")
 *
 * @OA\Schema(
 *     schema="Resource",
 *     type="object",
 *     title="Resource",
 *     required={"id", "title", "file_url", "file_type", "uploaded_by"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Guide Laravel"),
 *     @OA\Property(property="description", type="string", example="Un guide complet sur Laravel 10"),
 *     @OA\Property(property="file_url", type="string", example="uploads/abc123.pdf"),
 *     @OA\Property(property="file_type", type="string", example="pdf"),
 *     @OA\Property(property="uploaded_by", type="integer", example=2),
 *     @OA\Property(property="is_published", type="boolean", example=true),
 *     @OA\Property(property="download_count", type="integer", example=15),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-08T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-10T15:00:00Z")
 * )
 */
class ResourceController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/resources",
     *     tags={"Resources"},
     *     summary="Récupère toutes les ressources avec statistiques (Admin uniquement)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des ressources avec statistiques",
     *         @OA\JsonContent(
     *             @OA\Property(property="resources", type="array", @OA\Items(ref="#/components/schemas/Resource")),
     *             @OA\Property(property="total_resources", type="integer", example=100),
     *             @OA\Property(property="published_resources", type="integer", example=80),
     *             @OA\Property(property="total_downloads", type="integer", example=2500)
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function index()
    {
        try {
            $this->authorize('viewAny', Resource::class);

            $resources = Resource::all();
            $totalResources = $resources->count();
            $publishedResources = $resources->where('is_published', true)->count();
            $totalDownloads = $resources->sum('download_count');

            return $this->successResponse([
                'resources' => $resources,
                'total_resources' => $totalResources,
                'published_resources' => $publishedResources,
                'total_downloads' => $totalDownloads,
            ], 'Liste des ressources');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse("Vous n'avez pas la permission d'accéder à cette ressource");
        } catch (\Exception $e) {
            return $this->serverErrorResponse("Erreur serveur");
        }
    }

    /**
     * @OA\Post(
     *     path="/api/resources",
     *     tags={"Resources"},
     *     summary="Crée une nouvelle ressource (Admin uniquement)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title","file_type","file"},
     *                 @OA\Property(property="title", type="string", maxLength=255, example="Guide Laravel"),
     *                 @OA\Property(property="description", type="string", example="Un guide complet sur Laravel 10"),
     *                 @OA\Property(property="file_type", type="string", maxLength=50, example="pdf"),
     *                 @OA\Property(property="file", type="string", format="binary"),
     *                 @OA\Property(property="is_published", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Ressource créée avec succès", @OA\JsonContent(ref="#/components/schemas/Resource")),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', Resource::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file_type' => 'required|string|max:50',
            'file' => 'required|file',
            'is_published' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors(), 'Erreur de validation');
        }

        try {
            $file = $request->file('file');
            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads', $filename, 'public');

            $resource = Resource::create([
                'title' => $request->title,
                'description' => $request->description,
                'file_url' => $path,
                'file_type' => $request->file_type,
                'uploaded_by' => auth()->id(),
                'is_published' => $request->boolean('is_published', false),
                'download_count' => 0,
            ]);

            return $this->createdResponse($resource, 'Ressource créée avec succès');
        } catch (\Exception $e) {
            return $this->serverErrorResponse("Erreur lors de la création de la ressource");
        }
    }

    /**
     * @OA\Put(
     *     path="/api/resources/{id}",
     *     tags={"Resources"},
     *     summary="Met à jour une ressource (Admin uniquement)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string", maxLength=255, example="Guide Laravel mis à jour"),
     *                 @OA\Property(property="description", type="string", example="Mise à jour du guide avec les dernières fonctionnalités"),
     *                 @OA\Property(property="file_type", type="string", maxLength=50, example="pdf"),
     *                 @OA\Property(property="file", type="string", format="binary"),
     *                 @OA\Property(property="is_published", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Ressource mise à jour avec succès", @OA\JsonContent(ref="#/components/schemas/Resource")),
     *     @OA\Response(response=404, description="Ressource non trouvée"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $resource = Resource::findOrFail($id);
            $this->authorize('update', $resource);

            $validator = Validator::make($request->all(), [
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'file_type' => 'nullable|string|max:50',
                'file' => 'nullable|file',
                'is_published' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors(), 'Erreur de validation');
            }

            if ($request->hasFile('file')) {
                if ($resource->file_url && Storage::disk('public')->exists($resource->file_url)) {
                    Storage::disk('public')->delete($resource->file_url);
                }

                $file = $request->file('file');
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('uploads', $filename, 'public');
                $resource->file_url = $path;
            }

            $resource->update($request->only(['title', 'description', 'file_type', 'is_published']));

            return $this->updatedResponse($resource, 'Ressource mise à jour avec succès');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Ressource non trouvée');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse("Vous n'avez pas la permission de modifier cette ressource");
        } catch (\Exception $e) {
            return $this->serverErrorResponse("Erreur serveur");
        }
    }

    /**
     * @OA\Get(
     *     path="/api/resources/download/{id}",
     *     tags={"Resources"},
     *     summary="Télécharge un fichier ressource",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Fichier téléchargé"),
     *     @OA\Response(response=404, description="Ressource ou fichier non trouvé"),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function download($id)
    {
        try {
            $resource = Resource::findOrFail($id);
            $this->authorize('view', $resource);

            if (!$resource->file_url || !Storage::disk('public')->exists($resource->file_url)) {
                return $this->notFoundResponse("Fichier non trouvé sur le serveur");
            }

            $resource->increment('download_count');

            return response()->download(storage_path('app/public/' . $resource->file_url));
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Ressource non trouvée');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse("Vous n'avez pas la permission de télécharger cette ressource");
        } catch (\Exception $e) {
            return $this->serverErrorResponse("Erreur serveur");
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/resources/{id}",
     *     tags={"Resources"},
     *     summary="Supprime une ressource (Admin uniquement)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Ressource supprimée avec succès"),
     *     @OA\Response(response=404, description="Ressource non trouvée"),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function destroy($id)
    {
        try {
            $resource = Resource::findOrFail($id);
            $this->authorize('delete', $resource);

            if ($resource->file_url && Storage::disk('public')->exists($resource->file_url)) {
                Storage::disk('public')->delete($resource->file_url);
            }

            $resource->delete();

            return $this->deletedResponse('Ressource supprimée avec succès');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Ressource non trouvée');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse("Vous n'avez pas la permission de supprimer cette ressource");
        } catch (\Exception $e) {
            return $this->serverErrorResponse("Erreur serveur");
        }
    }
}