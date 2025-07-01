<?php
namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MuaProfile;
use App\Models\User;

/**
 * @OA\Get(
 *     path="/api/mua/profile",
 *     summary="Lihat profil MUA (private)",
 *     tags={"Profile"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Profil MUA")
 * )
 *
 * @OA\Put(
 *     path="/api/mua/profile",
 *     summary="Update profil MUA",
 *     tags={"Profile"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="bio", type="string", example="Certified bridal MUA"),
 *             @OA\Property(property="makeup_styles", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="makeup_specializations", type="array", @OA\Items(type="string"))
 *         )
 *     ),
 *     @OA\Response(response=200, description="Profil diperbarui")
 * )
 *
 * @OA\Get(
 *     path="/api/mua/{id}",
 *     summary="Lihat profil publik MUA (portofolio, layanan, review)",
 *     tags={"Profile"},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Profil publik dikembalikan")
 * )
 */

class ProfileController extends Controller
{
    public function show()
    {
        $profile = Auth::user()->muaProfile;
        return response()->json($profile);
    }

    public function update(Request $request)
    {
        $request->validate([
            'bio'                   => 'nullable|string',
            'certification'         => 'nullable|string',
            'service_area'          => 'nullable|string',
            'studio_lat'            => 'nullable|numeric',
            'studio_lng'            => 'nullable|numeric',
            'makeup_styles'         => 'nullable|array',
            'makeup_specializations'=> 'nullable|array',
        ]);

        $profile = Auth::user()->muaProfile;
        $profile->update($request->all());

        return response()->json([
            'message' => 'MUA profile updated',
            'data'    => $profile
        ]);
    }

    public function public($id)
    {
        $user = User::where('id', $id)
            ->where('role', 'mua')
            ->with(['muaProfile', 'services', 'portfolios', 'bookingsAsMua.review'])
            ->firstOrFail();

        return response()->json($user);
    }
}
