<?php

namespace App\Support;

final readonly class RxNormConcept
{
    public function __construct(
        public string $rxcui,
        public string $name,
    ) {}

    /**
     * @return array{rxcui: string, name: string}
     */
    public function toArray(): array
    {
        return [
            'rxcui' => $this->rxcui,
            'name' => $this->name,
        ];
    }
}
