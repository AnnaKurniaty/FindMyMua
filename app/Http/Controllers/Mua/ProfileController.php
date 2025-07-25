<?php

namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\MuaProfile;
use App\Models\User;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $profile = $user->muaProfile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        return response()->json($user);
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            $request->validate([
                'bio' => 'nullable|string',
                'certification' => 'nullable|json',
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

            $data = $request->only([
                'bio',
                'certification',
                'service_area',
                'studio_lat',
                'studio_lng',
                'makeup_styles',
                'makeup_specializations',
                'skin_type',
                'available_days',
                'available_start_time',
                'available_end_time'
            ]);

            $data['user_id'] = $user->id;

            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store('profile_photos', 'public');
                $data['profile_photo'] = basename($path);
            }

            $jsonFields = [
                'makeup_styles',
                'makeup_specializations',
                'available_days',
                'skin_type',
                'certification'
            ];

            foreach ($jsonFields as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $decoded = json_decode($data[$field], true);
                    $data[$field] = json_encode($decoded ?? []);
                }
            }

            $profile = MuaProfile::create($data);

            return response()->json([
                'message' => 'MUA profile created',
                'data' => $profile
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('MUA PROFILE STORE ERROR', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to create profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $profile = $user->muaProfile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $validated = $request->validate([
            'bio' => 'nullable|string',
            'certification' => 'nullable|string',
            'service_area' => 'nullable|string',
            'makeup_styles' => 'nullable|array',
            'makeup_specializations' => 'nullable|array',
            'skin_type' => 'nullable|array',
            'available_days' => 'nullable|array',
            'available_start_time' => 'nullable|date_format:H:i',
            'available_end_time' => 'nullable|date_format:H:i',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        foreach (['makeup_styles', 'makeup_specializations', 'skin_type', 'available_days'] as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = json_encode($validated[$field]);
            }
        }

        if (!empty($validated['available_start_time'])) {
            $validated['available_start_time'] .= ':00';
        }
        if (!empty($validated['available_end_time'])) {
            $validated['available_end_time'] .= ':00';
        }

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profile_photos', 'public');
            $data['profile_photo'] = basename($path);
        }

        \Log::info('DATA UPDATE:', $validated);

        $profile->update($validated);
        $profile->refresh();

        return response()->json([
            'message' => 'MUA profile updated',
            'data' => $profile->fresh()
        ]);
    }

    public function index($id)
    {
        $user = User::where('id', $id)
            ->where('role', 'mua')
            ->with(['muaProfile', 'services', 'portfolios', 'bookingsAsMua.review'])
            ->firstOrFail();

        return response()->json($user);
    }
}
