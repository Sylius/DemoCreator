<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Dto\ChatConversationDto;
use App\StoreDesigner\Dto\ChatConversationState;
use App\StoreDesigner\Dto\StoreDetailsDto;
use Random\RandomException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ChatConversationService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/config/gpt/')] private string $configPath,
        private GptClient $gptClient,
    ) {
    }

    /**
     * @throws RandomException
     */
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
            'updateStoreDetails' => function (array $args) use (&$storeDetails) {
                return new StoreDetailsDto(
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
            },
        ];

        $maxCompletionTokens = 4096;
        $maxFunctionCalls = 5;
        $functionCallCount = 0;

        do {
            if ($functionCallCount++ > $maxFunctionCalls) {
                $error = 'Przekroczono maksymalną liczbę wywołań function_call (limit: ' . $maxFunctionCalls . '). Możliwe zapętlenie.';
                $state = ChatConversationState::Error;
                break;
            }
            $message = $this->gptClient->chatCompletions(
                messages: $messages,
                model: 'gpt-4.1-mini',
                maxCompletionTokens: $maxCompletionTokens,
                functions: [$this->getUpdateStoreDetailsFunction()],
            );
            $messages[] = $message->toArray();

            if ($message->hasFunctionCall()) {
                $name = $message->getFunctionCallName();
                $args = $message->getFunctionCallData();
                if ($name === 'updateStoreDetails') {
                    $storeDetails = $functionMap['updateStoreDetails']($args ?? []);
                    $messages[] = [
                        'role' => 'function',
                        'name' => $name,
                        'content' => json_encode($storeDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ];
                    $state = ChatConversationState::AwaitingConfirmation;
                } else {
                    $error = "Unknown function: $name";
                    $state = ChatConversationState::Error;
                    break;
                }
            }
        } while ($message->hasFunctionCall() && $state !== ChatConversationState::Error);

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

        return ChatConversationState::Collecting;
    }

    private function getSystemInstructions(): string
    {
        $path = $this->configPath . 'system_instructions.md';
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
                    'required' => [
                        'industry',
                        'locales',
                        'currencies',
                        'countries',
                        'categories',
                        'productsPerCat',
                        'descriptionStyle',
                        'imageStyle',
                        'zones',
                    ]
                ]
            ];
    }
}
