<?php

namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BlockedTimeSlot;

class BlockedTimeSlotController extends Controller
{
    public function index(Request $request)
    {
        $mua = Auth::user();
        
        $query = BlockedTimeSlot::where('mua_id', $mua->id);
        
        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        
        $blockedSlots = $query->get();
        
        return response()->json($blockedSlots);
    }
    
    public function store(Request $request)
    {
        $mua = Auth::user();
        
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
            'full_day' => 'boolean',
            'reason' => 'nullable|string|max:255',
        ]);
        
        // Check if this time slot is already blocked
        $existing = BlockedTimeSlot::where('mua_id', $mua->id)
            ->where('date', $request->date)
            ->where('start_time', $request->start_time)
            ->where('end_time', $request->end_time)
            ->first();
            
        if ($existing) {
            return response()->json(['message' => 'This time slot is already blocked'], 400);
        }
        
        $blockedSlot = BlockedTimeSlot::create([
            'mua_id' => $mua->id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'full_day' => $request->full_day ?? false,
            'reason' => $request->reason,
        ]);
        
        return response()->json($blockedSlot, 201);
    }
    
    public function destroy($id)
    {
        $mua = Auth::user();
        
        $blockedSlot = BlockedTimeSlot::where('mua_id', $mua->id)->findOrFail($id);
        $blockedSlot->delete();
        
        return response()->json(['message' => 'Blocked time slot deleted successfully']);
    }
}
