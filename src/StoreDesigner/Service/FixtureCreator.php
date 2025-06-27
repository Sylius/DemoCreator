<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Dto\StoreDetailsDto;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class FixtureCreator
{
    public function __construct(
        private GptClient $gptClient,
        private FixtureParser $fixtureParser,
        #[Autowire('%kernel.project_dir%/config/gpt/')] private string $configPath,
        private StorePresetManager $storePresetManager,
    ) {
    }

    public function create(StoreDetailsDto $storeDetailsDto): void
    {
        $message = $this->gptClient->chatCompletions(
            messages: [
                [
                    'role' => 'system',
                    'content' => $this->getSystemMessage(),
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($storeDetailsDto->jsonSerialize()),
                ],
            ],
            model: 'gpt-4o',
            maxCompletionTokens: 4096,
            functions: [
                $this->getGenerateFixturesFunction(),
            ],
        );

        if ($message->hasFunctionCall()) {
            $functionName = $message->getFunctionCallName();
            if ($functionName === 'generateFixtures') {
                $data = $message->getFunctionCallData();
                $fixtures = $this->fixtureParser->parse($data);
                $this->storePresetManager->updateFixtures(
                    $data['suiteName'],
                    $fixtures
                );
            } else {
                throw new \RuntimeException('Unsupported function call: ' . $functionName);
            }
        } else {
            throw new \RuntimeException('No function call in the response from OpenAI');
        }
    }

    private function getGenerateFixturesFunction(): array
    {
        $schemaPath = $this->configPath . 'fixtures_schema.json';
        if (!file_exists($schemaPath)) {
            throw new \RuntimeException('Fixtures schema file not found: ' . $schemaPath);
        }
        $fixturesSchema = json_decode(file_get_contents($schemaPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in fixtures schema file: ' . json_last_error_msg());
        }

        return [
            'name' => 'generateFixtures',
            'description' => 'It generates the final json fixtures based on the collectStoreInfo retrieved data',
            'parameters' => $fixturesSchema,
        ];
    }

    private function getSystemMessage(): string
    {
        $path = $this->configPath . 'system_instructions_fixtures_creation.md';
        if (!file_exists($path)) {
            throw new \RuntimeException('System instructions file not found: ' . $path);
        }
        $data = file_get_contents($path);
        if ($data === false) {
            throw new \RuntimeException('Failed to read system instructions file: ' . $path);
        }

        return $data;
    }
}
