<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Dto\StoreDetailsDto;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;

final readonly class FixtureCreator
{
    public function __construct(
        private GptClient $gptClient,
        #[Autowire('%kernel.project_dir%/config/gpt/')] private string $configPath,
        private LoggerInterface $logger,
    )
    {
    }

    public function create(StoreDetailsDto $storeDetailsDto): array
    {
        $this->logger->info('Starting fixture creation', [
            'store_details' => $storeDetailsDto->jsonSerialize()
        ]);

        try {
            $systemMessage = $this->getSystemMessage();
            $functionDefinition = $this->getGenerateFixturesFunction();
            
            $this->logger->debug('Fixture creation configuration', [
                'system_message_length' => strlen($systemMessage),
                'function_name' => $functionDefinition['name'] ?? 'unknown'
            ]);

            $message = $this->gptClient->chatCompletions(
                messages: [
                    [
                        'role' => 'system',
                        'content' => $systemMessage,
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode($storeDetailsDto->jsonSerialize()),
                    ],
                ],
                model: 'gpt-4.1',
                maxCompletionTokens: 8192,
                functions: [
                    $functionDefinition,
                ],
            );

            $this->logger->info('GPT response received for fixture creation', [
                'has_function_call' => $message->hasFunctionCall(),
                'function_name' => $message->getFunctionCallName(),
                'is_truncated' => $message->isTruncated(),
                'usage' => $message->getUsage()
            ]);

            if (!$message->hasFunctionCall()) {
                $this->logger->error('No function call in GPT response', [
                    'content' => $message->getContent(),
                    'raw_response' => $message->getRaw()
                ]);
                throw new \RuntimeException('No function call in the response from OpenAI');
            }

            $functionName = $message->getFunctionCallName();
            if ($functionName !== 'generateFixtures') {
                $this->logger->error('Unexpected function call', [
                    'expected' => 'generateFixtures',
                    'received' => $functionName
                ]);
                throw new \RuntimeException('Unsupported function call: ' . $functionName);
            }

            $functionData = $message->getFunctionCallData();
            if ($functionData === null) {
                $this->logger->error('Failed to parse function call data', [
                    'raw_function_call' => $message->getRaw()['function_call'] ?? null
                ]);
                throw new \RuntimeException('Failed to parse function call data from OpenAI response');
            }

            $this->logger->info('Fixture creation completed successfully', [
                'data_keys' => array_keys($functionData)
            ]);

            return $functionData;
        } catch (\Exception $e) {
            $this->logger->error('Fixture creation failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
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
