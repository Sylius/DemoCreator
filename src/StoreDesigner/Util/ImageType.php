<?php

declare(strict_types=1);

namespace App\StoreDesigner\Util;

enum ImageType: string
{
    case PRODUCT = 'product';
    case LOGO    = 'logo';
    case BANNER  = 'banner';
}
