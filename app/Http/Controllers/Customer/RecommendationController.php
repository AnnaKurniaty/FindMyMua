<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\MuaProfile;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function index(Request $request)
    {
        $profile = Auth::user()->customerProfile;

        if (!$profile) {
            return response()->json(['message' => 'Please complete your skin profile first'], 422);
        }

        $preferredStylesRaw = $profile->makeup_preferences;
        $preferredStyles = is_string($preferredStylesRaw) ? json_decode($preferredStylesRaw, true) : $preferredStylesRaw;

        if (!is_array($preferredStyles)) {
            return response()->json(['message' => 'Invalid makeup preferences format'], 422);
        }

        $skinTypesRaw = $profile->skin_type;
        $skinTypes = is_string($skinTypesRaw) ? json_decode($skinTypesRaw, true) : $skinTypesRaw;
        if (!is_array($skinTypes)) {
            $skinTypes = [];
        }

        $makeupStylesRaw = $profile->makeup_style;
        $makeupStyles = is_string($makeupStylesRaw) ? json_decode($makeupStylesRaw, true) : $makeupStylesRaw;
        if (!is_array($makeupStyles)) {
            $makeupStyles = [];
        }

        $recommendedMuas = MuaProfile::where(function ($query) use ($skinTypes) {
            foreach ($skinTypes as $skinType) {
                $query->orWhereRaw("skin_type::jsonb @> ?", [json_encode([$skinType])]);
            }
        })
        ->where(function ($query) use ($makeupStyles) {
            foreach ($makeupStyles as $style) {
                $query->orWhereRaw("makeup_styles::jsonb @> ?", [json_encode([$style])]);
            }
        })
        ->with('user')
        ->get();

        // Add starting_price attribute from services table (lowest price per MUA)
        $recommendedMuas->map(function ($muaProfile) {
            $minPrice = \App\Models\Service::where('mua_id', $muaProfile->user_id)->min('price');
            $muaProfile->starting_price = $minPrice ?? 0;

            // Add service categories attribute
            $categories = \App\Models\Service::where('mua_id', $muaProfile->user_id)
                ->distinct()
                ->pluck('category');
            $muaProfile->service_categories = $categories;

            return $muaProfile;
        });

        return response()->json([
            'recommended' => $recommendedMuas
        ]);
    }
}
