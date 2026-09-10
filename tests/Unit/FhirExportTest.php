<?php

use App\Enums\GlucoseContext;
use App\Enums\GlucoseUnit;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Support\FhirExport;
use App\Support\Glucose;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('maps blood pressure, glucose, hba1c, and medications to a fhir collection', function () {
    $this->travelTo(Carbon::parse('2026-09-10 12:00:00', 'UTC'));

    $user = userWithProfile(['email' => 'parent@example.com'], [
        'name' => 'Ramesh',
        'timezone' => 'UTC',
        'date_of_birth' => '1950-01-15',
        'glucose_unit' => GlucoseUnit::MgDl,
    ]);
    $profile = $user->defaultProfile;

    $bp = BpReading::factory()->for($profile)->create([
        'systolic' => 120,
        'diastolic' => 80,
        'note' => 'At clinic',
        'measured_at' => Carbon::parse('2026-09-08 08:00:00', 'UTC'),
    ]);
    $glucose = GlucoseReading::factory()->for($profile)->create([
        'value_mg_dl' => '100.00',
        'context' => GlucoseContext::Fasting,
        'note' => 'Before tea',
        'measured_at' => Carbon::parse('2026-09-08 07:30:00', 'UTC'),
    ]);
    $hba1c = $profile->hba1cResults()->create([
        'value' => '6.50',
        'tested_at' => '2026-09-01',
        'lab_name' => 'City Lab',
    ]);
    $medication = $profile->medications()->create([
        'name' => 'metformin',
        'rxcui' => '6809',
        'dose' => '500 mg',
        'schedule' => 'Morning',
        'is_active' => true,
    ]);

    $bundle = FhirExport::make(
        $profile,
        '2026-09-01',
        '2026-09-08',
        $profile->bpReadings()->orderBy('measured_at')->get(),
        $profile->glucoseReadings()->orderBy('measured_at')->get(),
        $profile->hba1cResults()->orderBy('tested_at')->get(),
        $profile->medications()->orderBy('name')->get(),
    );

    $json = FhirExport::encode($bundle);

    expect($bundle['resourceType'])->toBe('Bundle')
        ->and($bundle['type'])->toBe('collection')
        ->and($json)->not->toContain('parent@example.com')
        ->and($json)->not->toContain('1950-01-15')
        ->and($json)->not->toContain('High')
        ->and($json)->not->toContain('diagnose');

    $patient = $bundle['entry'][0]['resource'];
    $bpResource = collect($bundle['entry'])->pluck('resource')->firstWhere('id', 'bp-'.$bp->id);
    $glucoseResource = collect($bundle['entry'])->pluck('resource')->firstWhere('id', 'glucose-'.$glucose->id);
    $hba1cResource = collect($bundle['entry'])->pluck('resource')->firstWhere('id', 'hba1c-'.$hba1c->id);
    $medResource = collect($bundle['entry'])->pluck('resource')->firstWhere('id', 'med-'.$medication->id);

    expect($patient['resourceType'])->toBe('Patient')
        ->and($patient['name'][0]['text'])->toBe('Ramesh')
        ->and($patient)->not->toHaveKey('birthDate')
        ->and($bpResource['component'][0]['code']['coding'][0]['code'])->toBe(FhirExport::LOINC_SYSTOLIC)
        ->and($bpResource['component'][1]['code']['coding'][0]['code'])->toBe(FhirExport::LOINC_DIASTOLIC)
        ->and($bpResource['component'][0]['valueQuantity'])->toMatchArray([
            'value' => 120,
            'unit' => 'mmHg',
            'code' => 'mm[Hg]',
        ])
        ->and($bpResource['note'][0]['text'])->toBe('At clinic')
        ->and($glucoseResource['code']['coding'][0]['code'])->toBe(FhirExport::LOINC_GLUCOSE_MG_DL)
        ->and($glucoseResource['valueQuantity'])->toMatchArray([
            'value' => 100.0,
            'unit' => 'mg/dL',
            'code' => 'mg/dL',
        ])
        ->and($glucoseResource['note'][0]['text'])->toBe('Fasting. Before tea')
        ->and($hba1cResource['code']['coding'][0]['code'])->toBe(FhirExport::LOINC_HBA1C)
        ->and($hba1cResource['valueQuantity'])->toMatchArray([
            'value' => 6.5,
            'unit' => '%',
            'code' => '%',
        ])
        ->and($medResource['resourceType'])->toBe('MedicationStatement')
        ->and($medResource['status'])->toBe('active')
        ->and($medResource['medicationCodeableConcept']['coding'][0])->toMatchArray([
            'system' => FhirExport::RXNORM_SYSTEM,
            'code' => '6809',
            'display' => 'metformin',
        ])
        ->and($medResource['dosage'][0]['text'])->toBe('500 mg · Morning');

    $this->travelBack();
});

it('exports glucose in the profile unit with the matching loinc code', function () {
    $user = userWithProfile([], [
        'timezone' => 'UTC',
        'glucose_unit' => GlucoseUnit::MmolL,
    ]);
    $profile = $user->defaultProfile;

    GlucoseReading::factory()->for($profile)->create([
        'value_mg_dl' => '180.00',
        'context' => GlucoseContext::PostMeal2h,
        'measured_at' => Carbon::parse('2026-09-08 14:00:00', 'UTC'),
    ]);

    $bundle = FhirExport::make(
        $profile,
        '2026-09-08',
        '2026-09-08',
        collect(),
        $profile->glucoseReadings()->get(),
        collect(),
        collect(),
    );

    $observation = $bundle['entry'][1]['resource'];

    expect($observation['code']['coding'][0]['code'])->toBe(FhirExport::LOINC_GLUCOSE_MMOL)
        ->and($observation['valueQuantity']['code'])->toBe('mmol/L')
        ->and($observation['valueQuantity']['value'])->toBe((float) Glucose::fromMgDl('180.00', GlucoseUnit::MmolL))
        ->and($observation['note'][0]['text'])->toBe('After meal 2h');
});
