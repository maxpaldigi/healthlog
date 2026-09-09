<?php

namespace App\Exceptions;

use RuntimeException;

class CouldNotReadPhotoException extends RuntimeException
{
    public static function make(): self
    {
        return new self("Couldn't read that photo, please enter the numbers manually");
    }
}
