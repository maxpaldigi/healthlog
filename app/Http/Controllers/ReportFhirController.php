<?php

namespace App\Http\Controllers;

use App\Support\CurrentProfile;
use App\Support\FhirExport;
use App\Support\HistoryDateRange;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportFhirController extends Controller
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

        $bundle = FhirExport::make(
            $profile,
            $range['filters']['from'],
            $range['filters']['to'],
            $profile->bpReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get(),
            $profile->glucoseReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get(),
            $profile->hba1cResults()->forPeriod($range['from'], $range['to'])->orderBy('tested_at')->get(),
            $profile->medications()->orderByDesc('is_active')->orderBy('name')->get(),
        );

        return FhirExport::download($bundle, $range['filters']['from'], $range['filters']['to']);
    }
}
