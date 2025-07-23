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
        try {
            $request->validate([
                'name'     => 'required|string',
                'email'    => 'required|email|unique:users',
                'phone'    => 'nullable|string',
                'password' => 'required|min:6|confirmed'
            ]);

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'phone'    => $request->phone,
                'password' => Hash::make($request->password),
                'role'     => 'customer',
            ]);

            return response()->json([
                    'message'      => 'Customer registered successfully',
                    'user'         => $user
                ], 201);
        } catch (\Throwable $e) {
            \Log::error('Customer REGISTER ERROR', ['error' => $e->getMessage()]);
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
                'user'         => $user
            ]);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

}
