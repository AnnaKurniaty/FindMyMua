<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MuaProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class MuaAuthController extends Controller
{
    /**
 * @OA\Post(
 *     path="/api/auth/register/mua",
 *     summary="Registrasi akun MUA",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "email", "password", "password_confirmation"},
 *             @OA\Property(property="name", type="string", example="MUA Rina"),
 *             @OA\Property(property="email", type="string", format="email", example="rina@example.com"),
 *             @OA\Property(property="phone", type="string", example="08123456789"),
 *             @OA\Property(property="password", type="string", example="secret123"),
 *             @OA\Property(property="password_confirmation", type="string", example="secret123")
 *         )
 *     ),
 *     @OA\Response(response=201, description="MUA registered successfully"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users',
            'phone'    => 'nullable|string',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => 'mua',
        ]);

        MuaProfile::create([
            'user_id' => $user->id
        ]);

        return response()->json([
            'message' => 'MUA registered successfully',
            'user'    => $user
        ]);
    }

    /**
 * @OA\Post(
 *     path="/api/auth/login/mua",
 *     summary="Login untuk MUA",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", format="email", example="rina@example.com"),
 *             @OA\Property(property="password", type="string", example="secret123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login berhasil",
 *         @OA\JsonContent(
 *             @OA\Property(property="access_token", type="string"),
 *             @OA\Property(property="token_type", type="string", example="Bearer"),
 *             @OA\Property(property="user", type="object")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Invalid credentials / unauthorized")
 * )
 */

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->role !== 'mua') {
                Auth::logout();
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $user
            ]);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }
}
