<?php

namespace App\Support;

use App\Enums\GlucoseUnit;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Hba1cResult;
use App\Models\Medication;
use App\Models\Profile;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maps stored readings to a FHIR R4 Bundle for clinic systems.
 * Display only — never a diagnosis. Numbers and names only; no range labels.
 */
final class FhirExport
{
    public const LOINC_SYSTOLIC = '8480-6';

    public const LOINC_DIASTOLIC = '8462-4';

    public const LOINC_BP_PANEL = '85354-9';

    public const LOINC_HBA1C = '4548-4';

    public const LOINC_GLUCOSE_MG_DL = '2339-0';

    public const LOINC_GLUCOSE_MMOL = '15074-8';

    public const RXNORM_SYSTEM = 'http://www.nlm.nih.gov/research/umls/rxnorm';

    /**
     * @param  Collection<int, BpReading>  $bpReadings
     * @param  Collection<int, GlucoseReading>  $glucoseReadings
     * @param  Collection<int, Hba1cResult>  $hba1cResults
     * @param  Collection<int, Medication>  $medications
     * @return array<string, mixed>
     */
    public static function make(
        Profile $profile,
        string $from,
        string $to,
        Collection $bpReadings,
        Collection $glucoseReadings,
        Collection $hba1cResults,
        Collection $medications,
    ): array {
        $entries = [
            self::entry(self::patient($profile)),
        ];

        foreach ($bpReadings as $reading) {
            $entries[] = self::entry(self::bloodPressure($profile, $reading));
        }

        foreach ($glucoseReadings as $reading) {
            $entries[] = self::entry(self::glucose($profile, $reading));
        }

        foreach ($hba1cResults as $result) {
            $entries[] = self::entry(self::hba1c($profile, $result));
        }

        foreach ($medications as $medication) {
            $entries[] = self::entry(self::medicationStatement($profile, $medication));
        }

        return [
            'resourceType' => 'Bundle',
            'type' => 'collection',
            'timestamp' => now()->utc()->toAtomString(),
            'identifier' => [
                'system' => rtrim((string) config('app.url'), '/').'/fhir',
                'value' => $from.'_'.$to,
            ],
            'meta' => [
                'source' => rtrim((string) config('app.url'), '/'),
            ],
            'entry' => $entries,
        ];
    }

    /**
     * @param  array<string, mixed>  $bundle
     */
    public static function download(array $bundle, string $from, string $to): Response
    {
        return response(self::encode($bundle), 200, [
            'Content-Type' => 'application/fhir+json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.self::filename($from, $to).'"',
        ]);
    }

    public static function filename(string $from, string $to): string
    {
        return 'healthyme-'.$from.'-to-'.$to.'.fhir.json';
    }

    /**
     * @param  array<string, mixed>  $bundle
     */
    public static function encode(array $bundle): string
    {
        return json_encode($bundle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array{fullUrl: string, resource: array<string, mixed>}
     */
    private static function entry(array $resource): array
    {
        $type = (string) $resource['resourceType'];
        $id = (string) $resource['id'];

        return [
            'fullUrl' => $type.'/'.$id,
            'resource' => $resource,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function patient(Profile $profile): array
    {
        return [
            'resourceType' => 'Patient',
            'id' => self::patientId($profile),
            'name' => [
                ['text' => $profile->name],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function bloodPressure(Profile $profile, BpReading $reading): array
    {
        $observation = [
            'resourceType' => 'Observation',
            'id' => 'bp-'.$reading->id,
            'status' => 'final',
            'category' => [self::category('vital-signs', 'Vital Signs')],
            'code' => self::loinc(self::LOINC_BP_PANEL, 'Blood pressure panel'),
            'subject' => self::subject($profile),
            'effectiveDateTime' => self::dateTime($profile, $reading->measured_at),
            'component' => [
                [
                    'code' => self::loinc(self::LOINC_SYSTOLIC, 'Systolic blood pressure'),
                    'valueQuantity' => self::quantity($reading->systolic, 'mmHg', 'mm[Hg]'),
                ],
                [
                    'code' => self::loinc(self::LOINC_DIASTOLIC, 'Diastolic blood pressure'),
                    'valueQuantity' => self::quantity($reading->diastolic, 'mmHg', 'mm[Hg]'),
                ],
            ],
        ];

        $note = self::noteText($reading->note);

        if ($note !== null) {
            $observation['note'] = [['text' => $note]];
        }

        return $observation;
    }

    /**
     * @return array<string, mixed>
     */
    private static function glucose(Profile $profile, GlucoseReading $reading): array
    {
        $unit = $profile->glucose_unit;
        $display = Glucose::fromMgDl((string) $reading->value_mg_dl, $unit);
        $loinc = $unit === GlucoseUnit::MmolL ? self::LOINC_GLUCOSE_MMOL : self::LOINC_GLUCOSE_MG_DL;
        $ucum = $unit === GlucoseUnit::MmolL ? 'mmol/L' : 'mg/dL';
        $note = self::noteText($reading->context->label(), $reading->note);

        $observation = [
            'resourceType' => 'Observation',
            'id' => 'glucose-'.$reading->id,
            'status' => 'final',
            'category' => [self::category('laboratory', 'Laboratory')],
            'code' => self::loinc($loinc, 'Glucose'),
            'subject' => self::subject($profile),
            'effectiveDateTime' => self::dateTime($profile, $reading->measured_at),
            'valueQuantity' => self::quantity(self::number($display), $unit->label(), $ucum),
        ];

        if ($note !== null) {
            $observation['note'] = [['text' => $note]];
        }

        return $observation;
    }

    /**
     * @return array<string, mixed>
     */
    private static function hba1c(Profile $profile, Hba1cResult $result): array
    {
        $observation = [
            'resourceType' => 'Observation',
            'id' => 'hba1c-'.$result->id,
            'status' => 'final',
            'category' => [self::category('laboratory', 'Laboratory')],
            'code' => self::loinc(self::LOINC_HBA1C, 'Hemoglobin A1c/Hemoglobin.total in Blood'),
            'subject' => self::subject($profile),
            'effectiveDateTime' => $result->tested_at->toDateString(),
            'valueQuantity' => self::quantity(self::number((string) $result->value), '%', '%'),
        ];

        $note = self::noteText($result->lab_name);

        if ($note !== null) {
            $observation['note'] = [['text' => $note]];
        }

        return $observation;
    }

    /**
     * @return array<string, mixed>
     */
    private static function medicationStatement(Profile $profile, Medication $medication): array
    {
        $statement = [
            'resourceType' => 'MedicationStatement',
            'id' => 'med-'.$medication->id,
            'status' => $medication->is_active ? 'active' : 'stopped',
            'medicationCodeableConcept' => self::medicationConcept($medication),
            'subject' => self::subject($profile),
        ];

        $period = self::medicationPeriod($medication);

        if ($period !== []) {
            $statement['effectivePeriod'] = $period;
        }

        $dosage = trim(implode(' · ', array_filter([
            $medication->dose,
            $medication->schedule,
        ], fn (?string $part): bool => filled($part))));

        if ($dosage !== '') {
            $statement['dosage'] = [['text' => $dosage]];
        }

        return $statement;
    }

    /**
     * @return array<string, mixed>
     */
    private static function medicationConcept(Medication $medication): array
    {
        $concept = [
            'text' => $medication->name,
        ];

        if (filled($medication->rxcui)) {
            $concept['coding'] = [[
                'system' => self::RXNORM_SYSTEM,
                'code' => (string) $medication->rxcui,
                'display' => $medication->name,
            ]];
        }

        return $concept;
    }

    /**
     * @return array<string, string>
     */
    private static function medicationPeriod(Medication $medication): array
    {
        $period = [];

        if ($medication->started_on !== null) {
            $period['start'] = $medication->started_on->toDateString();
        }

        if ($medication->stopped_on !== null) {
            $period['end'] = $medication->stopped_on->toDateString();
        }

        return $period;
    }

    /**
     * @return array{reference: string}
     */
    private static function subject(Profile $profile): array
    {
        return ['reference' => 'Patient/'.self::patientId($profile)];
    }

    private static function patientId(Profile $profile): string
    {
        return 'patient-'.$profile->id;
    }

    /**
     * @return array{coding: list<array{system: string, code: string, display: string}>}
     */
    private static function category(string $code, string $display): array
    {
        return [
            'coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                'code' => $code,
                'display' => $display,
            ]],
        ];
    }

    /**
     * @return array{coding: list<array{system: string, code: string, display: string}>}
     */
    private static function loinc(string $code, string $display): array
    {
        return [
            'coding' => [[
                'system' => 'http://loinc.org',
                'code' => $code,
                'display' => $display,
            ]],
        ];
    }

    /**
     * @return array{value: int|float, unit: string, system: string, code: string}
     */
    private static function quantity(int|float $value, string $unit, string $ucum): array
    {
        return [
            'value' => $value,
            'unit' => $unit,
            'system' => 'http://unitsofmeasure.org',
            'code' => $ucum,
        ];
    }

    private static function dateTime(Profile $profile, DateTimeInterface $measuredAt): string
    {
        $timezone = is_string($profile->timezone) && $profile->timezone !== ''
            ? $profile->timezone
            : $profile->clockTimezone();

        return Carbon::parse($measuredAt)->timezone($timezone)->toAtomString();
    }

    private static function number(string $value): float
    {
        $decoded = json_decode($value);

        return is_int($decoded) || is_float($decoded) ? $decoded : 0.0;
    }

    private static function noteText(?string ...$parts): ?string
    {
        $text = trim(implode('. ', array_filter(
            $parts,
            fn (?string $part): bool => filled($part),
        )));

        return $text === '' ? null : $text;
    }
}
