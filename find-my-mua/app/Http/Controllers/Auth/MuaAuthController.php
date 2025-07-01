<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MuaProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class MuaAuthController extends Controller
{
    public function register(Request $request)
    {
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
            'role'     => 'mua',
        ]);

        MuaProfile::create([
            'user_id' => $user->id
        ]);

        return response()->json([
            'message' => 'MUA registered successfully',
            'user'    => $user
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->role !== 'mua') {
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
