<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\CareAlerts;
use App\Support\CurrentProfile;
use App\Support\HomePayload;
use App\Support\ReadingReminders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentProfile $currentProfile,
        ReadingReminders $reminders,
        CareAlerts $alerts,
    ): JsonResponse {
        $user = $request->user();
        $profile = $currentProfile->handle($user, $request);

        return response()->json(HomePayload::make($user, $profile, $reminders, $alerts));
    }
}
