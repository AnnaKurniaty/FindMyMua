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
        $user->load('customerProfile');

        $profile = $user->customerProfile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $response = $profile->toArray();
        $response['name'] = $user->name;
        $response['email'] = $user->email;
        $response['phone'] = $user->phone;

        return response()->json($response);
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
                'address'
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
        try {
            $user = auth()->user();
            $profile = $user->customerProfile;

            if (!$profile) {
                $profile = new CustomerProfile();
                $profile->user_id = $user->id;
            }

            \Log::info('CUSTOMER PROFILE UPDATE REQUEST', [
                'all_request_data' => $request->all(),
                'content_type' => $request->header('Content-Type')
            ]);

            $validated = $request->validate([
                'skin_tone'          => 'nullable|string',
                'skin_type'          => 'nullable', // Can be string or array
                'skin_issues'        => 'nullable|string',
                'skincare_history'   => 'nullable|string',
                'allergies'          => 'nullable|string',
                'makeup_preferences' => 'nullable', // Can be string or array
                'profile_photo'      => 'nullable|image|max:2048',
                'address'            => 'nullable|string'
            ]);

            \Log::info('CUSTOMER PROFILE UPDATE VALIDATED', [
                'validated_data' => $validated
            ]);

            // Handle JSON fields - the model's casts will handle array to JSON conversion
            $jsonFields = ['skin_type', 'makeup_preferences'];
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

            \Log::info('CUSTOMER PROFILE UPDATE PROCESSED', [
                'processed_data' => $validated
            ]);

            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                // Delete old photo if exists
                if ($profile->profile_photo) {
                    Storage::disk('public')->delete('profiles/' . $profile->profile_photo);
                }
                $path = $request->file('profile_photo')->store('profiles', 'public');
                $validated['profile_photo'] = basename($path);
                \Log::info('Profile photo uploaded', ['path' => $path]);
            }

            // Update profile - the model's $casts will handle array to JSON conversion
            \Log::info('Updating profile with data', ['data' => $validated]);
            $result = $profile->update($validated);
            \Log::info('Profile update result', ['result' => $result]);
            \Log::info('Profile updated successfully in database');

            // Reload the user with profile relationship
            $user->load('customerProfile');

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
            \Log::error('CUSTOMER PROFILE UPDATE ERROR', [
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
