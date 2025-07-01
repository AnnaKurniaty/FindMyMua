<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use App\Models\Review;

/**
 * @OA\Post(
 *     path="/api/customer/reviews",
 *     summary="Customer menulis review setelah booking selesai",
 *     tags={"Review"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="booking_id", type="integer", example=7),
 *             @OA\Property(property="rating", type="integer", example=5),
 *             @OA\Property(property="comment", type="string", example="Hasil makeup bagus dan awet!")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Review disimpan")
 * )
 */

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string'
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('customer_id', Auth::id())
            ->where('status', 'completed')
            ->firstOrFail();

        if ($booking->review) {
            return response()->json(['message' => 'Review already submitted'], 409);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'rating'     => $request->rating,
            'comment'    => $request->comment
        ]);

        return response()->json([
            'message' => 'Review submitted',
            'review'  => $review
        ]);
    }
}
