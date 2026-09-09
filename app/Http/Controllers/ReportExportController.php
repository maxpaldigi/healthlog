<?php

namespace App\Http\Controllers;

use App\Support\CurrentProfile;
use App\Support\HistoryDateRange;
use App\Support\ReadingTimeline;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __invoke(Request $request, CurrentProfile $currentProfile): StreamedResponse
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
        $rows = ReadingTimeline::merge($profile, $bp, $glucose);
        usort($rows, fn (array $a, array $b): int => strcmp($a['measured_at_utc'], $b['measured_at_utc']));

        $filename = 'healthlog-'.$range['filters']['from'].'-to-'.$range['filters']['to'].'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Date', 'Time', 'Type', 'Slot or when', 'Value', 'Flag', 'Note']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['date_key'],
                    $row['time_label'],
                    $row['type'] === 'bp' ? 'Blood pressure' : 'Sugar',
                    $row['slot_label'] ?? $row['context_label'],
                    $row['title'],
                    $row['range_label'] ?? '',
                    $row['note'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
