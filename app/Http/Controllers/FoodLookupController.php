<?php

namespace App\Http\Controllers;

use App\Actions\Food\ProcessFoodPhoto;
use App\Http\Requests\Food\StoreFoodPhotoRequest;
use App\Support\OpenFoodFacts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FoodLookupController extends Controller
{
    public function search(Request $request, OpenFoodFacts $openFoodFacts): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $locale = $request->getPreferredLanguage() ?? (string) config('app.locale');
        $foods = $openFoodFacts->search($validated['q'], $locale);

        return response()->json([
            'results' => array_map(fn ($food): array => $food->toArray(), $foods),
        ]);
    }

    public function barcode(Request $request, OpenFoodFacts $openFoodFacts): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $food = $openFoodFacts->byBarcode($validated['code']);

        if ($food === null) {
            return response()->json([
                'product' => null,
                'message' => 'No product found for that barcode.',
            ]);
        }

        return response()->json([
            'product' => $food->toArray(),
        ]);
    }

    public function photo(StoreFoodPhotoRequest $request, ProcessFoodPhoto $processFoodPhoto): RedirectResponse
    {
        $photo = $request->file('photo');

        abort_unless($photo !== null, 422);

        $locale = $request->getPreferredLanguage() ?? (string) config('app.locale');

        return $processFoodPhoto->handle($photo, $locale);
    }
}
