<?php

namespace App\Http\Controllers;

use App\Actions\Readings\StoreBpReading;
use App\Actions\Readings\UpdateBpReading;
use App\Enums\ReadingTag;
use App\Http\Requests\Readings\StoreBpReadingRequest;
use App\Http\Requests\Readings\UpdateBpReadingRequest;
use App\Models\BpReading;
use App\Support\CurrentProfile;
use App\Support\ReadingSpeech;
use App\Support\ReadingTimeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BpReadingController extends Controller
{
    public function create(Request $request, CurrentProfile $currentProfile): Response
    {
        $profile = $currentProfile->handle($request->user(), $request);

        /** @var array<string, mixed>|null $extraction */
        $extraction = $request->session()->get('extraction');

        return Inertia::render('readings/LogBp', [
            'reading' => null,
            'defaults' => [
                'measured_at' => ReadingTimeline::localDateTimeValue($profile),
                'tag' => ReadingTag::None->value,
                'systolic' => $extraction['systolic'] ?? null,
                'diastolic' => $extraction['diastolic'] ?? null,
                'pulse' => $extraction['pulse'] ?? null,
            ],
            'tags' => $this->tags(),
            'source' => $extraction['source'] ?? 'manual',
            'extractionNote' => $extraction['note'] ?? null,
            'scanStatus' => $request->session()->get('scan_status'),
        ]);
    }

    public function store(StoreBpReadingRequest $request, CurrentProfile $currentProfile, StoreBpReading $storeBpReading): RedirectResponse
    {
        $profile = $currentProfile->handle($request->user(), $request);

        $reading = $storeBpReading->handle($profile, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Blood pressure recorded.',
            'speech' => ReadingSpeech::bloodPressure($reading),
        ]);

        return to_route('home');
    }

    public function edit(Request $request, BpReading $bpReading): Response
    {
        $this->authorize('update', $bpReading);

        $profile = $bpReading->profile;

        return Inertia::render('readings/LogBp', [
            'reading' => [
                'id' => $bpReading->id,
                'systolic' => $bpReading->systolic,
                'diastolic' => $bpReading->diastolic,
                'pulse' => $bpReading->pulse,
                'measured_at' => ReadingTimeline::localDateTimeValue($profile, $bpReading->measured_at),
                'tag' => $bpReading->tag->value,
                'note' => $bpReading->note,
            ],
            'defaults' => null,
            'tags' => $this->tags(),
            'source' => 'manual',
            'extractionNote' => null,
            'scanStatus' => null,
        ]);
    }

    public function update(UpdateBpReadingRequest $request, BpReading $bpReading, UpdateBpReading $updateBpReading): RedirectResponse
    {
        $updateBpReading->handle($bpReading, $request->validated());
        $bpReading->refresh();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Blood pressure updated.',
            'speech' => ReadingSpeech::bloodPressure($bpReading),
        ]);

        return to_route('history');
    }

    public function destroy(Request $request, BpReading $bpReading): RedirectResponse
    {
        $this->authorize('delete', $bpReading);

        $bpReading->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Blood pressure deleted.']);

        return to_route('history');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function tags(): array
    {
        return array_map(
            fn (ReadingTag $tag): array => ['value' => $tag->value, 'label' => $tag->label()],
            ReadingTag::cases(),
        );
    }
}
