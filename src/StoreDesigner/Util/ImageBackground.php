<?php

declare(strict_types=1);

namespace App\StoreDesigner\Util;

enum ImageBackground: string
{
    case Opaque = 'opaque';
    case Transparent = 'transparent';
}
