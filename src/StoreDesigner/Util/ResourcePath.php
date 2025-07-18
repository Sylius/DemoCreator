<?php

declare(strict_types=1);

namespace App\StoreDesigner\Util;

enum ResourcePath: string
{
    case FixturesSchema = 'resources/schemas/fixtures.schema.json';

    case StorePresetManifestSchema = 'resources/schemas/store-preset-manifest.schema.json';

    case FixturesGenerationInstructions = 'resources/prompts/fixtures-generation-instructions.md';

    case InterviewInstructions = 'resources/prompts/interview-instructions.md';
}
