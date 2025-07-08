<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Get(
 *     path="/api/customer/profile",
 *     summary="Lihat profil customer",
 *     tags={"Profile"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Profil customer dikembalikan")
 * )
 *
 * @OA\Put(
 *     path="/api/customer/profile",
 *     summary="Update profil customer",
 *     tags={"Profile"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="skin_tone", type="string", example="sawo matang"),
 *             @OA\Property(property="skin_type", type="string", example="berminyak"),
 *             @OA\Property(property="makeup_preferences", type="string", example="natural, matte")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Profil diperbarui")
 * )
 */
class ProfileController extends Controller
{
    public function show()
    {
        $profile = Auth::user()->customerProfile;
        return response()->json($profile);
    }

    public function update(Request $request)
    {
        $request->validate([
            'skin_tone'          => 'nullable|string',
            'skin_type'          => 'nullable|string',
            'skin_issues'        => 'nullable|array',
            'skincare_history'   => 'nullable|string',
            'allergies'          => 'nullable|string',
            'makeup_preferences' => 'nullable|string',
            'profile_photo'      => 'nullable|image|max:2048'
        ]);

        $profile = Auth::user()->customerProfile;

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profiles', 'public');
            $profile->profile_photo = $path;
        }

        $profile->fill($request->except('profile_photo'));
        $profile->save();

        return response()->json([
            'message' => 'Profile updated',
            'data'    => $profile
        ]);
    }
}
