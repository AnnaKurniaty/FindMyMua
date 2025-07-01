<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

/**
 * @OA\Get(
 *     path="/api/mua/{id}/availability",
 *     summary="Lihat slot waktu yang masih tersedia untuk MUA",
 *     tags={"Availability"},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="date", in="query", required=true, @OA\Schema(type="string", format="date")),
 *     @OA\Response(response=200, description="Slot tersedia dikembalikan")
 * )
 */

class AvailabilityController extends Controller
{
    public function show(Request $request, $id)
    {
        $date = $request->query('date');
        if (!$date) {
            return response()->json(['error' => 'Date parameter is required'], 400);
        }

        $mua = User::where('id', $id)->where('role', 'mua')->firstOrFail();

        // Jam kerja default: 08:00–18:00, per 1 jam
        $defaultSlots = collect(range(8, 17))->map(function ($hour) {
            return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
        });

        // Booking aktif di tanggal tersebut
        $bookedTimes = Booking::where('mua_id', $mua->id)
            ->where('date', $date)
            ->pluck('time')
            ->map(fn($t) => substr($t, 0, 5)); // "14:00:00" → "14:00"

        // Filter slot yang masih tersedia
        $availableSlots = $defaultSlots->filter(fn($slot) => !$bookedTimes->contains($slot))->values();

        return response()->json([
            'date' => $date,
            'available_slots' => $availableSlots
        ]);
    }
}
