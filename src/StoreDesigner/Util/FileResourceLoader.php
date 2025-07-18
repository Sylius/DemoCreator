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

    public function loadPrompt(PromptPath $prompt): string
    {
        return $this->load($prompt->value);
    }

    public function loadSchema(SchemaPath $schema): array
    {
        $data = $this->load($schema->value);

        $json = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in schema file: {$schema->value} - " . json_last_error_msg());
        }
        return $json;
    }

    private function load(string $path): string
    {
        $fullPath = $this->projectDir . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        if (!file_exists($fullPath)) {
            throw new \RuntimeException("File not found: $fullPath");
        }
        $data = file_get_contents($fullPath);
        if ($data === false) {
            throw new \RuntimeException("Failed to read file: $fullPath");
        }

        return $data;
    }
}
