<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerProfile;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        $profile = Auth::user()->customerProfile;
        return response()->json($profile);
    }

    public function store(Request $request)
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

        $user = Auth::user();

        if ($user->customerProfile) {
            return response()->json([
                'message' => 'Profile already exists',
            ], 409); // Conflict
        }

        $profile = new \App\Models\CustomerProfile();
        $profile->user_id           = $user->id;
        $profile->skin_tone         = $request->input('skin_tone');
        $profile->skin_type         = $request->input('skin_type');
        $profile->skin_issues       = $request->input('skin_issues');
        $profile->skincare_history  = $request->input('skincare_history');
        $profile->allergies         = $request->input('allergies');
        $profile->makeup_preferences= $request->input('makeup_preferences');

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profiles', 'public');
            $profile->profile_photo = $path;
        }

        $profile->save();

        return response()->json([
            'message' => 'Profile created successfully',
            'data'    => $profile
        ], 201);
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
