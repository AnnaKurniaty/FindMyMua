<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;

/**
 * @OA\Get(
 *     path="/api/customer/wishlist",
 *     summary="Lihat daftar MUA favorit customer",
 *     tags={"Wishlist"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Wishlist dikembalikan")
 * )
 *
 * @OA\Post(
 *     path="/api/customer/wishlist",
 *     summary="Tambahkan MUA ke wishlist",
 *     tags={"Wishlist"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="mua_id", type="integer", example=10)
 *         )
 *     ),
 *     @OA\Response(response=201, description="Ditambahkan")
 * )
 *
 * @OA\Delete(
 *     path="/api/customer/wishlist/{mua_id}",
 *     summary="Hapus MUA dari wishlist",
 *     tags={"Wishlist"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="mua_id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Dihapus")
 * )
 */

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::with('mua')
            ->where('customer_id', Auth::id())
            ->get();

        return response()->json($wishlists);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mua_id' => 'required|exists:users,id'
        ]);

        $exists = Wishlist::where('customer_id', Auth::id())
            ->where('mua_id', $request->mua_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'MUA already in wishlist'], 409);
        }

        $wishlist = Wishlist::create([
            'customer_id' => Auth::id(),
            'mua_id'      => $request->mua_id,
        ]);

        return response()->json([
            'message' => 'Added to wishlist',
            'data'    => $wishlist
        ]);
    }

    public function destroy($mua_id)
    {
        Wishlist::where('customer_id', Auth::id())
            ->where('mua_id', $mua_id)
            ->delete();

        return response()->json(['message' => 'Removed from wishlist']);
    }
}
