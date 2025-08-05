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

    public function publicProfile($id)
    {
        $user = User::with('muaProfile')->find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        if (!$user->muaProfile) {
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
                'certification' => 'nullable', // Can be string or array
                'service_area' => 'nullable|string',
                'studio_lat' => 'nullable|numeric',
                'studio_lng' => 'nullable|numeric',
                'makeup_styles' => 'nullable', // Can be string or array
                'makeup_specializations' => 'nullable', // Can be string or array
                'skin_type' => 'nullable', // Can be string or array
                'available_days' => 'nullable', // Can be string or array
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
                if (isset($data[$field])) {
                    // If it's already an array, convert to JSON
                    if (is_array($data[$field])) {
                        $data[$field] = json_encode($data[$field]);
                    }
                    // If it's a string, try to decode it as JSON, if that fails keep as is
                    elseif (is_string($data[$field])) {
                        $decoded = json_decode($data[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $data[$field] = json_encode($decoded);
                        }
                    }
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
        try {
            $user = Auth::user();
            $profile = $user->muaProfile;

            if (!$profile) {
                return response()->json(['message' => 'Profile not found'], 404);
            }

            $validated = $request->validate([
                'bio' => 'nullable|string',
                'certification' => 'nullable',
                'service_area' => 'nullable|string',
                'studio_lat' => 'nullable|numeric',
                'studio_lng' => 'nullable|numeric',
                'makeup_styles' => 'nullable',
                'makeup_specializations' => 'nullable',
                'skin_type' => 'nullable',
                'available_days' => 'nullable',
                'available_start_time' => 'nullable|string',
                'available_end_time' => 'nullable|string',
                'profile_photo' => 'nullable|image|max:2048'
            ]);

            // Handle array fields
            $jsonFields = ['certification', 'makeup_styles', 'makeup_specializations', 'skin_type', 'available_days'];
            foreach ($jsonFields as $field) {
                if (isset($validated[$field])) {
                    if (is_string($validated[$field])) {
                        $parsed = json_decode($validated[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $validated[$field] = $parsed;
                        } else {
                            $parsed = array_filter(array_map('trim', explode(',', trim($validated[$field], '[]'))));
                            $validated[$field] = array_values($parsed);
                        }
                    }
                }
            }

            // ✅ Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                if (!empty($profile->getRawOriginal('profile_photo'))) {
                    Storage::disk('public')->delete('profile_photos/' . $profile->getRawOriginal('profile_photo'));
                }

                $path = $request->file('profile_photo')->store('profile_photos', 'public');
                $validated['profile_photo'] = basename($path);
            }

            $profile->update($validated);
            $user->load('muaProfile');

            return response()->json([
                'message' => 'Profile updated successfully',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
