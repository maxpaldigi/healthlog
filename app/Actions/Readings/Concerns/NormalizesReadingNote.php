<?php

namespace App\Actions\Readings\Concerns;

trait NormalizesReadingNote
{
    private static function note(mixed $note): ?string
    {
        if (! is_string($note)) {
            return null;
        }

        $note = trim($note);

        return $note === '' ? null : $note;
    }
}
