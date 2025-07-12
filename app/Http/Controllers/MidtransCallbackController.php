<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Midtrans\Notification;

class MidtransCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $notif = new Notification();

        $transaction = $notif->transaction_status;
        $paymentType = $notif->payment_type;
        $orderId     = $notif->order_id;
        $fraud       = $notif->fraud_status;

        preg_match('/BOOK-(\d+)-/', $orderId, $matches);
        $bookingId = $matches[1] ?? null;

        if (!$bookingId) {
            return response()->json(['message' => 'Invalid order ID'], 400);
        }

        $booking = Booking::find($bookingId);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

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
