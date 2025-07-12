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

        $preferredStyles = $this->parsePreferenceString($profile->makeup_preferences);
        $skinTone = $profile->skin_tone;
        $skinType = $profile->skin_type;

        $recommended = MuaProfile::whereNotNull('makeup_styles')
            ->where(function ($q) use ($preferredStyles) {
                foreach ($preferredStyles as $style) {
                    $q->orWhereJsonContains('makeup_styles', $style);
                }
            })
            ->with('user')
            ->get();

        return response()->json([
            'recommended' => $recommended
        ]);
    }

    private function parsePreferenceString($string)
    {
        if (!$string) return [];

        preg_match_all('/\b(natural|bold|korean|western|glam|matte|dewy)\b/i', strtolower($string), $matches);
        return array_unique($matches[0]);
    }
}
