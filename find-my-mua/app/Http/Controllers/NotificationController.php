<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Get(
 *     path="/api/notifications",
 *     summary="Ambil notifikasi pengguna",
 *     tags={"Notification"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="List notifikasi")
 * )
 *
 * @OA\Post(
 *     path="/api/notifications/read",
 *     summary="Tandai semua notifikasi sebagai sudah dibaca",
 *     tags={"Notification"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Berhasil ditandai")
 * )
 */

class NotificationController extends Controller
{
    public function index()
    {
        return Notification::where('user_id', Auth::id())
            ->latest()
            ->get();
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->update([
                'read' => true,
                'sent_at' => now()
            ]);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca']);
    }
}
