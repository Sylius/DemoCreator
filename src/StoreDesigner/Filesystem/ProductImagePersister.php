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

use App\StoreDesigner\Util\PathResolver;
use Symfony\Component\Filesystem\Filesystem;

final readonly class ProductImagePersister implements ProductImagePersisterInterface
{
    public function __construct(
        private Filesystem $filesystem,
        private PathResolver $pathResolver,
    ) {
    }

    public function persist(string $storePresetId, string $imageName, string $binary): void
    {
        $this->filesystem->dumpFile(
            $this->pathResolver->getFixturesImageFilePath($storePresetId, $imageName),
            $binary,
        );
    }
}
