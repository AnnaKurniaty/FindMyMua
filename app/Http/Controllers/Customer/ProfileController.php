<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerProfile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class ProfileController extends Controller
{

    public function show()
    {
        $user = Auth::user();
        $profile = $user->customerProfile;

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
                'skin_tone'          => 'nullable|string',
                'skin_type'          => 'nullable|array',
                'skin_issues'        => 'nullable|string',
                'address'        => 'nullable|string',
                'studio_lat'        => 'nullable|string',
                'studio_lng'        => 'nullable|string',
                'skincare_history'   => 'nullable|string',
                'allergies'          => 'nullable|string',
                'makeup_preferences' => 'nullable|array',
                'profile_photo'      => 'nullable|image|max:2048'
            ]);

            $data = $request->only([
                'skin_tone',
                'skin_type',
                'skin_issues',
                'skincare_history',
                'allergies',
                'makeup_preferences',
                'profile_photo',
                'address',
                'studio_lat',
                'studio_lng'
            ]);

            $data['user_id'] = $user->id;

            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store('public/profile_photos');
                $data['profile_photo'] = \Storage::url($path);
            }

            $jsonFields = [
                'skin_type',
                'makeup_preferences'
            ];

            foreach ($jsonFields as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    // decode untuk validasi data array
                    $decoded = json_decode($data[$field], true);
                    $data[$field] = json_encode($decoded ?? []);
                }
            }

            $profile = CustomerProfile::create($data);

            return response()->json([
                'message' => 'Customer profile created',
                'data' => $profile
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Customer PROFILE STORE ERROR', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to create profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'skin_tone'          => 'nullable|string',
            'skin_type'          => 'nullable|array',
            'skin_issues'        => 'nullable|string',
            'skincare_history'   => 'nullable|string',
            'allergies'          => 'nullable|string',
            'makeup_preferences' => 'nullable|array',
            'profile_photo'      => 'nullable|image|max:2048'
        ]);

        $user = Auth::user();
        $profile = $user->customerProfile;

        if (!$profile) {
            $profile = new CustomerProfile();
            $profile->user_id = $user->id;
        }

        if ($request->hasFile('profile_photo')) {
            if ($profile->profile_photo) {
                Storage::disk('public')->delete($profile->profile_photo);
            }

            $path = $request->file('profile_photo')->store('profiles', 'public');
            $profile->profile_photo = $path;
        }

        $profile->skin_tone           = $request->input('skin_tone');
        $profile->skin_type           = $request->input('skin_type');
        $profile->skin_issues         = $request->input('skin_issues');
        $profile->skincare_history    = $request->input('skincare_history');
        $profile->allergies           = $request->input('allergies');
        $profile->makeup_preferences  = $request->input('makeup_preferences');

        $profile->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'data'    => $profile
        ]);
    }
}
