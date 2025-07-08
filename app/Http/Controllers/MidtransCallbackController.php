<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Midtrans\Notification;

/**
 * @OA\Post(
 *     path="/api/midtrans/callback",
 *     summary="Endpoint callback untuk notifikasi pembayaran dari Midtrans",
 *     tags={"Payment"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="order_id", type="string", example="BOOK-5-1719820000"),
 *             @OA\Property(property="transaction_status", type="string", example="settlement"),
 *             @OA\Property(property="payment_type", type="string", example="gopay"),
 *             @OA\Property(property="fraud_status", type="string", example="accept")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Booking status updated"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid request"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Booking not found"
 *     )
 * )
 */

class MidtransCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $notif = new Notification();

        $transaction = $notif->transaction_status;
        $paymentType = $notif->payment_type;
        $orderId     = $notif->order_id;  // e.g. BOOK-5-1719820000
        $fraud       = $notif->fraud_status;

        // Ambil ID booking dari order_id
        preg_match('/BOOK-(\d+)-/', $orderId, $matches);
        $bookingId = $matches[1] ?? null;

        if (!$bookingId) {
            return response()->json(['message' => 'Invalid order ID'], 400);
        }

        $booking = Booking::find($bookingId);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        // Update status booking & pembayaran berdasarkan transaksi Midtrans
        if ($transaction == 'capture' || $transaction == 'settlement') {
            $booking->payment_status = 'paid';
            if ($booking->status == 'pending') {
                $booking->status = 'confirmed';
            }
        } elseif (in_array($transaction, ['cancel', 'expire', 'deny'])) {
            $booking->payment_status = 'failed';
            $booking->status = 'cancelled';
        }

        $booking->save();

        return response()->json(['message' => 'Booking status updated']);
    }
}
