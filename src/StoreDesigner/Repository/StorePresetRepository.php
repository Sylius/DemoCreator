<?php

declare(strict_types=1);

namespace App\StoreDesigner\Repository;

use App\StoreDesigner\Dto\ManifestDto;
use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Exception\StoreDefinitionNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

final readonly class StorePresetRepository
{
    private const PRESET_FILENAME = 'store-preset.json';
    private const STORE_DETAILS_FILENAME = 'store-details.json';
    private const STORE_DEFINITION_FILENAME = 'store-definition.json';

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/store-presets')]
        private string $storePresetsDir,
        private Filesystem $filesystem,
    ) {
    }

    public function initializeStorePreset(string $storePresetId): void
    {
        $this->filesystem->mkdir(Path::join($this->storePresetsDir, $storePresetId), 0755);
        $this->filesystem->dumpFile($this->getManifestPath($storePresetId), json_encode(['id' => $storePresetId]));
    }

    public function saveStoreDefinition(string $id, array $data): void
    {
        $this->filesystem->dumpFile(
            $this->getStoreDefinitionPath($id),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
    }

    public function saveStoreDetails(string $id, StoreDetailsDto $storeDetailsDto): void
    {
        $this->filesystem->dumpFile($this->getStoreDetailsPath($id), $storeDetailsDto->toJson());
    }

    public function saveManifest(string $id, ManifestDto $manifestDto): void
    {
        $this->filesystem->dumpFile($this->getManifestPath($id), $manifestDto->toJson());
    }

    public function saveFixtures(string $storePresetId, array $fixtures): void
    {
        $yaml = Yaml::dump($fixtures, 10, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $this->filesystem->dumpFile($this->getStoreFixturesPath($storePresetId), $yaml);
    }

    public function getStoreDefinition(string $storePresetId): array
    {
        $definitionPath = $this->getStoreDefinitionPath($storePresetId);
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

    private function getStoreDefinitionPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, self::STORE_DEFINITION_FILENAME);
    }

    private function getStoreDetailsPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, self::STORE_DETAILS_FILENAME);
    }

    private function getManifestPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, self::PRESET_FILENAME);
    }

    private function getStoreFixturesPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, 'fixtures', 'fixtures.yaml');
    }

    public function getStoreImagesPath(string $storePresetId): string
    {
        return Path::join($this->storePresetsDir, $storePresetId, 'fixtures');
    }
}
