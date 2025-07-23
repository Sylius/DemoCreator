<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\StoreDesigner\Filesystem;

use App\StoreDesigner\Util\ImageType;

interface ImagePersisterInterface
{
    public function persistImage(string $storePresetId, string $imageName, string $binary, ImageType $type): string;
}
