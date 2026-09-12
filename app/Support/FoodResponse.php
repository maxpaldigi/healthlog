<?php

namespace App\Support;

use App\Enums\BpDaySlot;
use App\Enums\GlucoseContext;
use App\Enums\TargetSetBy;
use App\Models\BpReading;
use App\Models\FoodLog;
use App\Models\GlucoseReading;
use App\Models\Profile;

final class FoodResponse
{
    /**
     * Hide averages until the person has logged the same food this many times.
     * Display only — never a diagnosis.
     */
    public const MIN_SAMPLES = 5;

    public const PHOTO_HINT = 'After you log this food 5 times with a 2-hour sugar reading, your own average will show here.';

    /**
     * @return array{
     *     after_meals: list<array{key: string, label: string, sample_count: int, average: string, unit: string, line: string}>,
     *     morning_blood_pressure: list<array{key: string, label: string, sample_count: int, average: string, line: string}>,
     *     intro: string,
     *     hint: string
     * }
     */
    public static function make(Profile $profile): array
    {
        return [
            'after_meals' => self::afterMeals($profile),
            'morning_blood_pressure' => self::morningBloodPressure($profile),
            'intro' => AppLocale::get('food.intro'),
            'hint' => AppLocale::get('food.hint'),
        ];
    }

    /**
     * @return list<array{key: string, label: string, sample_count: int, average: string, unit: string, line: string}>
     */
    public static function afterMeals(Profile $profile): array
    {
        $samples = [];

        foreach ($profile->foodLogs()->whereNotNull('glucose_reading_id')->with('glucoseReading')->orderBy('eaten_at')->get() as $log) {
            $reading = $log->glucoseReading;

            if (! $reading instanceof GlucoseReading || $reading->context !== GlucoseContext::PostMeal2h) {
                continue;
            }

            $samples[] = [
                'log' => $log,
                'sort' => bcadd((string) $reading->value_mg_dl, '0', 2),
            ];
        }

        $unit = $profile->glucose_unit->label();
        $rows = [];

        foreach (self::grouped($samples) as $group) {
            $count = count($group['values']);

            if ($count < self::MIN_SAMPLES) {
                continue;
            }

            $meanMgDl = self::mean($group['values']);
            $average = NutritionFacts::display(Glucose::fromMgDl($meanMgDl, $profile->glucose_unit));

            $rows[] = [
                'key' => $group['key'],
                'label' => $group['label'],
                'sample_count' => $count,
                'average' => $average,
                'unit' => $unit,
                'sort' => $meanMgDl,
                'line' => AppLocale::get('food.after_meal', [
                    'food' => $group['label'],
                    'average' => $average,
                    'unit' => $unit,
                    'count' => $count,
                ]),
            ];
        }

        return array_map(
            fn (array $row): array => [
                'key' => $row['key'],
                'label' => $row['label'],
                'sample_count' => $row['sample_count'],
                'average' => $row['average'],
                'unit' => $row['unit'],
                'line' => $row['line'],
            ],
            self::sortRows($rows),
        );
    }

    /**
     * @return list<array{key: string, label: string, sample_count: int, average: string, line: string}>
     */
    public static function morningBloodPressure(Profile $profile): array
    {
        $morning = $profile->bpReadings()
            ->orderBy('measured_at')
            ->get()
            ->filter(function (BpReading $reading) use ($profile): bool {
                $local = $reading->measured_at->timezone($profile->clockTimezone());

                return BpDaySlot::fromLocalTime($local) === BpDaySlot::Morning;
            })
            ->values();

        $samples = [];

        foreach ($profile->foodLogs()->orderBy('eaten_at')->get() as $food) {
            $nextMorning = $morning->first(
                fn (BpReading $reading): bool => $reading->measured_at->gt($food->eaten_at),
            );

            if (! $nextMorning instanceof BpReading) {
                continue;
            }

            $from = $nextMorning->measured_at->copy()->subHours(18);
            $to = $nextMorning->measured_at->copy()->subHours(6);

            if ($food->eaten_at->lt($from) || $food->eaten_at->gt($to)) {
                continue;
            }

            $samples[] = [
                'log' => $food,
                'sort' => (string) $nextMorning->systolic,
            ];
        }

        $rows = [];

        foreach (self::grouped($samples) as $group) {
            $count = count($group['values']);

            if ($count < self::MIN_SAMPLES) {
                continue;
            }

            $average = self::roundToInt(self::mean($group['values']));

            $rows[] = [
                'key' => $group['key'],
                'label' => $group['label'],
                'sample_count' => $count,
                'average' => $average,
                'sort' => $average,
                'line' => AppLocale::get('food.after_morning_bp', [
                    'food' => $group['label'],
                    'average' => $average,
                    'count' => $count,
                ]),
            ];
        }

        return array_map(
            fn (array $row): array => [
                'key' => $row['key'],
                'label' => $row['label'],
                'sample_count' => $row['sample_count'],
                'average' => $row['average'],
                'line' => $row['line'],
            ],
            self::sortRows($rows),
        );
    }

    /**
     * Facts for one named food after a photo. Display only — never a diagnosis.
     *
     * @return array{after_meal: string|null, morning_blood_pressure: string|null, clinic_targets: string|null, hint: string|null}
     */
    public static function forFood(Profile $profile, string $label, ?string $barcode = null): array
    {
        $want = self::lookupKey($label, $barcode);

        $after = collect(self::afterMeals($profile))->first(
            fn (array $row): bool => $row['key'] === $want,
        );
        $morning = collect(self::morningBloodPressure($profile))->first(
            fn (array $row): bool => $row['key'] === $want,
        );

        return [
            'after_meal' => is_array($after) ? $after['line'] : null,
            'morning_blood_pressure' => is_array($morning) ? $morning['line'] : null,
            'clinic_targets' => self::clinicTargetsLine($profile),
            'hint' => is_array($after) ? null : AppLocale::get('food.photo_hint'),
        ];
    }

    /**
     * @param  list<array{log: FoodLog, sort: string}>  $samples
     * @return list<array{key: string, label: string, values: list<string>}>
     */
    private static function grouped(array $samples): array
    {
        /** @var array<string, array{labels: array<string, int>, values: list<string>}> $groups */
        $groups = [];

        foreach ($samples as $sample) {
            $log = $sample['log'];
            $key = self::foodKey($log);

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'labels' => [],
                    'values' => [],
                ];
            }

            $label = $log->label;
            $groups[$key]['labels'][$label] = ($groups[$key]['labels'][$label] ?? 0) + 1;
            $groups[$key]['values'][] = $sample['sort'];
        }

        $result = [];

        foreach ($groups as $key => $group) {
            arsort($group['labels']);
            $label = array_key_first($group['labels']);

            $result[] = [
                'key' => $key,
                'label' => is_string($label) ? $label : AppLocale::get('food.unnamed'),
                'values' => $group['values'],
            ];
        }

        return $result;
    }

    public static function lookupKey(string $label, ?string $barcode = null): string
    {
        $code = null;

        if (is_string($barcode) && $barcode !== '') {
            $code = OpenFoodFacts::normalizeBarcode($barcode)
                ?? (preg_replace('/\D+/', '', $barcode) ?: null);
        }

        if (is_string($code) && $code !== '') {
            return 'off:'.$code;
        }

        $normalized = preg_replace('/\s+/u', ' ', mb_strtolower(trim($label)));

        return 'name:'.($normalized ?? mb_strtolower(trim($label)));
    }

    private static function foodKey(FoodLog $log): string
    {
        return self::lookupKey($log->label, $log->source_ref);
    }

    private static function clinicTargetsLine(Profile $profile): ?string
    {
        $low = $profile->glucose_low_mg_dl;
        $high = $profile->glucose_high_mg_dl;

        if ($low === null || $high === null) {
            return null;
        }

        $unit = $profile->glucose_unit->label();
        $lowDisplay = NutritionFacts::display(Glucose::fromMgDl((string) $low, $profile->glucose_unit));
        $highDisplay = NutritionFacts::display(Glucose::fromMgDl((string) $high, $profile->glucose_unit));
        $key = $profile->glucose_target_set_by === TargetSetBy::Clinician
            ? 'food.clinic_range'
            : 'food.self_range';

        return AppLocale::get($key, [
            'low' => $lowDisplay,
            'high' => $highDisplay,
            'unit' => $unit,
        ]);
    }

    /**
     * @param  list<string>  $values
     */
    private static function mean(array $values): string
    {
        $sum = '0';

        foreach ($values as $value) {
            $sum = bcadd($sum, $value, 2);
        }

        return bcdiv($sum, (string) count($values), 2);
    }

    private static function roundToInt(string $value): string
    {
        $adjustment = bccomp($value, '0', 2) >= 0 ? '0.5' : '-0.5';

        return bcadd($value, $adjustment, 0);
    }

    /**
     * @template T of array{sort: string, sample_count: int}
     *
     * @param  list<T>  $rows
     * @return list<T>
     */
    private static function sortRows(array $rows): array
    {
        usort($rows, function (array $left, array $right): int {
            $cmp = bccomp($right['sort'], $left['sort'], 2);

            if ($cmp !== 0) {
                return $cmp;
            }

            return $right['sample_count'] <=> $left['sample_count'];
        });

        return $rows;
    }
}
