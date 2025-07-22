<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Filesystem\StoreFilesystemPersister;

final readonly class StoreGenerationOrchestrator
{
    public function __construct(
        private StoreDefinitionGenerator $storeDefinitionGenerator,
        private StoreFilesystemPersister $storeFilesystemPersister,
        private FixtureParser $fixtureParser,
        private ThemeScssGenerator $themeScssGenerator,
    ) {
    }

    public function orchestrate(string $storePresetId, StoreDetailsDto $storeDetailsDto): void
    {
        $storeDefinition = $this->storeDefinitionGenerator->generate($storeDetailsDto);
        $fixtures = $this->fixtureParser->parse($storeDefinition);
        $themeScss = $this->themeScssGenerator->generate($storeDefinition['theme'] ?? []);

        $this->storeFilesystemPersister->persistAll(
            $storePresetId,
            $storeDetailsDto,
            $storeDefinition,
            $fixtures,
            $themeScss,
        );
    }
}
