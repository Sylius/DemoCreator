<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

final class StorePresetManager
{
    private Filesystem $filesystem;

    public function __construct(
        #[Autowire('%kernel.project_dir%/store-presets/')]
        private readonly string $storePresetsDir,
    ) {
        $this->filesystem = new Filesystem();
    }

    /**
     * Tworzy nowy pusty preset
     */
    public function createEmptyPreset(string $name): void
    {
        $this->validatePresetName($name);
        
        $presetDir = $this->getPresetDirectory($name);
        $presetFile = $this->getPresetFilePath($name);
        
        try {
            // Tworzy katalog presetu
            $this->filesystem->mkdir($presetDir, 0755);
            
            // Tworzy domyślną strukturę presetu
            $preset = [
                'name' => $name,
                'plugins' => [],
                'themes' => [],
                'fixtures' => [],
                'readyToUse' => false,
                'createdAt' => date('c'),
                'updatedAt' => date('c'),
            ];
            
            $this->filesystem->dumpFile($presetFile, json_encode($preset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (IOException $e) {
            throw new \RuntimeException("Nie można utworzyć presetu '{$name}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Aktualizuje preset - tworzy go jeśli nie istnieje
     */
    public function updatePreset(string $name, array $data): void
    {
        $this->validatePresetName($name);
        
        $presetDir = $this->getPresetDirectory($name);
        $presetFile = $this->getPresetFilePath($name);
        
        try {
            $this->filesystem->mkdir($presetDir, 0755);
            
            // Pobiera istniejący preset lub tworzy nowy
            $preset = $this->getPreset($name) ?? [
                'name' => $name,
                'plugins' => [],
                'themes' => [],
                'fixtures' => [],
                'readyToUse' => false,
                'createdAt' => date('c'),
            ];
            
            // Aktualizuje dane
            $preset = array_merge($preset, $data);
            $preset['updatedAt'] = date('c');
            
            // Zapisuje zaktualizowany preset
            $this->filesystem->dumpFile($presetFile, json_encode($preset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (IOException $e) {
            throw new \RuntimeException("Nie można zaktualizować presetu '{$name}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Aktualizuje tylko pluginy w presetcie
     */
    public function updatePlugins(string $name, array $plugins): void
    {
        $this->updatePreset($name, ['plugins' => $plugins]);
    }

    /**
     * Aktualizuje tylko konfigurację motywu w presetcie
     */
    public function updateTheme(string $name, array $themeConfig): void
    {
        $this->updatePreset($name, ['themes' => $themeConfig]);
    }

    /**
     * Aktualizuje tylko fixtures w presetcie
     */
    public function updateFixtures(string $name, array $fixtures): void
    {
        $this->updatePreset($name, ['fixtures' => $fixtures]);
    }

    public function updateStoreDefinition(array $data): void
    {
        try {
            $definitionFile = $this->getPresetDirectory($data['suiteName']) . '/store-definition.json';
            $data['updatedAt'] = date('c');

            $this->filesystem->mkdir(dirname($definitionFile), 0755);
            $this->filesystem->dumpFile($definitionFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (IOException $e) {
            throw new \RuntimeException("Nie można zaktualizować definicji sklepu: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Oznacza preset jako gotowy do użycia
     */
    public function markReady(string $name, bool $ready = true): void
    {
        $this->updatePreset($name, ['readyToUse' => $ready]);
    }

    /**
     * Pobiera preset po nazwie
     */
    public function getPreset(string $name): ?array
    {
        $this->validatePresetName($name);
        
        $presetFile = $this->getPresetFilePath($name);
        
        if (!$this->filesystem->exists($presetFile)) {
            return null;
        }
        
        try {
            $content = $this->filesystem->readFile($presetFile);
            $preset = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("Nieprawidłowy format JSON w presetcie '{$name}': " . json_last_error_msg());
            }
            
            return $preset;
        } catch (IOException $e) {
            throw new \RuntimeException("Nie można odczytać presetu '{$name}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Sprawdza czy preset istnieje
     */
    public function presetExists(string $name): bool
    {
        return $this->filesystem->exists($this->getPresetFilePath($name));
    }

    /**
     * Listuje wszystkie dostępne presety
     */
    public function listPresets(): array
    {
        $presets = [];
        
        if (!$this->filesystem->exists($this->storePresetsDir)) {
            return $presets;
        }
        
        try {
            $presetFiles = glob($this->storePresetsDir . '/*/store-preset.json');
            
            foreach ($presetFiles as $file) {
                try {
                    $content = $this->filesystem->readFile($file);
                    $preset = json_decode($content, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && is_array($preset)) {
                        $presets[] = $preset;
                    }
                } catch (IOException $e) {
                    // Pomija nieczytelne pliki
                    continue;
                }
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("Nie można odczytać listy presetów: " . $e->getMessage(), 0, $e);
        }
        
        return $presets;
    }

    /**
     * Usuwa preset
     */
    public function deletePreset(string $name): void
    {
        $this->validatePresetName($name);
        
        $presetDir = $this->getPresetDirectory($name);
        
        if ($this->filesystem->exists($presetDir)) {
            try {
                $this->filesystem->remove($presetDir);
            } catch (IOException $e) {
                throw new \RuntimeException("Nie można usunąć presetu '{$name}': " . $e->getMessage(), 0, $e);
            }
        }
    }

    /**
     * Waliduje nazwę presetu
     */
    private function validatePresetName(string $name): void
    {
        if (empty($name) || !preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            throw new \InvalidArgumentException("Nieprawidłowa nazwa presetu. Dozwolone są tylko litery, cyfry, podkreślenia i myślniki.");
        }
    }

    /**
     * Zwraca ścieżkę do katalogu presetu
     */
    private function getPresetDirectory(string $name): string
    {
        return $this->storePresetsDir . '/' . $name;
    }

    /**
     * Zwraca ścieżkę do pliku presetu
     */
    private function getPresetFilePath(string $name): string
    {
        return $this->getPresetDirectory($name) . '/store-preset.json';
    }
} 