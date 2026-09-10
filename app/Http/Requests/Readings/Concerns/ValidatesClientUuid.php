<?php

namespace App\Http\Requests\Readings\Concerns;

trait ValidatesClientUuid
{
    /**
     * @return array<string, mixed>
     */
    protected function clientUuidRules(): array
    {
        return [
            'client_uuid' => ['nullable', 'uuid'],
        ];
    }

    protected function prepareClientUuid(): void
    {
        if ($this->input('client_uuid') === '') {
            $this->merge(['client_uuid' => null]);
        }
    }
}
