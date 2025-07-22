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

namespace App\StoreDesigner\Persister;

use App\StoreDesigner\Filesystem\StoreFilesystemPersister;

final readonly class ProductImagePersister implements ImagePersisterInterface
{
    public function __construct(private StoreFilesystemPersister $storeFilesystemPersister)
    {
    }

    public function persist(string $storePresetId, string $imageBinary): void
    {
        $this->storeFilesystemPersister->persistProductImage($storePresetId, $imageBinary);
    }
}
