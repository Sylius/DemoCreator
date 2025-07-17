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
        private GptClient $gptClient,
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

    public function updatePreset(string $id, array $data): void
    {
        $this->validatePresetId($id);

        try {
            $preset = $this->loadOrInitializePreset($id);
            $preset = array_merge($preset, $data);
            $preset['updatedAt'] = (new \DateTimeImmutable())->format(DATE_ATOM);
            $preset['id'] = $id;
            $preset['name'] = $data['name'] ?? $preset['name'] ?? '';
            $this->savePresetData($id, $preset);
        } catch (IOException | \JsonException $e) {
            throw new \RuntimeException(
                "Nie można zaktualizować presetu '{$id}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function updatePlugins(string $id, array $plugins): void
    {
        $this->updatePreset($id, ['plugins' => $plugins]);
    }

    public function updateTheme(string $id, array $themeConfig): void
    {
        $this->updatePreset($id, ['themes' => $themeConfig]);
    }

    public function updateFixtures(string $id, array $fixtures): void
    {
        $this->validatePresetId($id);
        $dir = Path::join($this->getPresetDirectory($id), 'fixtures');
        $filePath = Path::join($dir, 'fixtures.yaml');
        $this->filesystem->mkdir($dir, 0755);
        $yaml = Yaml::dump($fixtures, 10, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $this->filesystem->dumpFile($filePath, $yaml);
        $this->updatePreset($id, ['name' => array_keys($fixtures['sylius_fixtures']['suites'])[0] ?? '']);
    }

    public function updateStoreDefinition(array $data): void
    {
        if (empty($data['id'] ?? null) || !is_string($data['id'])) {
            throw new \InvalidArgumentException("Identyfikator presetu jest wymagany.");
        }

        $data['updatedAt'] = (new \DateTimeImmutable())->format(DATE_ATOM);

        try {
            $filePath = Path::join($this->getPresetDirectory($data['id']), 'store-definition.json');
            $this->filesystem->mkdir(dirname($filePath), 0755);
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $this->filesystem->dumpFile($filePath, $json);
        } catch (IOException | \JsonException $e) {
            throw new \RuntimeException(
                "Nie można zaktualizować definicji sklepu dla presetu '{$data['id']}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function markReady(string $id, bool $ready = true): void
    {
        $this->updatePreset($id, ['readyToUse' => $ready]);
    }

    public function getPreset(string $id): ?array
    {
        $this->validatePresetId($id);
        $filePath = $this->getPresetFilePath($id);

        if (!$this->filesystem->exists($filePath)) {
            return null;
        }

        try {
            $json = $this->filesystem->readFile($filePath);
            $preset = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $preset['id'] = $id;
            if (!isset($preset['name'])) {
                $preset['name'] = $id;
            }
            return $preset;
        } catch (IOException | \JsonException $e) {
            throw new \RuntimeException(
                "Nie można odczytać presetu '{$id}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function deletePreset(string $id): void
    {
        $this->validatePresetId($id);
        $dir = $this->getPresetDirectory($id);

        if ($this->filesystem->exists($dir)) {
            try {
                $this->filesystem->remove($dir);
            } catch (IOException $e) {
                throw new \RuntimeException(
                    "Nie można usunąć presetu '{$id}': {$e->getMessage()}",
                    0,
                    $e
                );
            }
        }
    }

    private function loadOrInitializePreset(string $id): array
    {
        $filePath = $this->getPresetFilePath($id);
        if (!$this->filesystem->exists($filePath)) {
            return $this->getDefaultPresetData($id);
        }

        $json = $this->filesystem->readFile($filePath);
        $preset = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $preset['id'] = $id;
        if (!isset($preset['name'])) {
            $preset['name'] = $id;
        }
        return $preset;
    }

    private function getDefaultPresetData(string $id): array
    {
        $now = (new \DateTimeImmutable())->format(DATE_ATOM);
        return [
            'id'         => $id,
            'name'       => '',
            'plugins'    => [],
            'themes'     => [],
            'fixtures'   => [],
            'readyToUse' => false,
            'createdAt'  => $now,
            'updatedAt'  => $now,
        ];
    }

    private function savePresetData(string $id, array $data): void
    {
        $filePath = $this->getPresetFilePath($id);
        $dir = dirname($filePath);
        $this->filesystem->mkdir($dir, 0755);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->filesystem->dumpFile($filePath, $json);
    }

    private function getPresetDirectory(string $id): string
    {
        return Path::join($this->storePresetsDir, $id);
    }

    private function getPresetFilePath(string $id): string
    {
        return Path::join($this->getPresetDirectory($id), self::PRESET_FILENAME);
    }

    private function validatePresetId(string $id): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+(_[a-f0-9-]+)?$/', $id)) {
            throw new \InvalidArgumentException("Nieprawidłowy identyfikator presetu: '{$id}'.");
        }
    }

    public function zipStorePreset(string $presetName): string
    {
        $this->validatePresetId($presetName);
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

    public function generateProductImages(string $id): array
    {
        $this->validatePresetId($id);
        $definitionPath = Path::join($this->getPresetDirectory($id), 'store-definition.json');
        if (!$this->filesystem->exists($definitionPath)) {
            throw new \RuntimeException("Brak store-definition.json dla presetu {$id}");
        }
        $definition = json_decode($this->filesystem->readFile($definitionPath), true, 512, JSON_THROW_ON_ERROR);
        $products = $definition['products'] ?? [];
        $imagesDir = Path::join($this->getPresetDirectory($id), 'fixtures');
        $this->filesystem->mkdir($imagesDir, 0755);
        $results = ['images' => [], 'errors' => []];
        foreach ($products as $product) {
            $imgPrompt = $product['img_prompt'] ?? null;
            $images = $product['images'] ?? [];
            if (!$imgPrompt || empty($images)) continue;
            foreach ($images as $imgName) {
                $fileName = $imgName . '.png';
                $filePath = Path::join($imagesDir, $fileName);
                try {
                    $imageData = $this->gptClient->generateImage($imgPrompt);
                    if (file_put_contents($filePath, $imageData) === false) {
                        throw new \RuntimeException('Failed to save image to ' . $filePath);
                    }
                    $results['images'][] = $filePath;
                } catch (\Throwable $e) {
                    $results['errors'][] = [
                        'product' => $product['name'] ?? '',
                        'img' => $imgName,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
        return $results;
    }

    public function generateBannerImage(string $id, string $prompt): ?string
    {
        $this->validatePresetId($id);
        $definitionPath = Path::join($this->getPresetDirectory($id), 'store-definition.json');
        if (!$this->filesystem->exists($definitionPath)) {
            throw new \RuntimeException("Brak store-definition.json dla presetu {$id}");
        }
        try {
            $imageData = $this->gptClient->generateImage($prompt);
            $fileName = 'banner_' . Uuid::v4()->toRfc4122() . '.png';
            $filePath = Path::join($this->getPresetDirectory($id), 'fixtures', $fileName);
            if (file_put_contents($filePath, $imageData) === false) {
                throw new \RuntimeException('Failed to save banner image to ' . $filePath);
            }
            return $filePath;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Nie można wygenerować obrazka banera: {$e->getMessage()}", 0, $e);
        }
    }
}
