<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Yaml\Yaml;

final class StorePresetManager
{
    private const PRESET_FILENAME = 'store-preset.json';

    public function __construct(
        private readonly Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%/var/store-presets')]
        private string $storePresetsDir,
        #[Autowire('%kernel.project_dir%/var')]
        private string $varDir,
    ) {
        // Upewniamy się, że bazowa ścieżka nie ma końcowego slash-a
        $this->storePresetsDir = rtrim($this->storePresetsDir, '/\\');
    }

    public function create(): string
    {
        $timestamp = (new \DateTimeImmutable())->format('Ymd_His');
        $uuid = Uuid::v4()->toRfc4122();
        $id = "{$timestamp}_{$uuid}";
        $preset = $this->getDefaultPresetData($id);

        try {
            $this->savePresetData($id, $preset);
            return $id;
        } catch (IOException | \JsonException $e) {
            throw new \RuntimeException(
                "Nie można utworzyć presetu '{$id}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function updatePreset(string $name, array $data): void
    {
        $this->validatePresetName($name);

        try {
            $preset = $this->loadOrInitializePreset($name);
            $preset = array_merge($preset, $data);
            $preset['updatedAt'] = (new \DateTimeImmutable())->format(DATE_ATOM);

            $this->savePresetData($name, $preset);
        } catch (IOException | \JsonException $e) {
            throw new \RuntimeException(
                "Nie można zaktualizować presetu '{$name}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function updatePlugins(string $name, array $plugins): void
    {
        $this->updatePreset($name, ['plugins' => $plugins]);
    }

    public function updateTheme(string $name, array $themeConfig): void
    {
        $this->updatePreset($name, ['themes' => $themeConfig]);
    }

    public function updateFixtures(string $name, array $fixtures): void
    {
        $this->validatePresetName($name);
        $dir = Path::join($this->getPresetDirectory($name), 'fixtures');
        $filePath = Path::join($dir, 'fixtures.yaml');
        $this->filesystem->mkdir($dir, 0755);
        $yaml = Yaml::dump($fixtures, 10, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $this->filesystem->dumpFile($filePath, $yaml);
    }

    public function updateStoreDefinition(array $data): void
    {
        $storePresetName = $data['storePresetName'] ?? null;
        if ($storePresetName === null) {
            throw new \InvalidArgumentException("Brak klucza 'storePresetName' w danych.");
        }

        $data['updatedAt'] = (new \DateTimeImmutable())->format(DATE_ATOM);

        try {
            $filePath = Path::join($this->getPresetDirectory($storePresetName), 'store-definition.json');
            $this->filesystem->mkdir(dirname($filePath), 0755);
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $this->filesystem->dumpFile($filePath, $json);
        } catch (IOException | \JsonException $e) {
            throw new \RuntimeException(
                "Nie można zaktualizować definicji sklepu dla presetu '{$storePresetName}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function markReady(string $name, bool $ready = true): void
    {
        $this->updatePreset($name, ['readyToUse' => $ready]);
    }

    public function getPreset(string $name): ?array
    {
        $this->validatePresetName($name);
        $filePath = $this->getPresetFilePath($name);

        if (!$this->filesystem->exists($filePath)) {
            return null;
        }

        try {
            $json = $this->filesystem->readFile($filePath);
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (IOException | \JsonException $e) {
            throw new \RuntimeException(
                "Nie można odczytać presetu '{$name}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function presetExists(string $name): bool
    {
        $this->validatePresetName($name);
        return $this->filesystem->exists($this->getPresetFilePath($name));
    }

    public function listPresets(): array
    {
        if (!$this->filesystem->exists($this->storePresetsDir)) {
            return [];
        }

        $pattern = Path::join($this->storePresetsDir, '*', self::PRESET_FILENAME);
        $files = glob($pattern) ?: [];
        $presets = [];

        foreach ($files as $file) {
            try {
                $json = $this->filesystem->readFile($file);
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($data)) {
                    $presets[] = $data;
                }
            } catch (IOException | \JsonException $e) {
                // Pomijamy uszkodzone pliki
            }
        }

        return $presets;
    }

    public function deletePreset(string $name): void
    {
        $this->validatePresetName($name);
        $dir = $this->getPresetDirectory($name);

        if ($this->filesystem->exists($dir)) {
            try {
                $this->filesystem->remove($dir);
            } catch (IOException $e) {
                throw new \RuntimeException(
                    "Nie można usunąć presetu '{$name}': {$e->getMessage()}",
                    0,
                    $e
                );
            }
        }
    }

    private function loadOrInitializePreset(string $name): array
    {
        $filePath = $this->getPresetFilePath($name);
        if (!$this->filesystem->exists($filePath)) {
            return $this->getDefaultPresetData($name);
        }

        $json = $this->filesystem->readFile($filePath);
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function getDefaultPresetData(string $name): array
    {
        $now = (new \DateTimeImmutable())->format(DATE_ATOM);

        return [
            'name'       => $name,
            'plugins'    => [],
            'themes'     => [],
            'fixtures'   => [],
            'readyToUse' => false,
            'createdAt'  => $now,
            'updatedAt'  => $now,
        ];
    }

    private function savePresetData(string $name, array $data): void
    {
        $filePath = $this->getPresetFilePath($name);
        $dir = dirname($filePath);
        $this->filesystem->mkdir($dir, 0755);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->filesystem->dumpFile($filePath, $json);
    }

    private function getPresetDirectory(string $name): string
    {
        return Path::join($this->storePresetsDir, $name);
    }

    private function getPresetFilePath(string $name): string
    {
        return Path::join($this->getPresetDirectory($name), self::PRESET_FILENAME);
    }

    private function validatePresetName(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            throw new \InvalidArgumentException("Nieprawidłowa nazwa presetu: '{$name}'.");
        }
    }

    public function zipStorePreset(string $presetName): string
    {
        $this->validatePresetName($presetName);
        $dir = Path::join($this->storePresetsDir, $presetName);
        if (!is_dir($dir)) {
            throw new \RuntimeException(sprintf('Store preset "%s" does not exist', $presetName));
        }
        $files = new \FilesystemIterator($dir);
        if (!$files->valid()) {
            throw new \RuntimeException('Store preset directory is empty');
        }
        $tmpZip = tempnam(sys_get_temp_dir(), 'preset_zip_');
        $zip = new \ZipArchive();
        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create zip file');
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($dir) + 1);
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        return $tmpZip;
    }

    public function saveRawAssistantResponse(array $storeDefinition): void
    {
        $dir = $this->varDir . '/raw-store-definitions';
        $this->filesystem->mkdir($dir, 0755);
        $timestamp = (new \DateTimeImmutable())->format('Ymd_His');
        $filePath = Path::join($dir, 'store_definition_' . $timestamp . '.json');
        try {
            $json = json_encode($storeDefinition, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $this->filesystem->dumpFile($filePath, $json);
        } catch (IOException | \JsonException $e) {
            throw new \RuntimeException(
                "Nie można zapisać surowej odpowiedzi asystenta: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function initializeStorePreset(string $storePresetName): void
    {
        $this->validatePresetName($storePresetName);
        $presetDir = $this->getPresetDirectory($storePresetName);
        if (!$this->filesystem->exists($presetDir)) {
            $this->filesystem->mkdir($presetDir, 0755);
        }
        $presetFilePath = $this->getPresetFilePath($storePresetName);
        if (!$this->filesystem->exists($presetFilePath)) {
            $this->create();
        }
    }
}
