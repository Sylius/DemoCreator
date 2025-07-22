<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Exception\InvalidStoreDefinitionException;

final readonly class ThemeScssGenerator
{
    /** @param array{cssVariables: array<string,string>} $theme */
    public function generate(array $theme): string
    {
        $cssVariables = $theme['cssVariables'] ?? [];

        if (empty($cssVariables)) {
            throw new InvalidStoreDefinitionException(
                'The theme must contain a "cssVariables" array with at least one variable.'
            );
        }

        $rootLines = [];
        $btnLines  = [];

        foreach ($cssVariables as $prop => $value) {
            $rootLines[] = sprintf('    %s: %s;', $prop, $value);

            if (str_starts_with($prop, '--bs-btn-')) {
                $btnLines[] = sprintf('    %s: %s;', $prop, $value);
            }
        }

        $content  = "// This file is auto-generated. Do not edit directly.\n\n";
        $content .= ":root {\n" . implode("\n", $rootLines) . "\n}\n\n";

        if (!empty($btnLines)) {
            $content .= ".btn-primary {\n" . implode("\n", $btnLines) . "\n}\n";
        }

        return $content;
    }
}
