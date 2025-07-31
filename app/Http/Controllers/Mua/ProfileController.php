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
            $user = auth()->user();
            $profile = $user->muaProfile;

            if (!$profile) {
                return response()->json(['message' => 'Profile not found'], 404);
            }

            \Log::info('MUA PROFILE UPDATE REQUEST', [
                'all_request_data' => $request->all(),
                'content_type' => $request->header('Content-Type')
            ]);

            $validated = $request->validate([
                'bio' => 'nullable|string',
                'certification' => 'nullable', // Can be string or array
                'service_area' => 'nullable|string',
                'studio_lat' => 'nullable|numeric',
                'studio_lng' => 'nullable|numeric',
                'makeup_styles' => 'nullable', // Can be string or array
                'makeup_specializations' => 'nullable', // Can be string or array
                'skin_type' => 'nullable', // Can be string or array
                'available_days' => 'nullable', // Can be string or array
                'available_start_time' => 'nullable|string',
                'available_end_time' => 'nullable|string',
                'profile_photo' => 'nullable|image|max:2048',
            ]);

            \Log::info('MUA PROFILE UPDATE VALIDATED', [
                'validated_data' => $validated
            ]);

            // Handle JSON fields - the model's casts will handle array to JSON conversion
            $jsonFields = ['certification', 'makeup_styles', 'makeup_specializations', 'skin_type', 'available_days'];
            foreach ($jsonFields as $field) {
                if (isset($validated[$field])) {
                    \Log::info("Processing JSON field: $field", [
                        'original_value' => $validated[$field],
                        'type' => gettype($validated[$field]),
                        'is_string' => is_string($validated[$field]),
                        'is_array' => is_array($validated[$field]),
                    ]);
                    
                    // If it's already an array, keep as is (model will handle JSON conversion)
                    if (is_array($validated[$field])) {
                        \Log::info("Field $field is already an array, keeping as is", ['array' => $validated[$field]]);
                        continue;
                    }
                    
                    // If it's a string, check if it's already JSON
                    if (is_string($validated[$field])) {
                        // Check if it's already a JSON array string
                        if (str_starts_with($validated[$field], '[') || str_starts_with($validated[$field], '"[')) {
                            // Validate that it's proper JSON
                            $decoded = json_decode($validated[$field], true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                \Log::info("Field $field is already valid JSON, keeping as is", ['json' => $validated[$field]]);
                                // Keep as is since it's already JSON
                                continue;
                            } else {
                                // If JSON decode fails, try to parse as comma-separated string
                                $validated[$field] = array_map('trim', explode(',', trim($validated[$field], '[]')));
                                \Log::info("Fallback to comma-separated parsing for $field", ['parsed' => $validated[$field]]);
                            }
                        } else {
                            // If it's not JSON, treat as comma-separated or single value
                            $parsed = array_map('trim', explode(',', $validated[$field]));
                            // Remove empty values
                            $parsed = array_filter($parsed, function($value) {
                                return $value !== '' && $value !== '""';
                            });
                            $validated[$field] = array_values($parsed);
                            \Log::info("Non-JSON treated as comma-separated for $field", ['parsed' => $validated[$field]]);
                        }
                    }
                }
            }

            \Log::info('MUA PROFILE UPDATE PROCESSED', [
                'processed_data' => $validated
            ]);

            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                // Delete old photo if exists
                if ($profile->profile_photo) {
                    Storage::disk('public')->delete('profile_photos/' . $profile->profile_photo);
                }
                $path = $request->file('profile_photo')->store('profile_photos', 'public');
                $validated['profile_photo'] = basename($path);
                \Log::info('Profile photo uploaded', ['path' => $path]);
            }

            // Update profile - the model's $casts will handle array to JSON conversion
            \Log::info('Updating profile with data', ['data' => $validated]);
            $result = $profile->update($validated);
            \Log::info('Profile update result', ['result' => $result]);
            \Log::info('Profile updated successfully in database');

            // Reload the user with profile relationship
            $user->load('muaProfile');

            return response()->json([
                'message' => 'Profile updated successfully',
                'data' => $user
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('MUA PROFILE UPDATE ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
