<?php

namespace App\Support;

final readonly class IdentifiedFood
{
    /**
     * @param  'high'|'medium'|'low'  $confidence
     */
    public function __construct(
        public string $label,
        public ?string $barcode,
        public string $confidence,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $confidence = $data['confidence'] ?? 'low';

        if (! in_array($confidence, ['high', 'medium', 'low'], true)) {
            $confidence = 'low';
        }

        $rawBarcode = $data['barcode'] ?? '';
        $barcode = is_string($rawBarcode) || is_int($rawBarcode)
            ? OpenFoodFacts::normalizeBarcode((string) $rawBarcode)
            : null;

        return new self(
            label: self::sanitizeLabel($data['label'] ?? ''),
            barcode: $barcode,
            confidence: $confidence,
        );
    }

    /**
     * Descriptive check copy only — never a diagnosis or instruction.
     */
    public function checkNote(): string
    {
        if ($this->label === '') {
            return "Couldn't tell what that food is. Search by name or type it.";
        }

        if ($this->confidence === 'low') {
            return 'This might be '.$this->label.'. Check, then save.';
        }

        return 'This looks like '.$this->label.'. Check, then save.';
    }

    private static function sanitizeLabel(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $label = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        $label = mb_substr($label, 0, 80);

        if ($label === '') {
            return '';
        }

        if (preg_match('/\b(diagnos(?:e|is|tic)?|treat(?:ment)?|prescrib(?:e|ed)?|medical advice|avoid this|too much|bad for)\b/i', $label) === 1) {
            return '';
        }

        return $label;
    }
}
