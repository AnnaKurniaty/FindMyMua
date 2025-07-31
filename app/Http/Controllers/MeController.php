<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MeController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'phone', 'role', 'created_at')
            ->with([
                'customerProfile',
                'muaProfile',
                'services',
                'portfolios',
                'bookingsAsCustomer',
                'bookingsAsMua',
                'wishlists',
            ])
            ->get();

        return response()->json($users);
    }

    public function me(): JsonResponse
    {
        $user = auth()->user()->load([
            'customerProfile',
            'muaProfile',
            'services',
            'portfolios',
            'bookingsAsCustomer',
            'bookingsAsMua',
            'wishlists',
        ]);

        return response()->json($user);
    }

    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        \Log::info('User update input', $request->all());

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $isDirty = false;

        if ($request->has('name')) {
            if ($user->name !== $request->input('name')) {
                $user->name = $request->input('name');
                $isDirty = true;
                \Log::info('User name changed', ['old' => $user->name, 'new' => $request->input('name')]);
            }
        }
        if ($request->has('email')) {
            if ($user->email !== $request->input('email')) {
                $user->email = $request->input('email');
                $isDirty = true;
                \Log::info('User email changed', ['old' => $user->email, 'new' => $request->input('email')]);
            }
        }
        if ($request->has('phone')) {
            if ($user->phone !== $request->input('phone')) {
                $user->phone = $request->input('phone');
                $isDirty = true;
                \Log::info('User phone changed', ['old' => $user->phone, 'new' => $request->input('phone')]);
            }
        }
        if ($request->has('address')) {
            if ($user->address !== $request->input('address')) {
                $user->address = $request->input('address');
                $isDirty = true;
                \Log::info('User address changed', ['old' => $user->address, 'new' => $request->input('address')]);
            }
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
            $isDirty = true;
            \Log::info('User password changed');
        }

        if ($isDirty) {
            $user->save();
            \Log::info('User saved with changes');
        } else {
            \Log::info('No changes detected on user');
        }

        \Log::info('User after save', $user->toArray());

        return response()->json([
            'message' => 'User profile updated successfully',
            'data' => $user,
        ]);
    }
}
