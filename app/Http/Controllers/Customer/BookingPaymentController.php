<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Get(
 *     path="/api/booking/{id}/pay",
 *     summary="Generate Midtrans Snap token & redirect URL untuk customer",
 *     tags={"Payment"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Booking ID",
 *         @OA\Schema(type="integer", example=7)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Berhasil menghasilkan Snap token",
 *         @OA\JsonContent(
 *             @OA\Property(property="token", type="string", example="snap-123abcxyz"),
 *             @OA\Property(property="redirect_url", type="string", example="https://app.sandbox.midtrans.com/snap/v2/vtweb/xxxx")
 *         )
 *     ),
 *     @OA\Response(response=404, description="Booking tidak ditemukan"),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

class BookingPaymentController extends Controller
{
    protected $midtrans;

    public function __construct(MidtransService $midtrans)
    {
        $this->midtrans = $midtrans;
    }

    public function pay($id)
    {
        $booking = Booking::with('customer')->where('customer_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $snap = $this->midtrans->createTransaction($booking);

        return response()->json([
            'token' => $snap->token,
            'redirect_url' => $snap->redirect_url,
        ]);
    }
}
