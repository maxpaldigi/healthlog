<?php

namespace App\Actions\Share;

use App\Enums\GlucoseUnit;
use App\Enums\TargetSetBy;
use App\Models\ClinicianTarget;
use App\Models\ShareLink;
use App\Support\Glucose;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SaveClinicianResponse
{
    /**
     * @param  array{acknowledged: mixed, instruction?: string|null, glucose_low?: mixed, glucose_high?: mixed}  $data
     */
    public function handle(ShareLink $link, array $data, ?string $ip): ClinicianTarget
    {
        $profile = $link->profile;
        $instruction = $this->instruction($data['instruction'] ?? null);
        [$low, $high] = $this->targets($profile->glucose_unit, $data['glucose_low'] ?? null, $data['glucose_high'] ?? null);
        $existing = $link->clinicianTarget;

        if ($low === null && $high === null && $existing !== null) {
            $low = $existing->glucose_low_mg_dl !== null ? (string) $existing->glucose_low_mg_dl : null;
            $high = $existing->glucose_high_mg_dl !== null ? (string) $existing->glucose_high_mg_dl : null;
        }

        $target = ClinicianTarget::query()->updateOrCreate(
            ['share_link_id' => $link->id],
            [
                'profile_id' => $profile->id,
                'instruction' => $instruction,
                'glucose_low_mg_dl' => $low,
                'glucose_high_mg_dl' => $high,
                'acknowledged_at' => now(),
                'ip_address' => $ip,
            ],
        );

        if ($low !== null && $high !== null) {
            $profile->update([
                'glucose_low_mg_dl' => $low,
                'glucose_high_mg_dl' => $high,
                'glucose_target_set_by' => TargetSetBy::Clinician,
            ]);
        }

        Log::info('Clinician respond write', [
            'share_link_id' => $link->id,
            'profile_id' => $profile->id,
            'ip' => $ip,
            'has_instruction' => $instruction !== null,
            'has_targets' => $low !== null && $high !== null,
        ]);

        return $target;
    }

    private function instruction(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $text = trim(strip_tags($value));

        return $text === '' ? null : mb_substr($text, 0, 500);
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function targets(GlucoseUnit $unit, mixed $lowRaw, mixed $highRaw): array
    {
        $lowFilled = filled($lowRaw);
        $highFilled = filled($highRaw);

        if (! $lowFilled && ! $highFilled) {
            return [null, null];
        }

        if ($lowFilled !== $highFilled) {
            throw ValidationException::withMessages([
                'glucose_high' => 'Enter both a low and a high sugar target, or leave both blank.',
            ]);
        }

        $low = Glucose::toMgDl((string) $lowRaw, $unit);
        $high = Glucose::toMgDl((string) $highRaw, $unit);

        if (! Glucose::isWithinStoredRange($low)) {
            throw ValidationException::withMessages([
                'glucose_low' => 'That low sugar target is outside a usable range.',
            ]);
        }

        if (! Glucose::isWithinStoredRange($high)) {
            throw ValidationException::withMessages([
                'glucose_high' => 'That high sugar target is outside a usable range.',
            ]);
        }

        if (! Glucose::isLowerThan($low, $high)) {
            throw ValidationException::withMessages([
                'glucose_high' => 'The high sugar target must be above the low target.',
            ]);
        }

        return [$low, $high];
    }
}
