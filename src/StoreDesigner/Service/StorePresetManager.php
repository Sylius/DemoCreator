<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Client\GptClient;
use App\StoreDesigner\Dto\ManifestDto;
use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Filesystem\StoreFilesystemPersister;

final readonly class StorePresetManager
{
    public function __construct(
        private StoreFilesystemPersister $storePresetRepository,
        private GptClient $gptClient,
    )
    {
    }

    public function saveStoreDefinition(string $storePresetId, array $data): void
    {
        $this->storePresetRepository->saveStoreDefinition(
            $storePresetId,
            $data
        );
    }

    public function saveStoreDetails(string $id, StoreDetailsDto $storeDetailsDto): void
    {
        $this->storePresetRepository->saveStoreDetails($id, $storeDetailsDto);
    }

    public function updatePlugins(string $id, array $plugins): void
    {
        $this->storePresetRepository->saveManifest($id, new ManifestDto(plugins: $plugins));
    }

    public function saveFixtures(string $id, array $fixtures): void
    {
        $this->storePresetRepository->saveFixtures($id, $fixtures);
    }
}
