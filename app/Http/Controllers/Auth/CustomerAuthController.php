<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CustomerProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class CustomerAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users',
            'phone'    => 'nullable|string',
            'password' => 'required|min:6|confirmed',

            'skin_tone'            => 'required|string',
            'skin_type'            => 'required|string',
            'skin_issues'          => 'nullable|array',
            'skin_issues.*'        => 'string',
            'skincare_history'     => 'nullable|string',
            'allergies'            => 'nullable|string',
            'makeup_preferences'   => 'nullable|string',
            'profile_photo'        => 'nullable|image|max:2048',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => 'customer',
        ]);

        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('profiles', 'public');
        }

        CustomerProfile::create([
            'user_id'             => $user->id,
            'skin_tone'           => $request->skin_tone,
            'skin_type'           => $request->skin_type,
            'skin_issues'         => $request->skin_issues,
            'skincare_history'    => $request->skincare_history,
            'allergies'           => $request->allergies,
            'makeup_preferences'  => $request->makeup_preferences,
            'profile_photo'       => $photoPath,
        ]);

        return response()->json([
            'message' => 'Customer registered with skin profile',
            'user'    => $user
        ]);
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
                'user'         => $user
            ]);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }
}
