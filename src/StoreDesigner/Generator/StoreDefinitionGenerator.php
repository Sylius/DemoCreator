<?php

declare(strict_types=1);

namespace App\StoreDesigner\Generator;

use App\StoreDesigner\Client\GptClient;
use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Util\FileResourceLoader;
use App\StoreDesigner\Util\PromptPath;
use App\StoreDesigner\Util\SchemaPath;

final readonly class StoreDefinitionGenerator implements StoreDefinitionGeneratorInterface
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
                    'content' => $this->fileResourceLoader->loadPrompt(PromptPath::StoreDefinitionGenerationInstructions),
                ],
                [
                    'role' => 'user',
                    'content' => $storeDetailsDto->toJson(),
                ],
            ],
            model: 'gpt-4.1-mini',
            maxCompletionTokens: 16384,
            functions: [
                [
                    'name' => 'generateFixtures',
                    'description' => 'It generates the final json fixtures based on the storeDetails data',
                    'parameters' => $this->fileResourceLoader->loadSchemaArray(SchemaPath::StoreDefinition),
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
