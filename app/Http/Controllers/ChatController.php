<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Chat;
use App\Models\Booking;
use App\Helpers\NotifyHelper;

/**
 * @OA\Get(
 *     path="/api/chat/{booking_id}",
 *     summary="Ambil pesan chat booking",
 *     tags={"Chat"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="booking_id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Daftar pesan chat")
 * )
 *
 * @OA\Post(
 *     path="/api/chat/{booking_id}",
 *     summary="Kirim pesan chat",
 *     tags={"Chat"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="booking_id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Halo Kak, lokasi kita di mana ya?")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Pesan terkirim")
 * )
 */

class ChatController extends Controller
{
    public function index($booking_id)
    {
        $booking = Booking::where('id', $booking_id)
            ->where(function ($q) {
                $q->where('customer_id', Auth::id())
                  ->orWhere('mua_id', Auth::id());
            })
            ->firstOrFail();

        $messages = Chat::where('booking_id', $booking_id)
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }

    public function store(Request $request, $booking_id)
    {
        $booking = Booking::where('id', $booking_id)
            ->where('status', 'confirmed')
            ->where(function ($q) {
                $q->where('customer_id', Auth::id())
                  ->orWhere('mua_id', Auth::id());
            })
            ->firstOrFail();

        $request->validate([
            'message' => 'required|string'
        ]);

        $chat = Chat::create([
            'booking_id' => $booking_id,
            'sender_id' => Auth::id(),
            'message' => $request->message,
        ]);

        // 🔔 Kirim notifikasi ke lawan bicara
        $receiver_id = Auth::id() === $booking->customer_id
            ? $booking->mua_id
            : $booking->customer_id;

        NotifyHelper::notify($receiver_id, 'Pesan Baru', 'Anda menerima pesan dari ' . Auth::user()->name);

        return response()->json([
            'message' => 'Message sent',
            'data' => $chat->load('sender')
        ]);
    }
}
