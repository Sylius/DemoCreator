<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class StoreFixtureProcessor
{
    public function __construct(
        private readonly FixtureParser $fixtureParser,
        private readonly string $fixturesDir
    ) {}

    public function processJsonAndSave(array $jsonData, string $industry): string
    {
        $path = $this->getPath($industry, 'json');
        $filesystem = new Filesystem();
        $filesystem->dumpFile($path, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $path;
    }

    public function processJsonAndConvertToYaml(array $jsonData, string $industry): string
    {
        $yamlName = $industry . '_' . date('Ymd_His') . '.yaml';
        $yamlPath = $this->fixturesDir . '/' . $yamlName;

        $this->fixtureParser->generateFixturesFromArray($jsonData, $yamlPath);
        return $yamlPath;
    }

    private function getPath(string $industry, string $ext): string
    {
        return $this->fixturesDir . '/' . $industry . '_' . date('Ymd_His') . '.' . $ext;
    }
}
