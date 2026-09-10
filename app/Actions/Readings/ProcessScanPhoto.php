<?php

namespace App\Actions\Readings;

use App\Exceptions\CouldNotReadPhotoException;
use App\Jobs\ExtractReadingJob;
use App\Models\Profile;
use App\Support\ExtractedReading;
use App\Support\ValidateExtractedReading;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Throwable;

class ProcessScanPhoto
{
    public function handle(UploadedFile $photo, Profile $profile, ?string $expect = null): RedirectResponse
    {
        set_time_limit(45);

        $path = $photo->store('scans', 'local');

        if ($path === false) {
            return $this->failed($expect);
        }

        try {
            $extracted = ExtractReadingJob::dispatchSync($path, $expect);

            if (! $extracted instanceof ExtractedReading) {
                return $this->failed($expect);
            }
        } catch (CouldNotReadPhotoException $exception) {
            return $this->failed($expect, $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->failed($expect);
        }

        $decision = ValidateExtractedReading::handle($extracted, $profile);

        if ($decision['extraction'] === null) {
            return $this->failed($expect, $decision['message']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $decision['message']]);

        return redirect()
            ->route($decision['route'])
            ->with('extraction', $decision['extraction']);
    }

    private function failed(?string $expect, ?string $message = null): RedirectResponse
    {
        $message ??= CouldNotReadPhotoException::make()->getMessage();

        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        if ($expect === 'glucose') {
            return redirect()->route('glucose.create')->with('scan_status', $message);
        }

        if ($expect === 'bp') {
            return redirect()->route('bp.create')->with('scan_status', $message);
        }

        return redirect()->route('scan')->with('scan_status', $message);
    }
}
