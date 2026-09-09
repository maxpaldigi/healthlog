<?php

namespace App\Http\Controllers;

use App\Support\CurrentProfile;
use App\Support\HistoryDateRange;
use App\Support\VisitReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
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

        $fromLocal = Carbon::parse($range['filters']['from'], $profile->timezone)->startOfDay();
        $toLocal = Carbon::parse($range['filters']['to'], $profile->timezone)->startOfDay();

        $bp = $profile->bpReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get();
        $glucose = $profile->glucoseReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get();
        $weights = $profile->weights()->forPeriod($range['from'], $range['to'])->orderByDesc('measured_at')->get();
        $hba1c = $profile->hba1cResults()->forPeriod($range['from'], $range['to'])->orderByDesc('tested_at')->get();
        $medications = $profile->medications()->orderByDesc('is_active')->orderBy('name')->get();

        return Inertia::render('readings/Report', [
            'report' => VisitReport::make(
                $profile,
                $fromLocal,
                $toLocal,
                $bp,
                $glucose,
                $weights,
                $hba1c,
                $medications,
            ),
            'filters' => $range['filters'],
            'presets' => $range['presets'],
            'month' => $range['month'],
            'shared' => false,
            'share_url' => $request->session()->get('share_url'),
            'expires_at' => null,
        ]);
    }
}
