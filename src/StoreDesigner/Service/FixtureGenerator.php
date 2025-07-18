<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Util\FileResourceLoader;
use App\StoreDesigner\Util\PromptPath;
use App\StoreDesigner\Util\SchemaPath;

final readonly class FixtureGenerator
{
    public function __construct(
        private GptClient $gptClient,
        private FileResourceLoader $fileResourceLoader,
    ) {
    }

    public function generate(StoreDetailsDto $storeDetailsDto): array
    {
        $message = $this->gptClient->chatCompletions(
            messages: [
                [
                    'role' => 'system',
                    'content' => $this->fileResourceLoader->loadPrompt(PromptPath::FixturesGenerationInstructions),
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($storeDetailsDto->jsonSerialize()),
                ],
            ],
//                model: 'gpt-4.1',
            model: 'gpt-4.1-mini',
            maxCompletionTokens: 8192,
            functions: [
                [
                    'name' => 'generateFixtures',
                    'description' => 'It generates the final json fixtures based on the collectStoreInfo retrieved data',
                    'parameters' => $this->fileResourceLoader->loadSchema(SchemaPath::FixturesSchema),
                ],
            ],
        );

        if (!$message->hasFunctionCall()) {
            throw new \RuntimeException('No function call in the response from OpenAI');
        }

        $functionName = $message->getFunctionCallName();
        if ($functionName !== 'generateFixtures') {
            throw new \RuntimeException('Unsupported function call: ' . $functionName);
        }

        $functionData = $message->getFunctionCallData();
        if ($functionData === null) {
            throw new \RuntimeException('Failed to parse function call data from OpenAI response');
        }

        return $functionData;
    }
}
