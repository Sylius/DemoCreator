<?php

declare(strict_types=1);

namespace App\StoreDesigner\Filesystem;

use App\StoreDesigner\Dto\ManifestDto;
use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Exception\StoreDefinitionNotFoundException;
use App\StoreDesigner\Util\PathResolver;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

final readonly class StoreDefinitionReader
{
    public function __construct(
        private Filesystem $filesystem,
        private PathResolver $pathResolver,
    ) {
    }

    public function getStoreDefinition(string $storePresetId): array
    {
        $definitionPath = $this->pathResolver->getStoreDefinitionFilePath($storePresetId);
        if (!$this->filesystem->exists($definitionPath)) {
            throw new StoreDefinitionNotFoundException($storePresetId);
        }

        return json_decode(
            $this->filesystem->readFile($definitionPath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
