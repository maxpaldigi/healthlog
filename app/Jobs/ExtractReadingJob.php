<?php

namespace App\Jobs;

use App\Contracts\ExtractsDeviceReading;
use App\Exceptions\CouldNotReadPhotoException;
use App\Support\ExtractedReading;
use App\Support\PrepareScanImage;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExtractReadingJob
{
    use Queueable;

    public function __construct(public string $path, public ?string $expect = null) {}

    public function handle(ExtractsDeviceReading $extract): ExtractedReading
    {
        $absolutePath = Storage::disk('local')->path($this->path);
        $convertedPath = null;

        try {
            $prepared = PrepareScanImage::handle($absolutePath, (string) mime_content_type($absolutePath));
            $convertedPath = $prepared['path'] !== $absolutePath ? $prepared['path'] : null;

            return $extract->handle($prepared['path'], $prepared['media_type'], $this->expect);
        } catch (CouldNotReadPhotoException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw CouldNotReadPhotoException::make();
        } finally {
            Storage::disk('local')->delete($this->path);

            if (is_string($convertedPath) && is_file($convertedPath)) {
                unlink($convertedPath);
            }
        }
    }
}
