<?php
namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Get(
 *     path="/api/mua/services",
 *     summary="Lihat semua layanan MUA",
 *     tags={"Service"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="List layanan")
 * )
 *
 * @OA\Post(
 *     path="/api/mua/services",
 *     summary="Buat layanan baru",
 *     tags={"Service"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="Wedding Makeup"),
 *             @OA\Property(property="price", type="number", example=1500000),
 *             @OA\Property(property="duration_minutes", type="integer", example=90),
 *             @OA\Property(property="location_type", type="string", example="home")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Layanan berhasil dibuat")
 * )
 */

class ServiceController extends Controller
{
    public function index()
    {
        $services = Auth::user()->services;
        return response()->json($services);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string',
            'description'         => 'nullable|string',
            'price'               => 'required|numeric|min:0',
            'duration_minutes'    => 'required|integer|min:1',
            'location_type'       => 'required|in:studio,home',
            'cancellation_policy' => 'nullable|string'
        ]);

        $service = Service::create([
            'mua_id'              => Auth::id(),
            'name'                => $request->name,
            'description'         => $request->description,
            'price'               => $request->price,
            'duration_minutes'    => $request->duration_minutes,
            'location_type'       => $request->location_type,
            'cancellation_policy' => $request->cancellation_policy,
        ]);

        return response()->json([
            'message' => 'Service created successfully',
            'service' => $service
        ]);
    }

    public function update(Request $request, $id)
    {
        $service = Service::where('id', $id)->where('mua_id', Auth::id())->firstOrFail();

        $request->validate([
            'name'                => 'required|string',
            'description'         => 'nullable|string',
            'price'               => 'required|numeric|min:0',
            'duration_minutes'    => 'required|integer|min:1',
            'location_type'       => 'required|in:studio,home',
            'cancellation_policy' => 'nullable|string'
        ]);

        $service->update($request->all());

        return response()->json([
            'message' => 'Service updated',
            'service' => $service
        ]);
    }

    public function destroy($id)
    {
        $service = Service::where('id', $id)->where('mua_id', Auth::id())->firstOrFail();
        $service->delete();

        return response()->json(['message' => 'Service deleted']);
    }
}
