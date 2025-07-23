<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Filesystem\StoreFilesystemPersisterProduct;
use App\StoreDesigner\Generator\StoreDefinitionGeneratorInterface;
use App\StoreDesigner\Generator\ThemeScssGeneratorInterface;

final readonly class StoreGenerationOrchestrator
{
    public function __construct(
        private StoreDefinitionGeneratorInterface $storeDefinitionGenerator,
        private StoreFilesystemPersisterProduct $storeFilesystemPersister,
        private FixtureParser $fixtureParser,
        private ThemeScssGeneratorInterface $themeScssGenerator,
    ) {
    }

    public function orchestrate(string $storePresetId, StoreDetailsDto $storeDetailsDto): void
    {
        $storeDefinition = $this->storeDefinitionGenerator->generate($storeDetailsDto);
        $fixtures = $this->fixtureParser->parse($storeDefinition);
        $themeScss = $this->themeScssGenerator->generate($storeDefinition);

        $this->storeFilesystemPersister->persistAll(
            $storePresetId,
            $storeDetailsDto,
            $storeDefinition,
            $fixtures,
            $themeScss,
        );
    }
}
