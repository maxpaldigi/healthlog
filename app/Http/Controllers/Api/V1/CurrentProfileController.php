<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrentProfileController extends Controller
{
    public function update(Request $request, Profile $profile): JsonResponse
    {
        $this->authorize('view', $profile);

        return response()->json([
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'glucose_unit' => $profile->glucose_unit->value,
                'timezone' => $profile->clockTimezone(),
            ],
            'message' => 'Send X-Profile-Id on later requests to stay on this profile.',
        ]);
    }
}
