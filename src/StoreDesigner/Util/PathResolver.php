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

namespace App\StoreDesigner\Util;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;

final readonly class PathResolver
{
    private const PRESET_FILENAME = 'store-preset.json';
    private const STORE_DETAILS_FILENAME = 'store-details.json';
    private const STORE_DEFINITION_FILENAME = 'store-definition.json';

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/store-presets')]
        private string $storePresetsDir,
    ) {
    }

    public function getStorePresetsDirectory(): string
    {
        return $this->storePresetsDir;
    }

    public function getFixturesDirectory(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, 'fixtures');
    }

    public function getAssetsStylesDirectory(string $storePresetId, StoreSection $storeSection): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, 'assets', $storeSection->value, 'styles');
    }

    public function getStorePresetRootDirectory(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId);
    }

    public function getAssetsStylesFilePath(string $storePresetId, StoreSection $storeSection, string $fileName = 'theme'): string
    {
        return Path::join($this->getAssetsStylesDirectory($storePresetId, $storeSection), $fileName . '.scss');
    }

    public function getAssetsImageFilePath(string $storePresetId, StoreSection $storeSection, string $imageName): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, 'assets', $storeSection->value, 'images', $imageName . '.png');
    }

    public function getStoreDefinitionFilePath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, self::STORE_DEFINITION_FILENAME);
    }

    public function getStoreDetailsFilePath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, self::STORE_DETAILS_FILENAME);
    }

    public function getManifestFilePath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, self::PRESET_FILENAME);
    }

    public function getFixturesFilePath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, 'fixtures', 'fixtures.yaml');
    }

    public function getFixturesImageFilePath(string $storePresetId, string $imageName): string
    {
        return Path::join($this->getFixturesDirectory($storePresetId), $imageName . '.png');
    }
}
