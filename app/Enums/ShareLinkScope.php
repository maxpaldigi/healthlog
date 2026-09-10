<?php

namespace App\Enums;

enum ShareLinkScope: string
{
    case Read = 'read';
    case Respond = 'respond';
}
