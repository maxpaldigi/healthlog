<?php

namespace App\Http\Controllers;

use App\Support\CurrentProfile;
use App\Support\HistoryDateRange;
use App\Support\ReadingTimeline;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function __invoke(Request $request, CurrentProfile $currentProfile): Response
    {
        $profile = $currentProfile->handle($request->user(), $request);

        $validated = $request->validate([
            'type' => ['nullable', 'in:all,bp,glucose'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $type = $validated['type'] ?? 'all';
        $range = HistoryDateRange::make(
            $profile,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        $bp = $type === 'glucose'
            ? collect()
            : $profile->bpReadings()->forPeriod($range['from'], $range['to'])->orderByDesc('measured_at')->get();

        $glucose = $type === 'bp'
            ? collect()
            : $profile->glucoseReadings()->forPeriod($range['from'], $range['to'])->orderByDesc('measured_at')->get();

        return Inertia::render('readings/History', [
            'entries' => ReadingTimeline::merge($profile, $bp, $glucose),
            'filters' => [
                'type' => $type,
                ...$range['filters'],
            ],
            'presets' => $range['presets'],
            'month' => $range['month'],
        ]);
    }
}
