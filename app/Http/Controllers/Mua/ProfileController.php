<?php

namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="bio", type="string", example="Certified bridal MUA"),
 *             @OA\Property(property="certification", type="string"),
 *             @OA\Property(property="service_area", type="string"),
 *             @OA\Property(property="studio_lat", type="number"),
 *             @OA\Property(property="studio_lng", type="number"),
 *             @OA\Property(property="makeup_styles", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="makeup_specializations", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="skin_type", type="string", description="JSON array"),
 *             @OA\Property(property="available_days", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="available_start_time", type="string", example="08:00:00"),
 *             @OA\Property(property="available_end_time", type="string", example="17:00:00")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Profil diperbarui")
 * )
 *
 * @OA\Post(
 *     path="/api/mua/profile",
 *     summary="Tambahkan profil MUA (hanya pertama kali)",
 *     tags={"Profile"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"bio", "certification"},
 *                 @OA\Property(property="bio", type="string"),
 *                 @OA\Property(property="certification", type="string"),
 *                 @OA\Property(property="service_area", type="string"),
 *                 @OA\Property(property="studio_lat", type="number"),
 *                 @OA\Property(property="studio_lng", type="number"),
 *                 @OA\Property(property="makeup_styles", type="string", description="JSON array"),
 *                 @OA\Property(property="makeup_specializations", type="string", description="JSON array"),
 *                 @OA\Property(property="skin_type", type="string", description="JSON array"),
 *                 @OA\Property(property="available_days", type="string", description="JSON array"),
 *                 @OA\Property(property="available_start_time", type="string", format="time"),
 *                 @OA\Property(property="available_end_time", type="string", format="time"),
 *                 @OA\Property(property="profile_photo", type="file")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=201, description="Profil MUA berhasil dibuat")
 * )
 *
 * @OA\Get(
 *     path="/api/mua/{id}",
 *     summary="Lihat profil publik MUA",
 *     tags={"Profile"},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Profil publik MUA")
 * )
 */

class ProfileController extends Controller
{
    public function show()
    {
        $profile = Auth::user()->muaProfile;
        return response()->json($profile);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'bio' => 'nullable|string',
            'certification' => 'nullable|string',
            'service_area' => 'nullable|string',
            'studio_lat' => 'nullable|numeric',
            'studio_lng' => 'nullable|numeric',
            'makeup_styles' => 'nullable|json',
            'makeup_specializations' => 'nullable|json',
            'skin_type' => 'nullable|json',
            'available_days' => 'nullable|json',
            'available_start_time' => 'nullable|date_format:H:i:s',
            'available_end_time' => 'nullable|date_format:H:i:s',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['profile_photo']);
        $data['user_id'] = $user->id;

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('public/profile_photos');
            $data['profile_photo'] = Storage::url($path);
        }

        foreach (['makeup_styles', 'makeup_specializations', 'available_days', 'skin_type'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }

        $profile = MuaProfile::create($data);

        return response()->json([
            'message' => 'MUA profile created',
            'data' => $profile
        ], 201);
    }

    public function update(Request $request)
    {
        $request->validate([
            'bio' => 'nullable|string',
            'certification' => 'nullable|string',
            'service_area' => 'nullable|string',
            'studio_lat' => 'nullable|numeric',
            'studio_lng' => 'nullable|numeric',
            'makeup_styles' => 'nullable|array',
            'makeup_specializations' => 'nullable|array',
            'skin_type' => 'nullable|array',
            'available_days' => 'nullable|array',
            'available_start_time' => 'nullable|date_format:H:i:s',
            'available_end_time' => 'nullable|date_format:H:i:s',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        $profile = Auth::user()->muaProfile;
        $data = $request->except(['profile_photo']);

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('public/profile_photos');
            $data['profile_photo'] = Storage::url($path);
        }

        $profile->update($data);

        return response()->json([
            'message' => 'MUA profile updated',
            'data' => $profile
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
