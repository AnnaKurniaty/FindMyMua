<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class CustomerAuthController extends Controller
{
    protected $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    public function register(Request $request)
    {
        try {
            // Handle both JSON and form data
            $name = $request->input('name') ?? $request->name;
            $email = $request->input('email') ?? $request->email;
            $phone = $request->input('phone') ?? $request->phone;
            $password = $request->input('password') ?? $request->password;
            
            $request->validate([
                'name'     => 'required|string',
                'email'    => 'required|email|unique:users',
                'phone'    => 'nullable|string',
                'password' => 'required|min:6|confirmed'
            ]);

            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'password' => Hash::make($password),
                'role'     => 'customer',
            ]);

            // Create customer profile
            $profileData = [
                'user_id' => $user->id,
                'address' => $request->input('address') ?? null,
                'skin_tone' => $request->input('skin_tone') ?? null,
                'skin_type' => $request->input('skin_type') ?? null,
                'skincare_history' => $request->input('skincare_history') ?? null,
                'allergies' => $request->input('allergies') ?? null,
                'makeup_preferences' => $request->input('makeup_preferences') ?? null,
                'skin_issues' => $request->input('skin_issues') ?? null,
            ];

            // Handle profile photo upload to S3
            if ($request->hasFile('profile_photo')) {
                $filename = $this->imageUploadService->uploadProfilePhoto($request->file('profile_photo'));
                $profileData['profile_photo'] = $filename;
            }

            // Create the profile
            $profile = CustomerProfile::create($profileData);

            return response()->json([
                'message' => 'Customer registered successfully',
                'user' => $user,
                'profile' => $profile
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to register Customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->role !== 'customer') {
                Auth::logout();
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user' => $user
            ]);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
        }
    }
