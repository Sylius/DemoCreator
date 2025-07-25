<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Client\GptClient;
use App\StoreDesigner\Dto\ChatConversationDto;
use App\StoreDesigner\Dto\ChatConversationState;
use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Util\FileResourceLoader;
use App\StoreDesigner\Util\PromptPath;
use App\StoreDesigner\Util\SchemaPath;
use Random\RandomException;

final readonly class ChatConversationService
{
    public function __construct(
        private GptClient $gptClient,
        private FileResourceLoader $fileResourceLoader,
    ) {
    }

    /**
     * @throws RandomException
     * @throws \JsonException
     */
    public function processConversation(ChatConversationDto $data): ChatConversationDto
    {
        $conversationId = $data->conversationId ?? bin2hex(random_bytes(16));
        $messages = $data->messages ?? [];
        $storeDetails = $data->storeDetails;
        $state = $data->state ?? ChatConversationState::Collecting;
        $error = $data->error;

        if (empty($messages) || ($messages[0]['role'] ?? '') !== 'system') {
            array_unshift($messages, [
                'role' => 'system',
                'content' => $this->fileResourceLoader->loadPrompt(PromptPath::InterviewInstructions),
            ]);
        }

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
                functions: [[
                    'name' => 'updateStoreDetails',
                    'description' => 'Zbiera podstawowe dane sklepu',
                    'parameters' => $this->fileResourceLoader->loadSchemaArray(SchemaPath::StoreDetails),
                ]],
            );
            $messages[] = $message->toArray();

            if ($message->hasFunctionCall()) {
                $name = $message->getFunctionCallName();
                $args = $message->getFunctionCallData();
                if ($name === 'updateStoreDetails') {
                    $storeDetails = StoreDetailsDto::fromArray(
                        $args,
                        $this->fileResourceLoader->loadSchemaObject(SchemaPath::StoreDetails),
                    );
                    $messages[] = [
                        'role' => 'function',
                        'name' => $name,
                        'content' => $storeDetails->toJson(),
                    ];
                    $state = ChatConversationState::Ready;
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
            return ChatConversationState::Ready;
        }

        return ChatConversationState::Collecting;
    }
}
