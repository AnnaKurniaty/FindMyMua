<?php
namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use App\Helpers\NotifyHelper;

/**
 * @OA\Get(
 *     path="/api/mua/bookings",
 *     summary="Lihat semua booking untuk MUA",
 *     tags={"Booking"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="List booking untuk MUA")
 * )
 *
 * @OA\Put(
 *     path="/api/mua/bookings/{id}/status",
 *     summary="Ubah status booking oleh MUA",
 *     tags={"Booking"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, description="ID booking", @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="confirmed"),
 *             @OA\Property(property="payment_status", type="string", example="paid")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Status booking diperbarui")
 * )
 */

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Auth::user()->bookingsAsMua()->with(['customer', 'service'])->latest()->get();
        return response()->json($bookings);
    }

    public function updateStatus(Request $request, $id)
    {
        $booking = Booking::where('id', $id)->where('mua_id', Auth::id())->firstOrFail();

        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'payment_status' => 'nullable|in:pending,paid,refunded'
        ]);

        $booking->update([
            'status' => $request->status,
            'payment_status' => $request->payment_status ?? $booking->payment_status,
        ]);

        $message = match ($request->status) {
            'confirmed' => 'Booking Anda telah dikonfirmasi oleh MUA.',
            'cancelled' => 'Booking Anda telah dibatalkan oleh MUA.',
            'completed' => 'Booking Anda telah ditandai selesai oleh MUA.',
            default => null
        };
    
        if ($message) {
            NotifyHelper::notify(
                $booking->customer_id,
                'Status Booking: ' . ucfirst($request->status),
                $message
            );
        }    

        return response()->json([
            'message' => 'Booking updated',
            'booking' => $booking
        ]);
    }
}
