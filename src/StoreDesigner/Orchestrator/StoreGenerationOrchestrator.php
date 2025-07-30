<?php

declare(strict_types=1);

namespace App\StoreDesigner\Orchestrator;

use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Factory\ImageRequestCollectionFactoryInterface;
use App\StoreDesigner\Filesystem\ImagePersisterInterface;
use App\StoreDesigner\Filesystem\StoreFilesystemPersister;
use App\StoreDesigner\Generator\ImageGeneratorInterface;
use App\StoreDesigner\Generator\StoreDefinitionGeneratorInterface;
use App\StoreDesigner\Generator\ThemeScssGeneratorInterface;
use App\StoreDesigner\Parser\FixtureParser;

final readonly class StoreGenerationOrchestrator implements StoreGenerationOrchestratorInterface
{
    public function __construct(
        private StoreDefinitionGeneratorInterface $storeDefinitionGenerator,
        private StoreFilesystemPersister $storeFilesystemPersister,
        private FixtureParser $fixtureParser,
        private ThemeScssGeneratorInterface $themeScssGenerator,
        private ImageRequestCollectionFactoryInterface $imageRequestCollectionFactory,
        private ImageGeneratorInterface $imageGenerator,
        private ImagePersisterInterface $imagePersister,
    ) {
    }

    public function orchestrate(string $storePresetId, StoreDetailsDto $storeDetailsDto): void
    {
        $storeDefinition = $this->storeDefinitionGenerator->generate($storeDetailsDto);
        $fixtures = $this->fixtureParser->parse($storeDefinition);
        $themeScss = $this->themeScssGenerator->generate($storeDefinition);

        $this->storeFilesystemPersister->persistAll(
            $storePresetId, $storeDetailsDto, $storeDefinition, $fixtures, $themeScss,
        );

        $imageRequests = $this->imageRequestCollectionFactory->createFromStoreDefinition($storeDefinition);
        $results = $this->imageGenerator->generateAll($imageRequests);
        $this->imagePersister->persistAll($storePresetId, $results);
    }
}
