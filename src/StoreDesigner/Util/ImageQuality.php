<?php

declare(strict_types=1);

namespace App\StoreDesigner\Util;

enum ImageQuality: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
