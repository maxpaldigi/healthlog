<?php

namespace App\Support;

use App\Enums\BpDaySlot;
use App\Enums\GlucoseContext;
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

    /**
     * @return array{
     *     after_meals: list<array{label: string, sample_count: int, average: string, unit: string, line: string}>,
     *     morning_blood_pressure: list<array{label: string, sample_count: int, average: string, line: string}>,
     *     intro: string,
     *     hint: string
     * }
     */
    public static function make(Profile $profile): array
    {
        return [
            'after_meals' => self::afterMeals($profile),
            'morning_blood_pressure' => self::morningBloodPressure($profile),
            'intro' => 'Your numbers after these foods. Check with your clinic.',
            'hint' => 'After 5 times of the same food with a 2-hour sugar reading, a comparison appears here.',
        ];
    }

    /**
     * @return list<array{label: string, sample_count: int, average: string, unit: string, line: string}>
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
                'label' => $group['label'],
                'sample_count' => $count,
                'average' => $average,
                'unit' => $unit,
                'sort' => $meanMgDl,
                'line' => sprintf(
                    'After %s: %s %s avg (%d readings).',
                    $group['label'],
                    $average,
                    $unit,
                    $count,
                ),
            ];
        }

        return array_map(
            fn (array $row): array => [
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
     * @return list<array{label: string, sample_count: int, average: string, line: string}>
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
                'label' => $group['label'],
                'sample_count' => $count,
                'average' => $average,
                'sort' => $average,
                'line' => sprintf(
                    'After %s: morning %s avg (%d readings).',
                    $group['label'],
                    $average,
                    $count,
                ),
            ];
        }

        return array_map(
            fn (array $row): array => [
                'label' => $row['label'],
                'sample_count' => $row['sample_count'],
                'average' => $row['average'],
                'line' => $row['line'],
            ],
            self::sortRows($rows),
        );
    }

    /**
     * @param  list<array{log: FoodLog, sort: string}>  $samples
     * @return list<array{label: string, values: list<string>}>
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

        foreach ($groups as $group) {
            arsort($group['labels']);
            $label = array_key_first($group['labels']);

            $result[] = [
                'label' => is_string($label) ? $label : 'Food',
                'values' => $group['values'],
            ];
        }

        return $result;
    }

    private static function foodKey(FoodLog $log): string
    {
        $ref = $log->source_ref;

        if (is_string($ref) && $ref !== '') {
            return 'off:'.$ref;
        }

        $normalized = preg_replace('/\s+/u', ' ', mb_strtolower(trim($log->label)));

        return 'name:'.($normalized ?? mb_strtolower(trim($log->label)));
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
