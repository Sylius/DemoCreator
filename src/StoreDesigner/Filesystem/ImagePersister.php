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
use App\StoreDesigner\Util\PathResolver;
use App\StoreDesigner\Util\StoreSection;
use Symfony\Component\Filesystem\Filesystem;

final readonly class ImagePersister implements ImagePersisterInterface
{
    public function __construct(
        private Filesystem $filesystem,
        private PathResolver $pathResolver,
    ) {
    }

    public function persistAll(string $storePresetId, array $imageResponses): void
    {
        foreach ($imageResponses as $image) {
            $path = match ($image->imageType) {
                ImageType::Product => $this->pathResolver->getFixturesImageFilePath(
                    $storePresetId,
                    $image->filename,
                ),
                ImageType::Asset => $this->pathResolver->getAssetsImageFilePath(
                    $storePresetId,
                    StoreSection::Shop,
                    $image->filename,
                ),
            };

            $this->filesystem->dumpFile($path, $image->binary);
        }
    }
}
