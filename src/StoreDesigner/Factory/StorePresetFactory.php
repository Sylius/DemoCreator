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

namespace App\StoreDesigner\Factory;

use App\StoreDesigner\Dto\ManifestDto;
use App\StoreDesigner\Filesystem\StoreFilesystemPersisterProduct;
use App\StoreDesigner\Service\StorePresetIdGenerator;

final readonly class StorePresetFactory
{
    public function __construct(
        private StorePresetIdGenerator $storePresetIdGenerator,
        private StoreFilesystemPersisterProduct $storeFilesystemPersister,
    ) {
    }

    public function create(): string
    {
        $storePresetId = $this->storePresetIdGenerator->generate();
        $this->storeFilesystemPersister->initStorePresetDirectory($storePresetId);
        $this->storeFilesystemPersister->saveManifest($storePresetId, new ManifestDto(plugins: []));

        return $storePresetId;
    }
}
