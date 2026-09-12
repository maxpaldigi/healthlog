<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinic visit report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1c1917; margin: 24pt; }
        h1 { font-size: 18pt; margin: 0 0 4pt; }
        h2 { font-size: 13pt; margin: 16pt 0 8pt; }
        p { margin: 0 0 8pt; line-height: 1.4; }
        .muted { color: #57534e; }
        .header { margin-bottom: 16pt; }
        .logo { width: 48pt; height: 48pt; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12pt; }
        th, td { border: 1px solid #d6d3d1; padding: 6pt 8pt; text-align: left; vertical-align: top; }
        th { background: #f5f5f4; font-weight: 600; }
        .nums { font-variant-numeric: tabular-nums; }
        ul { margin: 0 0 12pt; padding-left: 16pt; }
        .disclaimer { font-size: 9pt; color: #57534e; margin-top: 18pt; }
    </style>
</head>
<body>
    <div class="header">
        @if (is_string($logo ?? null) && is_file($logo))
            <img class="logo" src="{{ $logo }}" alt="HealthyMe">
        @endif
        <p class="muted">HealthyMe</p>
        <h1>Clinic visit report</h1>
        <p>{{ $report['profile']['name'] }}</p>
        <p class="muted">{{ $report['range']['label'] }}</p>
    </div>

    @if (! empty($report['clinic_note']))
        <h2>Note from clinic</h2>
        <p>Looked at this log on {{ $report['clinic_note']['acknowledged_on'] }}.</p>
        @if (! empty($report['clinic_note']['instruction']))
            <p>{{ $report['clinic_note']['instruction'] }}</p>
        @endif
        @if (! empty($report['clinic_note']['targets_line']))
            <p>{{ $report['clinic_note']['targets_line'] }}</p>
        @endif
    @endif

    <p>{{ $report['insights']['summary_text'] }}</p>

    @if (! empty($report['insights']['stats_lines']))
        <h2>Numbers from this range</h2>
        <ul>
            @foreach ($report['insights']['stats_lines'] as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    @endif

    <p>
        Average BP: <span class="nums">{{ $report['insights']['summary']['average_bp'] ?? '—' }}</span>
        · Average sugar: <span class="nums">{{ $report['insights']['summary']['average_glucose'] ?? '—' }}</span>
        · 3-time days: {{ $report['insights']['summary']['days_with_three_bp'] }} of {{ $report['insights']['summary']['days_with_bp'] }}
        · Readings: {{ $report['insights']['summary']['bp_count'] }} BP · {{ $report['insights']['summary']['glucose_count'] }} sugar
    </p>

    <h2>Blood pressure by time of day</h2>
    <table>
        <thead>
            <tr>
                <th>Day</th>
                <th>Morning</th>
                <th>Afternoon</th>
                <th>Evening</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['days'] as $day)
                <tr>
                    <th>{{ $day['date_label'] }}</th>
                    @foreach (['morning', 'afternoon', 'evening'] as $slot)
                        <td class="nums">
                            @if (! empty($day['slots'][$slot]))
                                {{ $day['slots'][$slot]['title'] }}
                                @if (! empty($day['slots'][$slot]['range_label']))
                                    <br>{{ $day['slots'][$slot]['range_label'] }}
                                @endif
                                <br><span class="muted">{{ $day['slots'][$slot]['time_label'] }}</span>
                                @if (! empty($day['slots'][$slot]['note']))
                                    <br>{{ $day['slots'][$slot]['note'] }}
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (collect($report['days'])->contains(fn (array $day): bool => $day['sugar'] !== []))
        <h2>Sugar readings</h2>
        <table>
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Value</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['days'] as $day)
                    @foreach ($day['sugar'] as $index => $item)
                        <tr>
                            <td>{{ $index === 0 ? $day['date_label'] : '' }}</td>
                            <td class="nums">
                                {{ $item['title'] }}
                                @if (! empty($item['range_label']))
                                    {{ $item['range_label'] }}
                                @endif
                                @if (! empty($item['note']))
                                    <br>{{ $item['note'] }}
                                @endif
                            </td>
                            <td>{{ $item['time_label'] }} · {{ $item['context_label'] }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    @if (($report['sugar_by_context'] ?? []) !== [])
        <h2>Sugar by when it was taken</h2>
        <ul>
            @foreach ($report['sugar_by_context'] as $row)
                <li>{{ $row['label'] }} ({{ $row['count'] }}) — {{ $row['average'] }}</li>
            @endforeach
        </ul>
    @endif

    @if (($report['weights'] ?? []) !== [])
        <h2>Weight</h2>
        <ul>
            @foreach ($report['weights'] as $item)
                <li>
                    {{ $item['measured_at'] }} — {{ $item['value'] }} {{ $item['unit'] }}
                    @if (! empty($item['bmi']))
                        · BMI {{ $item['bmi'] }}
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if (($report['hba1c'] ?? []) !== [])
        <h2>HbA1c</h2>
        <ul>
            @foreach ($report['hba1c'] as $item)
                <li>
                    {{ $item['tested_at'] }}
                    @if (! empty($item['lab_name']))
                        · {{ $item['lab_name'] }}
                    @endif
                    — {{ $item['value'] }}%
                </li>
            @endforeach
        </ul>
    @endif

    @if (($report['medications'] ?? []) !== [])
        <h2>Medications</h2>
        <ul>
            @foreach ($report['medications'] as $item)
                <li>
                    {{ $item['name'] }}
                    @if (! empty($item['dose']))
                        · {{ $item['dose'] }}
                    @endif
                    @if (! empty($item['schedule']))
                        · {{ $item['schedule'] }}
                    @endif
                    @if (empty($item['is_active']))
                        · stopped
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <p class="disclaimer">{{ $report['disclaimer'] }}</p>
</body>
</html>
