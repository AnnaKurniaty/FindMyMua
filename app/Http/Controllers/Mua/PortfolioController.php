<?php
namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Get(
 *     path="/api/mua/portfolio",
 *     summary="Lihat portofolio MUA",
 *     tags={"Portfolio"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Daftar portofolio")
 * )
 *
 * @OA\Post(
 *     path="/api/mua/portfolio",
 *     summary="Upload portofolio baru",
 *     tags={"Portfolio"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="image", type="string", format="binary"),
 *                 @OA\Property(property="description", type="string", example="Bridal look 2025")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=201, description="Portofolio ditambahkan")
 * )
 *
 * @OA\Delete(
 *     path="/api/mua/portfolio/{id}",
 *     summary="Hapus portofolio",
 *     tags={"Portfolio"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Dihapus")
 * )
 */
class PortfolioController extends Controller
{
    public function index()
    {
        $items = Auth::user()->portfolios;
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate([
            'media_type' => 'required|in:image,video',
            'file'       => 'required|file|max:10240', // max 10MB
            'caption'    => 'nullable|string'
        ]);

        // Simpan file
        $path = $request->file('file')->store('portfolios', 'public');

        $item = Portfolio::create([
            'mua_id'     => Auth::id(),
            'media_type' => $request->media_type,
            'media_url'  => $path,
            'caption'    => $request->caption
        ]);

        return response()->json([
            'message' => 'Media uploaded',
            'data'    => $item
        ]);
    }

    public function destroy($id)
    {
        $item = Portfolio::where('id', $id)
            ->where('mua_id', Auth::id())
            ->firstOrFail();

        Storage::disk('public')->delete($item->media_url);
        $item->delete();

        return response()->json(['message' => 'Media deleted']);
    }
}
