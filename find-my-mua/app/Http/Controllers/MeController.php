<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Get(
 *     path="/api/me",
 *     summary="Ambil data pengguna yang sedang login (Customer atau MUA)",
 *     tags={"Auth"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Data pengguna",
 *         @OA\JsonContent(
 *             @OA\Property(property="id", type="integer", example=7),
 *             @OA\Property(property="name", type="string", example="Anna"),
 *             @OA\Property(property="email", type="string", example="anna@example.com"),
 *             @OA\Property(property="role", type="string", example="customer"),
 *             @OA\Property(property="profile", type="object")
 *         )
 *     )
 * )
 */

class MeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $user->load([
            'customerProfile',
            'muaProfile',
        ]);

        return response()->json([
            'user' => $user
        ]);
    }
}
