<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp;
use App\Mail\SendOtpMail;
use App\Enums\RoleEnum;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * @OA\Tag(name="Authentication", description="Gestion de l'authentification")
 *
 * @OA\Schema(
 *     schema="Authentication",
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
class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","email","password","password_confirmation"},
     *             @OA\Property(property="first_name", type="string", example="Ulrich"),
     *             @OA\Property(property="last_name", type="string", example="Assogba"),
     *             @OA\Property(property="email", type="string", format="email", example="ulrich@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Password123!")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered", @OA\JsonContent(ref="#/components/schemas/User")),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => 'required|email|unique:users,email',
            'password'   => [
                'required', 'string', 'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/'
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            \DB::beginTransaction();

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'email'      => $request->email,
                'password'   => $request->password,
                'role'       => RoleEnum::Participant,
            ]);

            $otpCode = rand(100000, 999999);

            $otp = Otp::create([
                'email' => $user->email,
                'code'  => $otpCode
            ]);

            try {
                Mail::to($user->email)->send(new SendOtpMail($otpCode, $user->first_name));
            } catch (\Throwable $e) {
                \DB::rollBack();
                return $this->serverErrorResponse("L'envoi de l'email a échoué.", $e->getMessage());
            }

            \DB::commit();

            return $this->createdResponse($user, 'Utilisateur enregistré. Veuillez vérifier votre adresse email.');
        } catch (\Throwable $e) {
            \DB::rollBack();
            return $this->serverErrorResponse("Une erreur est survenue lors de l'enregistrement.", $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Login user and get JWT",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="ulrich@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login success",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        auth()->shouldUse('api'); // forcer le guard JWT

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->unauthorizedResponse('Email ou mot de passe invalide');
        }

        if (!$user->is_verified) {
            return $this->forbiddenResponse('Veuillez vérifier votre adresse email.');
        }

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return $this->unauthorizedResponse('Identifiants invalides');
            }
        } catch (JWTException $e) {
            return $this->serverErrorResponse('Impossible de créer le token', $e->getMessage());
        }

        return $this->successResponse([
            'token' => $token,
            'user'  => $user
        ], 'Connexion réussie');
    }

    /**
     * @OA\Post(
     *     path="/api/verify-email",
     *     tags={"Authentication"},
     *     summary="Verify user email with OTP",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","code"},
     *             @OA\Property(property="email", type="string", format="email", example="ulrich@example.com"),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Email verified"),
     *     @OA\Response(response=400, description="Invalid or expired code")
     * )
     */
    public function verifyEmail(Request $request)
    {
        $otp = Otp::where('email', $request->email)
                  ->where('code', $request->code)
                  ->first();

        if (!$otp || $otp->isExpired()) {
            return $this->validationErrorResponse(null, 'Code invalide ou expiré.', 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->notFoundResponse('Utilisateur introuvable.');
        }

        $user->is_verified = true;
        $user->save();
        $otp->delete();

        return $this->successResponse(null, 'Email vérifié avec succès.');
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Authentication"},
     *     summary="Déconnexion de l'utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide ou manquant"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return $this->successResponse(null, 'Déconnexion réussie.');
        } catch (JWTException $e) {
            return $this->serverErrorResponse('La déconnexion a échoué.', $e->getMessage());
        }
    }
}
