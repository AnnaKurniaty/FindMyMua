<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CustomerProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class CustomerAuthController extends Controller
{
    /**
 * @OA\Post(
 *     path="/api/auth/register/customer",
 *     summary="Register akun customer",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"name", "email", "password", "password_confirmation", "skin_tone", "skin_type"},
 *                 @OA\Property(property="name", type="string", example="Anna"),
 *                 @OA\Property(property="email", type="string", format="email", example="anna@example.com"),
 *                 @OA\Property(property="phone", type="string", example="08123456789"),
 *                 @OA\Property(property="password", type="string", example="secret123"),
 *                 @OA\Property(property="password_confirmation", type="string", example="secret123"),
 *                 @OA\Property(property="skin_tone", type="string", example="sawo matang"),
 *                 @OA\Property(property="skin_type", type="string", example="berminyak"),
 *                 @OA\Property(property="skin_issues[]", type="array", @OA\Items(type="string"), example={"jerawat", "kering"}),
 *                 @OA\Property(property="skincare_history", type="string", example="The Ordinary, Hada Labo"),
 *                 @OA\Property(property="allergies", type="string", example="Fragrance, Alcohol"),
 *                 @OA\Property(property="makeup_preferences", type="string", example="natural, dewy"),
 *                 @OA\Property(property="profile_photo", type="string", format="binary")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=201, description="Customer registered with skin profile"),
 *     @OA\Response(response=422, description="Validasi gagal")
 * )
 */

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users',
            'phone'    => 'nullable|string',
            'password' => 'required|min:6|confirmed',

            // Profil kulit
            'skin_tone'            => 'required|string',
            'skin_type'            => 'required|string',
            'skin_issues'          => 'nullable|array',
            'skin_issues.*'        => 'string',
            'skincare_history'     => 'nullable|string',
            'allergies'            => 'nullable|string',
            'makeup_preferences'   => 'nullable|string',
            'profile_photo'        => 'nullable|image|max:2048',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => 'customer',
        ]);

        // Upload profil photo jika ada
        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('profiles', 'public');
        }

        CustomerProfile::create([
            'user_id'             => $user->id,
            'skin_tone'           => $request->skin_tone,
            'skin_type'           => $request->skin_type,
            'skin_issues'         => $request->skin_issues,
            'skincare_history'    => $request->skincare_history,
            'allergies'           => $request->allergies,
            'makeup_preferences'  => $request->makeup_preferences,
            'profile_photo'       => $photoPath,
        ]);

        return response()->json([
            'message' => 'Customer registered with skin profile',
            'user'    => $user
        ]);
    }

    /**
 * @OA\Post(
 *     path="/api/auth/login/customer",
 *     summary="Login untuk Customer",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", format="email", example="anna@example.com"),
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
            if ($user->role !== 'customer') {
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
