<?php

namespace App\Http\Controllers;

use App\Actions\Food\StoreFoodLog;
use App\Enums\FoodSource;
use App\Http\Requests\Food\StoreFoodLogRequest;
use App\Models\FoodLog;
use App\Support\CurrentProfile;
use App\Support\FoodPortions;
use App\Support\NutritionFacts;
use App\Support\ReadingTimeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FoodLogController extends Controller
{
    public function create(Request $request, CurrentProfile $currentProfile): Response
    {
        $profile = $currentProfile->handle($request->user(), $request);

        $recent = $profile->foodLogs()
            ->orderByDesc('eaten_at')
            ->limit(12)
            ->get()
            ->map(fn (FoodLog $log): array => [
                'id' => $log->id,
                'label' => $log->label,
                'portion_label' => $log->portion_label,
                'portion_g' => NutritionFacts::display((string) $log->portion_g),
                'eaten_at_label' => $log->eaten_at->timezone($profile->clockTimezone())->format('j M, g:ia'),
                'attribution' => $log->source === FoodSource::OpenFoodFacts
                    ? 'Data from Open Food Facts (ODbL)'
                    : null,
            ])
            ->values()
            ->all();

        return Inertia::render('food/LogFood', [
            'defaults' => [
                'eaten_at' => ReadingTimeline::localDateTimeValue($profile),
            ],
            'whoReference' => NutritionFacts::whoSaltReference(),
            'portions' => FoodPortions::presets(null),
            'recent' => $recent,
            'identified' => $this->identifiedFromSession($request),
            'scanStatus' => $request->session()->get('scan_status'),
        ]);
    }

    public function store(StoreFoodLogRequest $request, CurrentProfile $currentProfile, StoreFoodLog $storeFoodLog): RedirectResponse
    {
        $profile = $currentProfile->handle($request->user(), $request);
        $log = $storeFoodLog->handle($profile, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Food saved.',
            'speech' => sprintf('Saved %s. %s grams.', $log->label, NutritionFacts::display((string) $log->portion_g)),
        ]);

        return to_route('food.create');
    }

    public function destroy(Request $request, FoodLog $foodLog): RedirectResponse
    {
        $this->authorize('delete', $foodLog);

        $foodLog->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Food removed.']);

        return to_route('food.create');
    }

    /**
     * @return array{
     *     label: string,
     *     note: string,
     *     confidence: string,
     *     product: array<string, mixed>|null,
     *     results: list<mixed>
     * }|null
     */
    private function identifiedFromSession(Request $request): ?array
    {
        $payload = $request->session()->pull('food_photo');

        if (! is_array($payload) || ! is_string($payload['label'] ?? null) || $payload['label'] === '') {
            return null;
        }

        $results = $payload['results'] ?? [];

        return [
            'label' => $payload['label'],
            'note' => is_string($payload['note'] ?? null) ? $payload['note'] : '',
            'confidence' => is_string($payload['confidence'] ?? null) ? $payload['confidence'] : 'low',
            'product' => is_array($payload['product'] ?? null) ? $payload['product'] : null,
            'results' => is_array($results) ? array_values($results) : [],
        ];
    }
}
