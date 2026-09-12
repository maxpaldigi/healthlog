<?php

namespace App\Support;

use App\Models\Profile;
use App\Models\Weight;

final class BodyCheckIn
{
    public const MONTHS = 6;

    public static function shouldOpenAfterSignIn(Profile $profile): bool
    {
        return $profile->body_checkin_pending;
    }

    /**
     * @return array{key: string, title: string, message: string}|null
     */
    public static function homeCard(Profile $profile): ?array
    {
        if ($profile->body_checkin_pending) {
            return null;
        }

        if ($profile->height_cm === null && $profile->height_prompt_skipped_at === null) {
            return [
                'key' => 'body-first',
                'title' => 'Height and weight',
                'message' => 'Optional. We can show BMI on your clinic page. You can skip.',
            ];
        }

        if (! self::weightIsDue($profile)) {
            return null;
        }

        $latest = self::latestWeight($profile);
        $when = $latest?->measured_at
            ?->timezone($profile->clockTimezone())
            ->format('j M Y');

        return [
            'key' => 'body-weight',
            'title' => 'Today’s weight',
            'message' => $when === null
                ? 'Add today’s weight when you have it. We ask about every six months because it changes.'
                : "Last weight was {$when}. Add today’s when you have it.",
        ];
    }

    public static function weightIsDue(Profile $profile): bool
    {
        $latest = self::latestWeight($profile);
        $now = $profile->nowInTimezone();

        if ($latest === null) {
            return $profile->height_cm !== null || $profile->height_prompt_skipped_at !== null;
        }

        return $latest->measured_at
            ->timezone($profile->clockTimezone())
            ->lte($now->copy()->subMonths(self::MONTHS));
    }

    public static function latestWeight(Profile $profile): ?Weight
    {
        return $profile->weights()->orderByDesc('measured_at')->first();
    }

    /**
     * @return array{id: int, value: string, unit: string, measured_at: string, bmi: string|null}
     */
    public static function weightPayload(Profile $profile, Weight $weight, string $when): array
    {
        return [
            'id' => $weight->id,
            'value' => BodyWeight::fromKg((string) $weight->kg, $profile->weight_unit),
            'unit' => $profile->weight_unit->label(),
            'measured_at' => $when,
            'bmi' => BodyMassIndex::format(
                (string) $weight->kg,
                $profile->height_cm === null ? null : (string) $profile->height_cm,
            ),
        ];
    }
}
