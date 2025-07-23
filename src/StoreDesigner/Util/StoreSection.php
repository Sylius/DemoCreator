<?php

declare(strict_types=1);

namespace App\StoreDesigner\Util;

enum StoreSection: string
{
    case Shop = 'shop';
    case Admin = 'admin';
}
