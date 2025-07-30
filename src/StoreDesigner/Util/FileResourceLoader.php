<?php

declare(strict_types=1);

namespace App\StoreDesigner\Util;

use App\StoreDesigner\Schema\JsonSchema;
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

    public function loadSchemaArray(SchemaPath $schemaPath): array
    {
        $absPath = $this->projectDir . DIRECTORY_SEPARATOR . ltrim($schemaPath->value, DIRECTORY_SEPARATOR);
        $raw = json_decode(file_get_contents($absPath), true);
        if (!is_array($raw)) {
            throw new \RuntimeException("Invalid JSON in schema: $absPath");
        }

        return $raw;
    }

    public function loadSchemaObject(SchemaPath $schemaPath): JsonSchema
    {
        return new JsonSchema($this->loadSchemaArray($schemaPath));
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
