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


namespace App\StoreDeployer\Deployer;

use App\StoreDesigner\Exception\StorePresetNotFoundException;
use Symfony\Component\Filesystem\Path;

trait StorePresetCopierTrait
{
    private function copyPresetToProject(string $storePresetId, string $syliusProjectPath): void
    {
        $sourcePath = $this->pathResolver->getStorePresetRootDirectory($storePresetId);
        $destinationPath = Path::join($syliusProjectPath, 'store-preset');

        if (!is_dir($sourcePath)) {
            throw new StorePresetNotFoundException(
                sprintf('Store preset "%s" not found in "%s".', $storePresetId, $sourcePath)
            );
        }

        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $this->filesystem->mirror(
            originDir: $sourcePath,
            targetDir: $destinationPath,
            options: [
                'override' => true,
                'delete' => true,
            ]
        );
    }
}
