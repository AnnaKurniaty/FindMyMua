<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MuaProfile;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $style     = $request->query('style');
        $spec      = $request->query('specialization');
        $minPrice  = $request->query('min_price');
        $maxPrice  = $request->query('max_price');

        $query = MuaProfile::query()
            ->with(['user', 'user.services', 'user.portfolios']);

        if ($style) {
            $query->whereJsonContains('makeup_styles', $style);
        }

        if ($spec) {
            $query->whereJsonContains('makeup_specializations', $spec);
        }

        if ($minPrice || $maxPrice) {
            $query->whereHas('user.services', function ($q) use ($minPrice, $maxPrice) {
                if ($minPrice) $q->where('price', '>=', $minPrice);
                if ($maxPrice) $q->where('price', '<=', $maxPrice);
            });
        }

        $results = $query->get();

        return response()->json($results);
    }
}
