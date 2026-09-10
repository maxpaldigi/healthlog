<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * RxNorm name lookup for autocomplete and FHIR coding.
 * Search words only — never name, email, or date of birth.
 * Do not use this class for drug interaction checking.
 */
final class RxNorm
{
    public const ATTRIBUTION = 'Names from RxNorm (NLM).';

    /**
     * @return list<RxNormConcept>
     */
    public function search(string $query): array
    {
        $term = mb_substr(trim($query), 0, 80);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $cacheKey = 'rxnorm:search:'.mb_strtolower($term);

        /** @var list<array{rxcui: string, name: string}> $cached */
        $cached = Cache::remember($cacheKey, now()->addDays(7), function () use ($term): array {
            $payload = $this->fetchJson($this->approximateUrl($term)) ?? [];
            $group = is_array($payload['approximateGroup'] ?? null) ? $payload['approximateGroup'] : [];
            $candidates = $group['candidate'] ?? [];

            if (! is_array($candidates)) {
                return [];
            }

            if (isset($candidates['rxcui'])) {
                $candidates = [$candidates];
            }

            $seen = [];
            $concepts = [];

            foreach ($candidates as $candidate) {
                if (! is_array($candidate)) {
                    continue;
                }

                $rxcui = $this->digits((string) ($candidate['rxcui'] ?? ''));

                if ($rxcui === null || isset($seen[$rxcui])) {
                    continue;
                }

                $seen[$rxcui] = true;
                $concept = $this->properties($rxcui);

                if ($concept === null) {
                    continue;
                }

                $concepts[] = $concept->toArray();

                if (count($concepts) >= 8) {
                    break;
                }
            }

            return $concepts;
        });

        return array_map(
            fn (array $row): RxNormConcept => new RxNormConcept($row['rxcui'], $row['name']),
            $cached,
        );
    }

    public function properties(string $rxcui): ?RxNormConcept
    {
        $id = $this->digits($rxcui);

        if ($id === null) {
            return null;
        }

        $cacheKey = 'rxnorm:rxcui:'.$id;

        /** @var array{rxcui: string, name: string}|array{} $cached */
        $cached = Cache::remember($cacheKey, now()->addDays(30), function () use ($id): array {
            $payload = $this->fetchJson($this->propertiesUrl($id)) ?? [];
            $properties = is_array($payload['properties'] ?? null) ? $payload['properties'] : [];
            $name = trim((string) ($properties['name'] ?? ''));
            $code = $this->digits((string) ($properties['rxcui'] ?? $id));

            if ($name === '' || $code === null) {
                return [];
            }

            return [
                'rxcui' => $code,
                'name' => mb_substr($name, 0, 80),
            ];
        });

        if ($cached === []) {
            return null;
        }

        return new RxNormConcept($cached['rxcui'], $cached['name']);
    }

    /**
     * @return array{name: string, rxcui: string|null}
     */
    public function resolveForSave(?string $rxcui, string $name): array
    {
        $id = $this->digits((string) $rxcui);

        if ($id === null) {
            return [
                'name' => $name,
                'rxcui' => null,
            ];
        }

        $concept = $this->properties($id);

        if ($concept === null) {
            return [
                'name' => $name,
                'rxcui' => $id,
            ];
        }

        return [
            'name' => $concept->name,
            'rxcui' => $concept->rxcui,
        ];
    }

    public function digits(string $value): ?string
    {
        if (preg_match('/^\d{1,20}$/', trim($value)) !== 1) {
            return null;
        }

        return trim($value);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchJson(string $url): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => $this->userAgent(),
                    'Accept' => 'application/json',
                ])
                ->get($url);
        } catch (ConnectionException $exception) {
            report($exception);
            Log::warning('RxNorm request failed.', ['url' => $url]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }

    private function approximateUrl(string $term): string
    {
        return rtrim((string) config('services.rxnorm.base'), '/').'/approximateTerm.json?'.http_build_query([
            'term' => $term,
            'maxEntries' => 8,
        ]);
    }

    private function propertiesUrl(string $rxcui): string
    {
        return rtrim((string) config('services.rxnorm.base'), '/').'/rxcui/'.$rxcui.'/properties.json';
    }

    private function userAgent(): string
    {
        $configured = config('services.rxnorm.user_agent');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return 'HealthyMe/1.0 ('.(string) config('app.url').')';
    }
}
