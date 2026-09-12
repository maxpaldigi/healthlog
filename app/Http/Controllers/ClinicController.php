<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clinic\StoreHba1cRequest;
use App\Http\Requests\Clinic\StoreMedicationRequest;
use App\Http\Requests\Clinic\StoreWeightRequest;
use App\Models\Hba1cResult;
use App\Models\Medication;
use App\Models\Weight;
use App\Support\BodyCheckIn;
use App\Support\BodyWeight;
use App\Support\CurrentProfile;
use App\Support\ReadingTimeline;
use App\Support\RxNorm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ClinicController extends Controller
{
    public function index(Request $request, CurrentProfile $currentProfile): Response
    {
        $profile = $currentProfile->handle($request->user(), $request);

        return Inertia::render('clinic/Clinic', [
            'unit' => $profile->weight_unit->label(),
            'defaults' => [
                'measured_at' => ReadingTimeline::localDateTimeValue($profile),
                'tested_at' => $profile->nowInTimezone()->toDateString(),
            ],
            'weights' => $profile->weights()->orderByDesc('measured_at')->limit(12)->get()->map(fn (Weight $weight): array => BodyCheckIn::weightPayload(
                $profile,
                $weight,
                $weight->measured_at->timezone($profile->timezone)->format('j M Y, g:ia'),
            )),
            'hba1c' => $profile->hba1cResults()->orderByDesc('tested_at')->limit(12)->get()->map(fn (Hba1cResult $result): array => [
                'id' => $result->id,
                'value' => (string) $result->value,
                'tested_at' => $result->tested_at->format('j M Y'),
                'lab_name' => $result->lab_name,
            ]),
            'medications' => $profile->medications()->orderByDesc('is_active')->orderBy('name')->get()->map(fn (Medication $medication): array => [
                'id' => $medication->id,
                'name' => $medication->name,
                'rxcui' => $medication->rxcui,
                'dose' => $medication->dose,
                'schedule' => $medication->schedule,
                'is_active' => $medication->is_active,
            ]),
        ]);
    }

    public function storeWeight(StoreWeightRequest $request, CurrentProfile $currentProfile): RedirectResponse
    {
        $profile = $currentProfile->handle($request->user(), $request);

        $profile->weights()->create([
            'kg' => BodyWeight::toKg((string) $request->validated('value'), $profile->weight_unit),
            'measured_at' => Carbon::parse($request->validated('measured_at'), $profile->clockTimezone())->utc(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Weight saved.']);

        return to_route('clinic.index');
    }

    public function destroyWeight(Request $request, Weight $weight): RedirectResponse
    {
        $this->authorize('delete', $weight);

        $weight->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Weight deleted.']);

        return to_route('clinic.index');
    }

    public function storeHba1c(StoreHba1cRequest $request, CurrentProfile $currentProfile): RedirectResponse
    {
        $profile = $currentProfile->handle($request->user(), $request);
        $data = $request->validated();

        $profile->hba1cResults()->create([
            'value' => $data['value'],
            'tested_at' => $data['tested_at'],
            'lab_name' => filled($data['lab_name'] ?? null) ? $data['lab_name'] : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'HbA1c saved.']);

        return to_route('clinic.index');
    }

    public function destroyHba1c(Request $request, Hba1cResult $hba1cResult): RedirectResponse
    {
        $this->authorize('delete', $hba1cResult);

        $hba1cResult->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'HbA1c deleted.']);

        return to_route('clinic.index');
    }

    public function storeMedication(StoreMedicationRequest $request, CurrentProfile $currentProfile, RxNorm $rxNorm): RedirectResponse
    {
        $profile = $currentProfile->handle($request->user(), $request);
        $data = $request->validated();
        $resolved = $rxNorm->resolveForSave(
            isset($data['rxcui']) ? (string) $data['rxcui'] : null,
            (string) $data['name'],
        );

        $profile->medications()->create([
            'name' => $resolved['name'],
            'rxcui' => $resolved['rxcui'],
            'dose' => filled($data['dose'] ?? null) ? $data['dose'] : null,
            'schedule' => filled($data['schedule'] ?? null) ? $data['schedule'] : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Medication saved.']);

        return to_route('clinic.index');
    }

    public function updateMedication(StoreMedicationRequest $request, Medication $medication, RxNorm $rxNorm): RedirectResponse
    {
        $this->authorize('update', $medication);

        $data = $request->validated();
        $resolved = $rxNorm->resolveForSave(
            isset($data['rxcui']) ? (string) $data['rxcui'] : null,
            (string) $data['name'],
        );

        $medication->update([
            'name' => $resolved['name'],
            'rxcui' => $resolved['rxcui'],
            'dose' => filled($data['dose'] ?? null) ? $data['dose'] : null,
            'schedule' => filled($data['schedule'] ?? null) ? $data['schedule'] : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Medication updated.']);

        return to_route('clinic.index');
    }

    public function destroyMedication(Request $request, Medication $medication): RedirectResponse
    {
        $this->authorize('delete', $medication);

        $medication->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Medication deleted.']);

        return to_route('clinic.index');
    }
}
