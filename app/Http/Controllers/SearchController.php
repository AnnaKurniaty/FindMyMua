<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MuaProfile;
use App\Models\User;

/**
 * @OA\Get(
 *     path="/api/mua/search",
 *     summary="Cari MUA berdasarkan gaya, spesialisasi, harga",
 *     tags={"Search"},
 *     @OA\Parameter(name="style", in="query", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="specialization", in="query", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="min_price", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="max_price", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Hasil pencarian")
 * )
 */

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $style     = $request->query('style'); // natural, bold
        $spec      = $request->query('specialization'); // bridal
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
