<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerProfile;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    protected $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $profile = CustomerProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $validated = $request->validate([
            'address' => 'nullable|string',
            'skin_tone' => 'nullable|string',
            'skin_type' => 'nullable|string',
            'skincare_history' => 'nullable|string',
            'allergies' => 'nullable|string',
            'makeup_preferences' => 'nullable|string',
            'skin_issues' => 'nullable|string',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        // Handle profile photo upload to S3
        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($profile->profile_photo) {
                $this->imageUploadService->deleteImage($profile->profile_photo, 'images/profile_photos');
            }
            
            $filename = $this->imageUploadService->uploadProfilePhoto($request->file('profile_photo'));
            $validated['profile_photo'] = $filename;
        }

        // Handle JSON fields
        $jsonFields = ['skin_type', 'makeup_preferences'];
        foreach ($jsonFields as $field) {
            if (isset($validated[$field])) {
                if (is_string($validated[$field])) {
                    $decoded = json_decode($validated[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $validated[$field] = $decoded;
                    } else {
                        $validated[$field] = array_filter(array_map('trim', explode(',', $validated[$field])));
                    }
                }
            }
        }

        $profile->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile
        ]);
    }

    public function show()
    {
        $user = Auth::user();
        $profile = CustomerProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        // Add S3 URL for profile photo
        if ($profile->profile_photo) {
            $profile->profile_photo_url = $this->imageUploadService->getImageUrl($profile->profile_photo, 'images/profile_photos');
        }

        return response()->json([
            'profile' => $profile
        ]);
    }
}
