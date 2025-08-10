<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\RoleEnum;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * @OA\Tag(name="Users", description="Gestion des utilisateurs")
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"id","first_name","last_name","email","role","is_verified"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="first_name", type="string", example="Ulrich"),
 *     @OA\Property(property="last_name", type="string", example="Assogba"),
 *     @OA\Property(property="email", type="string", format="email", example="ulrich@example.com"),
 *     @OA\Property(property="role", type="string", enum={"Participant","Mentor","Admin"}, example="Participant"),
 *     @OA\Property(property="is_verified", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-08T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-10T15:00:00Z")
 * )
 */
class UserController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/participants",
     *     tags={"Users"},
     *     summary="Get all users (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index()
    {
        try {
            $this->authorize('viewAny', User::class);
            $users = User::all();
            return $this->successResponse($users, 'Liste des utilisateurs');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse("Vous n'avez pas la permission d'accéder à cette ressource");
        } catch (\Throwable $e) {
            return $this->serverErrorResponse('Erreur serveur');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     tags={"Users"},
     *     summary="Get authenticated user information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user data",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(Request $request)
    {
        try {
            return $this->successResponse(
                $request->user(),
                'Informations de l’utilisateur connecté'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Erreur serveur');
        }
    }


    /**
     * @OA\Get(
     *     path="/api/participants/{id}",
     *     tags={"Users"},
     *     summary="Get user by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="User found",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            $this->authorize('view', $user);
            return $this->successResponse($user, 'Utilisateur trouvé');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Utilisateur non trouvé');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse("Vous n'avez pas la permission de voir cet utilisateur");
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Erreur serveur');
        }
    }

    /**
     * @OA\Put(
     *     path="/api/participants/{id}",
     *     tags={"Users"},
     *     summary="Update user details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="Ulrich"),
     *             @OA\Property(property="last_name", type="string", example="Assogba"),
     *             @OA\Property(property="email", type="string", format="email", example="ulrich@example.com"),
     *             @OA\Property(property="role", type="string", enum={"Participant", "Mentor", "Admin"}, example="Participant")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $this->authorize('update', $user);

            $validator = Validator::make($request->all(), [
                'first_name' => 'nullable|string|max:255',
                'last_name'  => 'nullable|string|max:255',
                'email'      => "nullable|email|unique:users,email,{$id}",
                'role'       => 'nullable|string|in:' . implode(',', RoleEnum::getValues()),
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors(), 'Erreur de validation');
            }

            $user->update($request->only(['first_name', 'last_name', 'email', 'role']));

            return $this->updatedResponse($user, 'Utilisateur mis à jour');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Utilisateur non trouvé');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse("Vous n'avez pas la permission de modifier cet utilisateur");
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Erreur serveur');
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/participants/{id}",
     *     tags={"Users"},
     *     summary="Delete user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User deleted"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $this->authorize('delete', $user);
            $user->delete();

            return $this->deletedResponse('Utilisateur supprimé');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Utilisateur non trouvé');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse("Vous n'avez pas la permission de supprimer cet utilisateur");
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Erreur serveur');
        }
    }
}
