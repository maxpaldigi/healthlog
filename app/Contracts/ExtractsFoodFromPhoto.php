<?php

namespace App\Contracts;

use App\Support\IdentifiedFood;

interface ExtractsFoodFromPhoto
{
    public function handle(string $absolutePath, string $mediaType): IdentifiedFood;
}
