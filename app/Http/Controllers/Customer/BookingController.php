<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Helpers\NotifyHelper;

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

    public function show($id)
    {
        $booking = Booking::where('id', $id)->where('customer_id', auth()->id())->with(['mua', 'service'])->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        return response()->json($booking);
    }
}
