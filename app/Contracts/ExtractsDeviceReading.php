<?php

namespace App\Contracts;

use App\Support\ExtractedReading;

interface ExtractsDeviceReading
{
    public function handle(string $absolutePath, string $mediaType, ?string $expect = null): ExtractedReading;
}
