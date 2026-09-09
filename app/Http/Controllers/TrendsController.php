<?php

namespace App\Http\Controllers;

use App\Support\CurrentProfile;
use App\Support\HistoryDateRange;
use App\Support\ReadingInsights;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrendsController extends Controller
{
    public function __invoke(Request $request, CurrentProfile $currentProfile): Response
    {
        $profile = $currentProfile->handle($request->user(), $request);

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $range = HistoryDateRange::make(
            $profile,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        $bp = $profile->bpReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get();
        $glucose = $profile->glucoseReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get();

        return Inertia::render('readings/Trends', [
            'insights' => ReadingInsights::make($profile, $bp, $glucose),
            'filters' => $range['filters'],
            'presets' => $range['presets'],
            'month' => $range['month'],
        ]);
    }
}
