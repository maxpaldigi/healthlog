<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Readings\StoreBpReading;
use App\Actions\Readings\UpdateBpReading;
use App\Http\Controllers\Controller;
use App\Http\Requests\Readings\StoreBpReadingRequest;
use App\Http\Requests\Readings\UpdateBpReadingRequest;
use App\Models\BpReading;
use App\Support\CurrentProfile;
use App\Support\ReadingTimeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BpReadingController extends Controller
{
    public function store(
        StoreBpReadingRequest $request,
        CurrentProfile $currentProfile,
        StoreBpReading $storeBpReading,
    ): JsonResponse {
        $profile = $currentProfile->handle($request->user(), $request);
        $reading = $storeBpReading->handle($profile, $request->validated());

        return response()->json([
            'reading' => ReadingTimeline::bpItem($profile, $reading),
        ], 201);
    }

    public function update(
        UpdateBpReadingRequest $request,
        BpReading $bpReading,
        UpdateBpReading $updateBpReading,
    ): JsonResponse {
        $reading = $updateBpReading->handle($bpReading, $request->validated());
        $profile = $reading->profile;

        return response()->json([
            'reading' => ReadingTimeline::bpItem($profile, $reading),
        ]);
    }

    public function destroy(Request $request, BpReading $bpReading): JsonResponse
    {
        $this->authorize('delete', $bpReading);
        $bpReading->delete();

        return response()->json(['message' => 'Blood pressure deleted.']);
    }
}
