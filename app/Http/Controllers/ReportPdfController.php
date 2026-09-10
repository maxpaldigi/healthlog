<?php

namespace App\Http\Controllers;

use App\Support\CurrentProfile;
use App\Support\HistoryDateRange;
use App\Support\VisitReport;
use App\Support\VisitReportPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ReportPdfController extends Controller
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

        $fromLocal = Carbon::parse($range['filters']['from'], $profile->clockTimezone())->startOfDay();
        $toLocal = Carbon::parse($range['filters']['to'], $profile->clockTimezone())->startOfDay();

        $report = VisitReport::make(
            $profile,
            $fromLocal,
            $toLocal,
            $profile->bpReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get(),
            $profile->glucoseReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get(),
            $profile->weights()->forPeriod($range['from'], $range['to'])->orderByDesc('measured_at')->get(),
            $profile->hba1cResults()->forPeriod($range['from'], $range['to'])->orderByDesc('tested_at')->get(),
            $profile->medications()->orderByDesc('is_active')->orderBy('name')->get(),
        );

        return response(VisitReportPdf::render($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.VisitReportPdf::filename($report).'"',
        ]);
    }
}
