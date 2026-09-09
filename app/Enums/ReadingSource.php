<?php

namespace App\Enums;

enum ReadingSource: string
{
    case Manual = 'manual';
    case Photo = 'photo';
    case Voice = 'voice';
    case Import = 'import';
}
