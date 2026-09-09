<?php

namespace App\Exceptions;

use RuntimeException;

class CouldNotReadPhotoException extends RuntimeException
{
    public static function make(?string $message = null): self
    {
        return new self($message ?? "Couldn't read that photo, please enter the numbers manually");
    }
}
