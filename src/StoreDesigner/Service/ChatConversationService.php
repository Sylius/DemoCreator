<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Dto\ChatConversationDto;
use App\StoreDesigner\Dto\ChatConversationState;
use App\StoreDesigner\Dto\StoreDetailsDto;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class ChatConversationService
{
    private HttpClientInterface $client;
    private KernelInterface $kernel;

    public function __construct(
        KernelInterface $kernel,
        HttpClientInterface $client
    ) {
        $this->kernel = $kernel;
        $this->client = $client;
    }

    public function processConversation(ChatConversationDto $data): ChatConversationDto
    {
        $conversationId = $data->conversationId ?? bin2hex(random_bytes(16));
        $messages = $data->messages ?? [];
        $storeDetails = $data->storeDetails;
        $state = $data->state ?? ChatConversationState::Collecting;
        $error = $data->error;

        if (empty($messages) || ($messages[0]['role'] ?? '') !== 'system') {
            array_unshift($messages, $this->getSystemMessage());
        }

        $functionMap = [
            'updateStoreDetails' => function(array $args) use (&$storeDetails) {
                $storeDetails = new StoreDetailsDto(
                    industry: $args['industry'] ?? '',
                    locales: $args['locales'] ?? [],
                    currencies: $args['currencies'] ?? [],
                    countries: $args['countries'] ?? [],
                    categories: $args['categories'] ?? [],
                    productsPerCat: $args['productsPerCat'] ?? 0,
                    descriptionStyle: $args['descriptionStyle'] ?? null,
                    imageStyle: $args['imageStyle'] ?? null,
                    zones: $args['zones'] ?? [],
                );
                return $storeDetails;
            },
            // Dodaj inne funkcje jeśli będą potrzebne
        ];

        $model = 'gpt-4o';
        $maxCompletionTokens = 4096;
        $maxFunctionCalls = 5;
        $functionCallCount = 0;

        do {
            if ($functionCallCount++ > $maxFunctionCalls) {
                $error = 'Przekroczono maksymalną liczbę wywołań function_call (limit: ' . $maxFunctionCalls . '). Możliwe zapętlenie.';
                $state = ChatConversationState::Error;
                break;
            }
            $message = $this->askGPT(
                $messages,
                $model,
                $maxCompletionTokens
            );
            $messages[] = $message;

            if (isset($message['function_call'])) {
                $func = $message['function_call'];
                $name = $func['name'];
                $args = json_decode($func['arguments'] ?? '{}', true);
                if (isset($functionMap[$name])) {
                    $result = $functionMap[$name]($args);
                    $messages[] = [
                        'role' => 'function',
                        'name' => $name,
                        'content' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ];
                } else {
                    $error = "Unknown function: $name";
                    $state = ChatConversationState::Error;
                    break;
                }
            }
        } while (isset($message['function_call']) && $state !== ChatConversationState::Error);

        // Ustal stan na podstawie ostatniej odpowiedzi
        if ($state !== ChatConversationState::Error) {
            $state = $this->determineState($messages, $storeDetails);
        }

        return new ChatConversationDto(
            conversationId: $conversationId,
            messages: $messages,
            storeDetails: $storeDetails,
            state: $state,
            error: $error,
        );
    }

    private function determineState(array $messages, ?StoreDetailsDto $storeDetails): ChatConversationState
    {
        $last = end($messages);
        if (isset($last['function_call']) && $last['function_call']['name'] === 'generateFixtures') {
            return ChatConversationState::Generating;
        }
        if ($storeDetails && $storeDetails->industry && $storeDetails->locales && $storeDetails->currencies && $storeDetails->countries && $storeDetails->productsPerCat) {
            return ChatConversationState::AwaitingConfirmation;
        }
        if (isset($last['content']) && $last['content']) {
            return ChatConversationState::Done;
        }
        return ChatConversationState::Collecting;
    }

    private function collectStoreInfo(array $data): array
    {
        $data['locales'] = $data['locales'] ?? ['pl_PL'];
        $data['currencies'] = $data['currencies'] ?? ['PLN'];
        $data['countries'] = $data['countries'] ?? [$data['locales'][0] === 'en_US' ? 'US' : 'PL'];
        $data['categories'] = $data['categories'] ?? [];
        $data['productsPerCat'] = $data['productsPerCat'] ?? 10;
        $data['descriptionStyle'] = $data['descriptionStyle'] ?? '';
        $data['imageStyle'] = $data['imageStyle'] ?? '';
        $data['zones'] = $data['zones'] ?? ['WORLD' => ['name' => 'WORLD', 'countries' => $data['countries']]];
        return $data;
    }

    private function askGPT(array $messages, string $model, int $maxCompletionTokens = 1024): array
    {
        $apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
        if (!$apiKey) {
            throw new \Exception('OpenAI API key is not set. Please set the OPENAI_API_KEY environment variable.');
        }
        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'functions' => array_merge([
                        $this->getUpdateStoreDetailsFunction(),
                        $this->getGenerateFixturesFunction(),
                    ]),
                    'function_call' => 'auto',
                    'max_completion_tokens' => $maxCompletionTokens,
                ],
            ]);
            $body = $response->toArray(false);
            $usage = $body['usage'] ?? [];
            $message = $body['choices'][0]['message'] ?? [];
            $message['usage'] = $usage;

            return $message;
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('OpenAI API transport error: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new \RuntimeException('OpenAI API error: ' . $e->getMessage(), 0, $e);
        }
    }

    private function getSystemInstructions(): string
    {
        $path = $this->kernel->getProjectDir() . '/config/gpt/system_instructions.md';
        if (!file_exists($path)) {
            throw new \RuntimeException('System instructions file not found: ' . $path);
        }
        $data = file_get_contents($path);
        if ($data === false) {
            throw new \RuntimeException('Failed to read system instructions file: ' . $path);
        }

        return $data;
    }

    private function getSystemMessage(): array
    {
        $instructions = $this->getSystemInstructions();
        return [
            'role' => 'system',
            'content' => $instructions,
        ];
    }

    private function getUpdateStoreDetailsFunction(): array
    {
        return
            [
                'name' => 'updateStoreDetails',
                'description' => 'Zbiera podstawowe dane sklepu',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'industry' => ['type' => 'string'],
                        'locales' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'currencies' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'countries' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'zones' => [
                            'type' => 'object',
                            'additionalProperties' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'countries' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                ],
                                'required' => ['name', 'countries'],
                                'additionalProperties' => false,
                            ],
                        ],
                        'categories' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'code' => ['type' => 'string'],
                                    'name' => ['type' => 'string'],
                                    'slug' => ['type' => 'string'],
                                    'translations' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'string',
                                            ]
                                        ],
                                    ],
                                ],
                                'required' => ['code', 'name'],
                            ],
                        ],
                        'productsPerCat' => ['type' => 'integer', 'minimum' => 1],
                        'descriptionStyle' => ['type' => 'string'],
                        'imageStyle' => ['type' => 'string'],
                    ],
                    'required' => [],
                ],
            ];
    }

    private function getGenerateFixturesFunction(): array
    {
        $schemaPath = $this->kernel->getProjectDir() . '/config/gpt/core.json';

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
}
