<?php

declare(strict_types=1);

namespace App\StoreDesigner\Filesystem;

use App\StoreDesigner\Dto\ManifestDto;
use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Util\ImageType;
use App\StoreDesigner\Util\PathResolver;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

final readonly class StoreFilesystemPersister implements ImagePersisterInterface
{
    public function __construct(
        private Filesystem $filesystem,
        private PathResolver $pathResolver,
    ) {
    }

    public function persistImage(string $storePresetId, string $imageName, string $binary, ImageType $type): string
    {
        $dir = match ($type) {
            ImageType::PRODUCT => $this->pathResolver->getStoreImagesPath($storePresetId),
            ImageType::LOGO,
            ImageType::BANNER => $this->pathResolver->getStoreAssetsPath($storePresetId),
        };

        $this->filesystem->mkdir($dir, 0755);
        $path = Path::join($dir, $imageName . '.png');
        $this->filesystem->dumpFile($path, $binary);

        return $path;
    }

    public function persistAll(string $storePresetId, StoreDetailsDto $storeDetailsDto, array $storeDefinition, array $fixtures, string $themeScss): void
    {
        $this->saveStoreDetails($storePresetId, $storeDetailsDto);
        $this->saveStoreDefinition($storePresetId, $storeDefinition);
        $this->saveFixtures($storePresetId, $fixtures);
        $this->saveThemeScss($storePresetId, $themeScss);
    }

    public function saveStoreDefinition(string $id, array $data): void
    {
        $this->dumpJson($this->pathResolver->getStoreDefinitionPath($id), $data);
    }

    public function saveStoreDetails(string $id, StoreDetailsDto $storeDetailsDto): void
    {
        $this->filesystem->dumpFile($this->pathResolver->getStoreDetailsPath($id), $storeDetailsDto->toJson());
    }

    public function saveManifest(string $id, ManifestDto $manifestDto): void
    {
        $this->filesystem->dumpFile($this->pathResolver->getManifestPath($id), $manifestDto->toJson());
    }

    public function saveFixtures(string $storePresetId, array $fixtures): void
    {
        $yaml = Yaml::dump($fixtures, 10, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $this->filesystem->dumpFile($this->pathResolver->getStoreFixturesPath($storePresetId), $yaml);
    }

    public function initStorePresetDirectory(string $storePresetId): void
    {
        $this->filesystem->mkdir(Path::join($this->pathResolver->getStorePresetsDir(), $storePresetId), 0755);
        $this->filesystem->dumpFile($this->pathResolver->getManifestPath($storePresetId), json_encode(['id' => $storePresetId]));

    }

    private function saveThemeScss(string $storePresetId, string $themeScss): void
    {
        $scssPath = $this->pathResolver->getThemeScssPath($storePresetId);
        if (!$this->filesystem->exists(dirname($scssPath))) {
            $this->filesystem->mkdir(dirname($scssPath));
        }
        $this->filesystem->dumpFile($scssPath, $themeScss);
    }

    private function dumpJson(string $path, mixed $data): void
    {
        $this->filesystem->dumpFile($path, json_encode($data, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
    }
}
