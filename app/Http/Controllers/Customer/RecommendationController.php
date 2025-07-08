<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\MuaProfile;
use Illuminate\Http\Request;

/**
 * @OA\Get(
 *     path="/api/customer/recommendations",
 *     summary="Rekomendasi MUA berdasarkan preferensi & profil kulit customer",
 *     tags={"Recommendation"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="List rekomendasi MUA")
 * )
 */

class RecommendationController extends Controller
{
    public function index(Request $request)
    {
        $profile = Auth::user()->customerProfile;

        if (!$profile) {
            return response()->json(['message' => 'Please complete your skin profile first'], 422);
        }

        // Ekstrak preferensi customer
        $preferredStyles = $this->parsePreferenceString($profile->makeup_preferences); // convert to array
        $skinTone = $profile->skin_tone;
        $skinType = $profile->skin_type;

        // Query MUA yang cocok
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

        // Example: "natural, matte, suka brand BLP, hindari MAC"
        preg_match_all('/\b(natural|bold|korean|western|glam|matte|dewy)\b/i', strtolower($string), $matches);
        return array_unique($matches[0]);
    }
}
