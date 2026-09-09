<?php

namespace App\Enums;

enum Arm: string
{
    case Left = 'left';
    case Right = 'right';
    case Unknown = 'unknown';
}
