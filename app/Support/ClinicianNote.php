<?php

namespace App\Support;

use App\Enums\TargetSetBy;
use App\Models\ClinicianTarget;
use App\Models\Profile;

/**
 * Latest clinic reply on a log. Display only — never a diagnosis.
 */
final class ClinicianNote
{
    /**
     * @return array{
     *     instruction: string|null,
     *     acknowledged_on: string,
     *     targets_line: string|null,
     *     set_by: string
     * }|null
     */
    public static function current(Profile $profile): ?array
    {
        $row = $profile->clinicianTargets()
            ->whereNotNull('acknowledged_at')
            ->orderByDesc('id')
            ->first();

        if ($row === null) {
            return null;
        }

        return [
            'instruction' => $row->instruction,
            'acknowledged_on' => $row->acknowledged_at?->timezone($profile->clockTimezone())->format('j M Y') ?? '',
            'targets_line' => self::targetsLine($profile, $row),
            'set_by' => $profile->glucose_target_set_by->value,
        ];
    }

    private static function targetsLine(Profile $profile, ClinicianTarget $row): ?string
    {
        if ($profile->glucose_target_set_by !== TargetSetBy::Clinician) {
            return null;
        }

        if ($row->glucose_low_mg_dl === null || $row->glucose_high_mg_dl === null) {
            return null;
        }

        $unit = $profile->glucose_unit->label();
        $low = Glucose::fromMgDl((string) $row->glucose_low_mg_dl, $profile->glucose_unit);
        $high = Glucose::fromMgDl((string) $row->glucose_high_mg_dl, $profile->glucose_unit);

        return 'Clinic sugar targets: '.$low.'–'.$high.' '.$unit.'.';
    }
}
