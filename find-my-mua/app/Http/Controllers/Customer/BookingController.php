<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Helpers\NotifyHelper;

/**
 * @OA\Get(
 *     path="/api/customer/bookings",
 *     summary="Lihat semua booking milik customer",
 *     tags={"Booking"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Daftar booking")
 * )
 *
 * @OA\Post(
 *     path="/api/customer/bookings",
 *     summary="Buat booking baru",
 *     tags={"Booking"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="mua_id", type="integer", example=5),
 *             @OA\Property(property="service_id", type="integer", example=3),
 *             @OA\Property(property="date", type="string", format="date", example="2025-07-01"),
 *             @OA\Property(property="time", type="string", example="14:00"),
 *             @OA\Property(property="payment_method", type="string", example="transfer")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Booking berhasil dibuat")
 * )
 */

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Auth::user()->bookingsAsCustomer()->with(['mua', 'service'])->latest()->get();
        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mua_id'     => 'required|exists:users,id',
            'service_id' => 'required|exists:services,id',
            'date'       => 'required|date|after_or_equal:today',
            'time'       => 'required',
            'payment_method' => 'nullable|string',
        ]);

        $customerProfile = Auth::user()->customerProfile;

        $booking = Booking::create([
            'customer_id'  => Auth::id(),
            'mua_id'       => $request->mua_id,
            'service_id'   => $request->service_id,
            'date'         => $request->date,
            'time'         => $request->time,
            'status'       => 'pending',
            'payment_status' => 'pending',
            'payment_method' => $request->payment_method,
            'total_price'  => $request->total_price ?? 0,
            'customer_skin_profile_snapshot' => $customerProfile ? $customerProfile->toArray() : null,
        ]);

        NotifyHelper::notify(
            $booking->mua_id,
            'Booking Baru Masuk',
            'Anda menerima booking dari ' . Auth::user()->name . ' untuk tanggal ' . $booking->date . ' pukul ' . $booking->time
        );

        return response()->json([
            'message' => 'Booking created',
            'data'    => $booking
        ]);
    }
}
