<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Readings\StoreGlucoseReading;
use App\Actions\Readings\UpdateGlucoseReading;
use App\Http\Controllers\Controller;
use App\Http\Requests\Readings\StoreGlucoseReadingRequest;
use App\Http\Requests\Readings\UpdateGlucoseReadingRequest;
use App\Models\GlucoseReading;
use App\Support\CurrentProfile;
use App\Support\ReadingTimeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlucoseReadingController extends Controller
{
    public function store(
        StoreGlucoseReadingRequest $request,
        CurrentProfile $currentProfile,
        StoreGlucoseReading $storeGlucoseReading,
    ): JsonResponse {
        $profile = $currentProfile->handle($request->user(), $request);
        $reading = $storeGlucoseReading->handle($profile, $request->validated());

        return response()->json([
            'reading' => ReadingTimeline::glucoseItem($profile, $reading),
        ], 201);
    }

    public function update(
        UpdateGlucoseReadingRequest $request,
        GlucoseReading $glucoseReading,
        UpdateGlucoseReading $updateGlucoseReading,
    ): JsonResponse {
        $reading = $updateGlucoseReading->handle($glucoseReading, $request->validated());
        $profile = $reading->profile;

        return response()->json([
            'reading' => ReadingTimeline::glucoseItem($profile, $reading),
        ]);
    }

    public function destroy(Request $request, GlucoseReading $glucoseReading): JsonResponse
    {
        $this->authorize('delete', $glucoseReading);
        $glucoseReading->delete();

        return response()->json(['message' => 'Glucose deleted.']);
    }
}
