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

    public function getStorePresetsDir(): string
    {
        return $this->storePresetsDir;
    }

    public function getStoreDefinitionPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, self::STORE_DEFINITION_FILENAME);
    }

    public function getStoreDetailsPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, self::STORE_DETAILS_FILENAME);
    }

    public function getManifestPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, self::PRESET_FILENAME);
    }

    public function getStoreFixturesPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, 'fixtures', 'fixtures.yaml');
    }

    public function getStoreImagesPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, 'fixtures');
    }

    public function getThemeScssPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, 'theme', 'theme.scss');
    }

    public function getStoreAssetsPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, 'assets');
    }
}
