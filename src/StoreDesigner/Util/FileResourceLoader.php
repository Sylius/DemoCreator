<?php

declare(strict_types=1);

namespace App\StoreDesigner\Util;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class FileResourceLoader
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {
    }

    public function load(string $relativePath): string
    {
        $path = $this->projectDir . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: $path");
        }

        $data = file_get_contents($path);
        if ($data === false) {
            throw new \RuntimeException("Failed to read file: $path");
        }

        return $data;
    }

    public function loadJson(string $relativePath): array
    {
        $data = $this->load($relativePath);
        $json = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in $relativePath: " . json_last_error_msg());
        }

        return $json;
    }
}
