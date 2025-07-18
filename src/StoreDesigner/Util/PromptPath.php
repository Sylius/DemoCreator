<?php

declare(strict_types=1);

namespace App\StoreDesigner\Util;

enum PromptPath: string
{
    case InterviewInstructions = 'resources/prompts/interview-instructions.md';
    case FixturesGenerationInstructions = 'resources/prompts/fixtures-generation-instructions.md';
}
