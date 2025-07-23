<?php

declare(strict_types=1);

namespace App\StoreDesigner\Generator;

use App\StoreDesigner\Exception\InvalidStoreDefinitionException;
use App\StoreDesigner\Util\StoreSection;

final readonly class ThemeScssGenerator implements ThemeScssGeneratorInterface
{
    public function generate(array $storeDefinition): array
    {
        $themes = $storeDefinition['themes'] ?? null;

        if ($themes === null) {
            throw new InvalidStoreDefinitionException('Themes section is missing in the store definition.');
        }

        $result = [];

        foreach (StoreSection::cases() as $section) {
            $rootLines = [];
            $btnLines  = [];

            $cssVariables = $themes[$section->value]['cssVariables'] ?? null;
            if ($cssVariables === null) {
                throw new InvalidStoreDefinitionException(sprintf(
                    'CSS variables for section "%s" are missing in the store definition.',
                    $section->value
                ));
            }

            foreach ($cssVariables as $prop => $value) {
                $rootLines[] = sprintf('    %s: %s;', $prop, $value);
                $btnLines[] = sprintf('    %s: %s;', $prop, $value);
            }

            $content  = "// This file is auto-generated. Do not edit directly.\n\n";
            $content .= ":root {\n" . implode("\n", $rootLines) . "\n}\n\n";
            $content .= ".btn-primary {\n" . implode("\n", $btnLines) . "\n}\n";

            $result[$section->value] = $content;
        }

        return $result;
    }
}
