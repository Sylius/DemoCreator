<?php

declare(strict_types=1);

namespace App\StoreDesigner\Filesystem;

use App\StoreDesigner\Dto\ManifestDto;
use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Util\PathResolver;
use App\StoreDesigner\Util\StoreSection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final readonly class StoreFilesystemPersisterProduct
{
    public function __construct(
        private Filesystem $filesystem,
        private PathResolver $pathResolver,
    ) {
    }

    public function persistAll(
        string $storePresetId,
        StoreDetailsDto $storeDetailsDto,
        array $storeDefinition,
        array $fixtures,
        array $themeScss,
    ): void {
        $this->saveStoreDetails($storePresetId, $storeDetailsDto);
        $this->saveStoreDefinition($storePresetId, $storeDefinition);
        $this->saveFixtures($storePresetId, $fixtures);
        $this->saveThemeScss($storePresetId, $themeScss);
    }

    public function saveStoreDefinition(string $id, array $data): void
    {
        $this->dumpJson($this->pathResolver->getStoreDefinitionFilePath($id), $data);
    }

    public function saveStoreDetails(string $id, StoreDetailsDto $storeDetailsDto): void
    {
        $this->dumpJson($this->pathResolver->getStoreDetailsFilePath($id), $storeDetailsDto->toArray());
    }

    public function saveManifest(string $id, ManifestDto $manifestDto): void
    {
        $this->filesystem->dumpFile($this->pathResolver->getManifestFilePath($id), $manifestDto->toJson());
    }

    public function saveFixtures(string $storePresetId, array $fixtures): void
    {
        $yaml = Yaml::dump($fixtures, 10, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $this->filesystem->dumpFile($this->pathResolver->getFixturesFilePath($storePresetId), $yaml);
    }

    public function initStorePresetDirectory(string $storePresetId): void
    {
        $this->filesystem->mkdir($this->pathResolver->getStorePresetRootDirectory($storePresetId));
    }

    private function saveThemeScss(string $storePresetId, array $themeScss): void
    {
        foreach (StoreSection::cases() as $storeSection) {
            $scssContent = $themeScss[$storeSection->value];
            $scssFilePath = $this->pathResolver->getAssetsStylesFilePath($storePresetId, $storeSection);
            $this->filesystem->dumpFile($scssFilePath, $scssContent);
        }
    }

    private function dumpJson(string $path, mixed $data): void
    {
        $this->filesystem->dumpFile($path, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
