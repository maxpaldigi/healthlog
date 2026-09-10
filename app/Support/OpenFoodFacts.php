<?php

namespace App\Support;

use App\Models\OffProduct;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class OpenFoodFacts
{
    /**
     * Worldwide product lookup by barcode. Cached in off_products so the same
     * code is never requested twice. No identifiers are sent.
     */
    public function byBarcode(string $barcode): ?OffFood
    {
        $code = self::normalizeBarcode($barcode);

        if ($code === null) {
            return null;
        }

        $cached = OffProduct::query()->where('barcode', $code)->first();

        if ($cached !== null) {
            return $this->fromModel($cached);
        }

        $payload = $this->fetchJson($this->productUrl($code));

        if ($payload === null || (int) ($payload['status'] ?? 0) !== 1 || ! is_array($payload['product'] ?? null)) {
            return null;
        }

        $food = $this->fromProduct($code, $payload['product']);

        if ($food === null) {
            return null;
        }

        $this->remember($food);

        return $food;
    }

    /**
     * Worldwide name search. Cached by locale + query. No identifiers are sent.
     *
     * @return list<OffFood>
     */
    public function search(string $query, string $locale): array
    {
        $term = mb_substr(trim($query), 0, 80);
        $language = self::languageCode($locale);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $cacheKey = 'off:search:'.$language.':'.mb_strtolower($term);

        /** @var list<array<string, mixed>> $cached */
        $cached = Cache::remember($cacheKey, now()->addDays(7), function () use ($term, $language): array {
            $payload = $this->fetchJson($this->searchUrl($term, $language));

            if ($payload === null || ! is_array($payload['products'] ?? null)) {
                return [];
            }

            $foods = [];

            foreach ($payload['products'] as $product) {
                if (! is_array($product)) {
                    continue;
                }

                $barcode = self::normalizeBarcode((string) ($product['code'] ?? ''));

                if ($barcode === null) {
                    continue;
                }

                $food = $this->fromProduct($barcode, $product);

                if ($food === null) {
                    continue;
                }

                $this->remember($food);
                $foods[] = $food->toArray();

                if (count($foods) >= 12) {
                    break;
                }
            }

            return $foods;
        });

        return array_map(
            fn (array $row): OffFood => $this->fromCachedRow($row),
            $cached,
        );
    }

    public static function normalizeBarcode(string $barcode): ?string
    {
        $digits = preg_replace('/\D+/', '', $barcode) ?? '';

        if (strlen($digits) < 8 || strlen($digits) > 14) {
            return null;
        }

        return $digits;
    }

    public static function languageCode(string $locale): string
    {
        $language = strtolower(str_replace('_', '-', $locale));

        if ($language === '') {
            return 'en';
        }

        $parts = explode('-', $language);
        $code = $parts[0];

        if ($code === '' || strlen($code) > 3) {
            return 'en';
        }

        return $code;
    }

    private function remember(OffFood $food): void
    {
        OffProduct::query()->updateOrCreate(
            ['barcode' => $food->barcode],
            [
                'name' => $food->name,
                'brands' => $food->brands,
                'sodium_mg_100g' => $food->sodiumMg100g,
                'sugars_g_100g' => $food->sugarsG100g,
                'carbs_g_100g' => $food->carbsG100g,
                'nutriscore' => $food->nutriscore,
                'serving_g' => $food->servingG,
                'fetched_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function fromProduct(string $barcode, array $product): ?OffFood
    {
        $name = trim((string) ($product['product_name'] ?? $product['generic_name'] ?? ''));

        if ($name === '') {
            $name = trim((string) ($product['product_name_en'] ?? ''));
        }

        if ($name === '') {
            return null;
        }

        $brands = trim((string) ($product['brands'] ?? ''));
        $nutriments = is_array($product['nutriments'] ?? null) ? $product['nutriments'] : [];
        $grade = strtolower(trim((string) ($product['nutriscore_grade'] ?? '')));

        return new OffFood(
            barcode: $barcode,
            name: mb_substr($name, 0, 180),
            brands: $brands !== '' ? mb_substr($brands, 0, 180) : null,
            sodiumMg100g: $this->sodiumMg($nutriments),
            sugarsG100g: $this->decimal($nutriments['sugars_100g'] ?? null),
            carbsG100g: $this->decimal($nutriments['carbohydrates_100g'] ?? null),
            nutriscore: preg_match('/^[a-e]$/', $grade) === 1 ? $grade : null,
            servingG: $this->servingGrams($product),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function fromCachedRow(array $row): OffFood
    {
        return new OffFood(
            barcode: (string) $row['barcode'],
            name: (string) $row['name'],
            brands: isset($row['brands']) ? (string) $row['brands'] : null,
            sodiumMg100g: isset($row['sodium_mg_100g']) ? (string) $row['sodium_mg_100g'] : null,
            sugarsG100g: isset($row['sugars_g_100g']) ? (string) $row['sugars_g_100g'] : null,
            carbsG100g: isset($row['carbs_g_100g']) ? (string) $row['carbs_g_100g'] : null,
            nutriscore: isset($row['nutriscore']) ? (string) $row['nutriscore'] : null,
            servingG: isset($row['serving_g']) ? (string) $row['serving_g'] : null,
        );
    }

    private function fromModel(OffProduct $product): OffFood
    {
        return new OffFood(
            barcode: $product->barcode,
            name: $product->name,
            brands: $product->brands,
            sodiumMg100g: $product->sodium_mg_100g !== null ? (string) $product->sodium_mg_100g : null,
            sugarsG100g: $product->sugars_g_100g !== null ? (string) $product->sugars_g_100g : null,
            carbsG100g: $product->carbs_g_100g !== null ? (string) $product->carbs_g_100g : null,
            nutriscore: $product->nutriscore,
            servingG: $product->serving_g !== null ? (string) $product->serving_g : null,
        );
    }

    /**
     * @param  array<string, mixed>  $nutriments
     */
    private function sodiumMg(array $nutriments): ?string
    {
        $sodiumG = $this->decimal($nutriments['sodium_100g'] ?? null);

        if ($sodiumG !== null) {
            return bcmul($sodiumG, '1000', 2);
        }

        $saltG = $this->decimal($nutriments['salt_100g'] ?? null);

        if ($saltG !== null) {
            return bcmul($saltG, '400', 2);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function servingGrams(array $product): ?string
    {
        $quantity = $this->decimal($product['serving_quantity'] ?? null);

        if ($quantity !== null) {
            return $quantity;
        }

        $size = trim((string) ($product['serving_size'] ?? ''));

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*g\b/i', $size, $matches) === 1) {
            return $this->decimal(str_replace(',', '.', $matches[1]));
        }

        return null;
    }

    private function decimal(mixed $value): ?string
    {
        if (is_int($value)) {
            return bcadd((string) $value, '0', 2);
        }

        if (is_float($value)) {
            return bcadd(sprintf('%.6F', $value), '0', 2);
        }

        if (is_string($value) && is_numeric($value)) {
            return bcadd(str_replace(',', '.', $value), '0', 2);
        }

        return null;
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
            Log::warning('Open Food Facts request failed.', ['url' => $url]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }

    private function productUrl(string $barcode): string
    {
        return rtrim((string) config('services.open_food_facts.base'), '/').'/api/v2/product/'.$barcode.'.json';
    }

    private function searchUrl(string $term, string $language): string
    {
        return rtrim((string) config('services.open_food_facts.base'), '/').'/cgi/search.pl?'.http_build_query([
            'search_terms' => $term,
            'search_simple' => 1,
            'action' => 'process',
            'json' => 1,
            'page_size' => 20,
            'lc' => $language,
        ]);
    }

    private function userAgent(): string
    {
        $configured = config('services.open_food_facts.user_agent');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return 'HealthyMe/1.0 ('.(string) config('app.url').')';
    }
}
