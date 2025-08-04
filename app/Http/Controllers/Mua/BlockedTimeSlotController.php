<?php

namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use App\Models\BlockedTimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockedTimeSlotController extends Controller
{
    /**
     * Get all blocked time slots for the authenticated MUA
     */
    public function index(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $query = BlockedTimeSlot::where('mua_id', Auth::id());
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        $blockedSlots = $query->get()->map(function ($slot) {
            return [
                'id' => $slot->id,
                'date' => $slot->date,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'reason' => $slot->reason,
                'is_full_day' => $slot->is_full_day
            ];
        });
        
        return response()->json($blockedSlots);
    }

    /**
     * Get blocked time slots for a specific MUA (public endpoint)
     */
    public function getBlockedSlots($muaId, Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $query = BlockedTimeSlot::where('mua_id', $muaId);
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        $blockedSlots = $query->get()->map(function ($slot) {
            return [
                'date' => $slot->date,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'reason' => $slot->reason,
                'is_full_day' => $slot->is_full_day
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $blockedSlots
        ]);
    }

    /**
     * Create a new blocked time slot
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:255',
            'is_full_day' => 'boolean'
        ]);

        $blockedSlot = BlockedTimeSlot::create([
            'mua_id' => Auth::id(),
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'reason' => $request->reason,
            'is_full_day' => $request->is_full_day ?? false
        ]);

        return response()->json([
            'success' => true,
            'data' => $blockedSlot,
            'message' => 'Blocked time slot created successfully'
        ]);
    }

    /**
     * Update a blocked time slot
     */
    public function update(Request $request, $id)
    {
        $blockedSlot = BlockedTimeSlot::where('mua_id', Auth::id())->findOrFail($id);
        
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:255',
            'is_full_day' => 'boolean'
        ]);

        $blockedSlot->update([
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'reason' => $request->reason,
            'is_full_day' => $request->is_full_day ?? false
        ]);

        return response()->json([
            'success' => true,
            'data' => $blockedSlot,
            'message' => 'Blocked time slot updated successfully'
        ]);
    }

    /**
     * Delete a blocked time slot
     */
    public function destroy($id)
    {
        $blockedSlot = BlockedTimeSlot::where('mua_id', Auth::id())->findOrFail($id);
        $blockedSlot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blocked time slot deleted successfully'
        ]);
    }
}
