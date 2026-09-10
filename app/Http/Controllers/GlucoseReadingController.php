<?php

namespace App\Http\Controllers;

use App\Actions\Readings\StoreGlucoseReading;
use App\Actions\Readings\UpdateGlucoseReading;
use App\Enums\GlucoseContext;
use App\Http\Requests\Readings\StoreGlucoseReadingRequest;
use App\Http\Requests\Readings\UpdateGlucoseReadingRequest;
use App\Models\GlucoseReading;
use App\Support\CurrentProfile;
use App\Support\Glucose;
use App\Support\ReadingSpeech;
use App\Support\ReadingTimeline;
use App\Support\SuggestGlucoseContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GlucoseReadingController extends Controller
{
    public function create(Request $request, CurrentProfile $currentProfile): Response
    {
        $profile = $currentProfile->handle($request->user(), $request);

        /** @var array<string, mixed>|null $extraction */
        $extraction = $request->session()->get('extraction');

        return Inertia::render('readings/LogSugar', [
            'reading' => null,
            'defaults' => [
                'measured_at' => ReadingTimeline::localDateTimeValue($profile),
                'context' => SuggestGlucoseContext::forLocalTime($profile->nowInTimezone())->value,
                'value' => $extraction['value'] ?? null,
            ],
            'contexts' => $this->contexts(),
            'unit' => $profile->glucose_unit->label(),
            'unitValue' => $profile->glucose_unit->value,
            'source' => $extraction['source'] ?? 'manual',
            'extractionNote' => $extraction['note'] ?? null,
            'scanStatus' => $request->session()->get('scan_status'),
        ]);
    }

    public function store(StoreGlucoseReadingRequest $request, CurrentProfile $currentProfile, StoreGlucoseReading $storeGlucoseReading): RedirectResponse
    {
        $profile = $currentProfile->handle($request->user(), $request);

        $reading = $storeGlucoseReading->handle($profile, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Glucose recorded.',
            'speech' => ReadingSpeech::glucose($profile, $reading),
        ]);

        return to_route('home');
    }

    public function edit(Request $request, GlucoseReading $glucoseReading): Response
    {
        $this->authorize('update', $glucoseReading);

        $profile = $glucoseReading->profile;

        return Inertia::render('readings/LogSugar', [
            'reading' => [
                'id' => $glucoseReading->id,
                'value' => Glucose::fromMgDl((string) $glucoseReading->value_mg_dl, $profile->glucose_unit),
                'context' => $glucoseReading->context->value,
                'note' => $glucoseReading->note,
                'measured_at' => ReadingTimeline::localDateTimeValue($profile, $glucoseReading->measured_at),
            ],
            'defaults' => null,
            'contexts' => $this->contexts(),
            'unit' => $profile->glucose_unit->label(),
            'unitValue' => $profile->glucose_unit->value,
            'source' => 'manual',
            'extractionNote' => null,
            'scanStatus' => null,
        ]);
    }

    public function update(UpdateGlucoseReadingRequest $request, GlucoseReading $glucoseReading, UpdateGlucoseReading $updateGlucoseReading): RedirectResponse
    {
        $updateGlucoseReading->handle($glucoseReading, $request->validated());
        $glucoseReading->refresh();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Glucose updated.',
            'speech' => ReadingSpeech::glucose($glucoseReading->profile, $glucoseReading),
        ]);

        return to_route('history');
    }

    public function destroy(Request $request, GlucoseReading $glucoseReading): RedirectResponse
    {
        $this->authorize('delete', $glucoseReading);

        $glucoseReading->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Glucose deleted.']);

        return to_route('history');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function contexts(): array
    {
        return array_map(
            fn (GlucoseContext $context): array => ['value' => $context->value, 'label' => $context->label()],
            GlucoseContext::cases(),
        );
    }
}
