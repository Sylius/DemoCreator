<?php

declare(strict_types=1);

namespace App\StoreDesigner\Util;

enum ImageType: string
{
    case Asset = 'asset';
    case Product = 'product';
}
