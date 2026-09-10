<?php

namespace App\Actions\Food;

use App\Contracts\ExtractsFoodFromPhoto;
use App\Exceptions\CouldNotReadPhotoException;
use App\Support\IdentifiedFood;
use App\Support\OffFood;
use App\Support\OpenFoodFacts;
use App\Support\PrepareScanImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Throwable;

class ProcessFoodPhoto
{
    public function __construct(
        private ExtractsFoodFromPhoto $extract,
        private OpenFoodFacts $openFoodFacts,
    ) {}

    public function handle(UploadedFile $photo, string $locale): RedirectResponse
    {
        set_time_limit(45);

        $path = $photo->store('scans', 'local');

        if ($path === false) {
            return $this->failed();
        }

        $absolutePath = Storage::disk('local')->path($path);
        $convertedPath = null;

        try {
            $prepared = PrepareScanImage::handle($absolutePath, (string) mime_content_type($absolutePath));
            $convertedPath = $prepared['path'] !== $absolutePath ? $prepared['path'] : null;

            $identified = $this->extract->handle($prepared['path'], $prepared['media_type']);

            if ($identified->label === '') {
                return $this->failed();
            }

            $payload = $this->payload($identified, $locale);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => $payload['note'],
            ]);

            return redirect()
                ->route('food.create')
                ->with('food_photo', $payload);
        } catch (CouldNotReadPhotoException $exception) {
            return $this->failed($exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->failed();
        } finally {
            Storage::disk('local')->delete($path);

            if (is_string($convertedPath) && is_file($convertedPath)) {
                unlink($convertedPath);
            }
        }
    }

    /**
     * @return array{
     *     label: string,
     *     note: string,
     *     confidence: string,
     *     product: array<string, mixed>|null,
     *     results: list<array<string, mixed>>
     * }
     */
    private function payload(IdentifiedFood $identified, string $locale): array
    {
        $product = null;

        if ($identified->barcode !== null) {
            $product = $this->openFoodFacts->byBarcode($identified->barcode);
        }

        $results = [];

        if ($product === null) {
            $results = $this->openFoodFacts->search($identified->label, $locale);
        }

        return [
            'label' => $identified->label,
            'note' => $identified->checkNote(),
            'confidence' => $identified->confidence,
            'product' => $product?->toArray(),
            'results' => array_map(fn (OffFood $food): array => $food->toArray(), $results),
        ];
    }

    private function failed(?string $message = null): RedirectResponse
    {
        $message ??= "Couldn't tell what that food is. Search by name or type it.";

        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return redirect()->route('food.create')->with('scan_status', $message);
    }
}
