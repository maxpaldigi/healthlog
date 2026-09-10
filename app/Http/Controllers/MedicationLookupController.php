<?php

namespace App\Http\Controllers;

use App\Support\RxNorm;
use App\Support\RxNormConcept;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicationLookupController extends Controller
{
    public function search(Request $request, RxNorm $rxNorm): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        return response()->json([
            'results' => array_map(
                fn (RxNormConcept $concept): array => $concept->toArray(),
                $rxNorm->search($validated['q']),
            ),
            'attribution' => RxNorm::ATTRIBUTION,
        ]);
    }
}
