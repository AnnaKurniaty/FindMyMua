<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

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
}
